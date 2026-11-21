<?php

namespace App\Http\Controllers\API;

use App\Facades\DivisionApi;
use App\Http\Controllers\API\Concerns\PaginatesApiResults;
use App\Http\Controllers\API\Concerns\ResolvesApiActor;
use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Endorsement;
use App\Models\Position;
use App\Models\Rating;
use App\Models\User;
use App\Notifications\EndorsementCreatedNotification;
use App\Notifications\EndorsementModifiedNotification;
use App\Notifications\EndorsementRevokedNotification;
use App\Services\DivisionApi\DivisionApiError;
use Carbon\Carbon;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EndorsementController extends Controller
{
    use PaginatesApiResults;
    use ResolvesApiActor;

    /**
     * List endorsements.
     */
    #[Response(
        status: 200,
        description: 'Paginated endorsements, filterable by type, area, user, expired, and revoked.',
    )]
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'type' => ['sometimes', Rule::in(['FACILITY', 'SOLO', 'EXAMINER', 'VISITING'])],
            'area_id' => 'sometimes|integer|exists:areas,id',
            'user_id' => 'sometimes|integer|exists:users,id',
            'expired' => 'sometimes|boolean',
            'revoked' => 'sometimes|boolean',
        ]);

        $query = Endorsement::query()
            ->with(['user', 'ratings', 'positions', 'areas'])
            ->orderByDesc('id');

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (array_key_exists('expired', $filters)) {
            $query->where('expired', $filters['expired']);
        }

        if (array_key_exists('revoked', $filters)) {
            $query->where('revoked', $filters['revoked']);
        }

        if (isset($filters['area_id'])) {
            $query->whereHas('areas', fn ($q) => $q->where('areas.id', $filters['area_id']));
        }

        $paginator = $this->paginateQuery($query, $request);
        $paginator->getCollection()->transform(fn (Endorsement $endorsement) => $this->mapEndorsement($endorsement));

        return response()->json($this->paginatedResponse($paginator), 200);
    }

    /**
     * Create an endorsement.
     */
    #[Response(status: 201, description: 'The endorsement was created.')]
    #[Response(status: 422, description: 'Validation failed or the Division API rejected the write.')]
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'type' => ['required', Rule::in(['FACILITY', 'SOLO', 'EXAMINER', 'VISITING'])],
            'rating_id' => 'required_if:type,FACILITY,EXAMINER,VISITING|integer|exists:ratings,id',
            'position_ids' => 'required_if:type,SOLO|array|min:1',
            'position_ids.*' => 'integer|exists:positions,id',
            'area_ids' => 'required_if:type,EXAMINER,VISITING|array|min:1',
            'area_ids.*' => 'integer|exists:areas,id',
            'valid_from' => 'sometimes|date',
            'valid_to' => 'required_if:type,SOLO|nullable|date|after:valid_from',
            'actor_cid' => 'required|integer|exists:users,id',
        ]);

        $actor = $this->requireActor($request);
        $user = User::findOrFail($data['user_id']);
        $type = $data['type'];

        $endorsement = new Endorsement;
        $endorsement->user_id = $user->id;
        $endorsement->type = $type;
        $endorsement->valid_from = isset($data['valid_from']) ? Carbon::parse($data['valid_from']) : now();
        $endorsement->valid_to = isset($data['valid_to']) ? Carbon::parse($data['valid_to']) : null;
        $endorsement->issued_by = $actor->id;
        $endorsement->expired = false;
        $endorsement->revoked = false;

        if ($type === 'FACILITY') {
            $rating = Rating::findOrFail($data['rating_id']);
            $response = DivisionApi::assignTierEndorsement($user, $rating, $actor->id);
            if ($response && $response->failed()) {
                return response()->json(['message' => DivisionApiError::message($response)], 422);
            }
            $endorsement->save();
            $endorsement->ratings()->attach($rating->id);
        } elseif ($type === 'SOLO') {
            $positions = Position::whereIn('id', $data['position_ids'])->get();
            foreach ($positions as $position) {
                $response = DivisionApi::assignSoloEndorsement($user, $position, $actor->id, $endorsement->valid_to);
                if ($response && $response->failed()) {
                    return response()->json(['message' => DivisionApiError::message($response)], 422);
                }
            }
            $endorsement->save();
            $endorsement->positions()->attach($positions->pluck('id'));
        } elseif ($type === 'EXAMINER') {
            $rating = Rating::findOrFail($data['rating_id']);
            $response = DivisionApi::assignExaminer($user, $rating, $actor->id);
            if ($response && $response->failed()) {
                return response()->json(['message' => DivisionApiError::message($response)], 422);
            }
            $endorsement->save();
            $endorsement->ratings()->attach($rating->id);
            $endorsement->areas()->attach($data['area_ids']);
        } else {
            $rating = Rating::findOrFail($data['rating_id']);
            $endorsement->save();
            $endorsement->ratings()->attach($rating->id);
            $endorsement->areas()->attach($data['area_ids']);
        }

        if ($type === 'SOLO') {
            $user->notify(new EndorsementCreatedNotification($endorsement));
        }

        return response()->json(['data' => $this->mapEndorsement($endorsement->fresh(['user', 'ratings', 'positions', 'areas']))], 201);
    }

    /**
     * Revoke an endorsement.
     */
    #[Response(status: 200, description: 'The endorsement was revoked.')]
    #[Response(status: 409, description: 'The endorsement is already revoked.')]
    public function destroy(Request $request, Endorsement $endorsement): JsonResponse
    {
        if ($endorsement->revoked) {
            return response()->json(['message' => 'Endorsement is already revoked.'], 409);
        }

        $actor = $this->requireActor($request);

        $endorsement->loadMissing(['ratings', 'user', 'positions']);

        if ($endorsement->type === 'EXAMINER') {
            $response = DivisionApi::removeExaminer($endorsement->user, $endorsement, $actor->id);
            if ($response && $response->failed()) {
                return response()->json(['message' => DivisionApiError::message($response)], 422);
            }
        } elseif ($endorsement->type === 'FACILITY') {
            $rating = $endorsement->ratings->first();
            if ($rating && isset($rating->endorsement_type)) {
                $response = DivisionApi::revokeTierEndorsement($rating->endorsement_type, $endorsement->user_id, $rating->name);
                if ($response && $response->failed()) {
                    return response()->json(['message' => DivisionApiError::message($response)], 422);
                }
            }
        } elseif ($endorsement->type === 'SOLO') {
            $response = DivisionApi::revokeSoloEndorsement($endorsement);
            if ($response && $response->failed()) {
                return response()->json(['message' => DivisionApiError::message($response)], 422);
            }
        }

        $endorsement->revoked = true;
        $endorsement->revoked_by = $actor->id;
        $endorsement->valid_to = now();
        $endorsement->save();

        if ($endorsement->type === 'SOLO') {
            $endorsement->user->notify(new EndorsementRevokedNotification($endorsement));
        }

        return response()->json(['data' => $this->mapEndorsement($endorsement->fresh(['user', 'ratings', 'positions', 'areas']))], 200);
    }

    /**
     * Shorten a solo endorsement's valid_to date.
     */
    #[Response(status: 200, description: 'The solo endorsement was shortened.')]
    #[Response(status: 409, description: 'Only active solo endorsements can be shortened.')]
    public function shorten(Request $request, Endorsement $endorsement): JsonResponse
    {
        if ($endorsement->type !== 'SOLO' || $endorsement->revoked || $endorsement->expired) {
            return response()->json(['message' => 'Only active solo endorsements can be shortened.'], 409);
        }

        $data = $request->validate([
            'valid_to' => 'required|date|after:now|before:' . $endorsement->valid_to->toDateTimeString(),
            'actor_cid' => 'sometimes|integer|exists:users,id',
        ]);

        $this->resolveActor($request);

        $endorsement->valid_to = Carbon::parse($data['valid_to']);
        $endorsement->save();

        $endorsement->user->notify(new EndorsementModifiedNotification($endorsement));

        return response()->json(['data' => $this->mapEndorsement($endorsement->fresh(['user', 'ratings', 'positions', 'areas']))], 200);
    }

    /**
     * @return array<string, mixed>
     */
    private function mapEndorsement(Endorsement $endorsement): array
    {
        return [
            'id' => $endorsement->id,
            'user_id' => $endorsement->user_id,
            'type' => $endorsement->type,
            'valid_from' => $endorsement->valid_from?->toIso8601String(),
            'valid_to' => $endorsement->valid_to?->toIso8601String(),
            'expired' => (bool) $endorsement->expired,
            'revoked' => (bool) $endorsement->revoked,
            'issued_by' => $endorsement->issued_by,
            'revoked_by' => $endorsement->revoked_by,
            'ratings' => $endorsement->ratings->map(fn (Rating $rating) => [
                'id' => $rating->id,
                'name' => $rating->name,
            ])->values(),
            'positions' => $endorsement->positions->map(fn (Position $position) => [
                'id' => $position->id,
                'callsign' => $position->callsign,
            ])->values(),
            'areas' => $endorsement->areas->map(fn (Area $area) => [
                'id' => $area->id,
                'name' => $area->name,
            ])->values(),
        ];
    }
}
