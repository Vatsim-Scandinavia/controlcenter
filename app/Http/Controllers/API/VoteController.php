<?php

namespace App\Http\Controllers\API;

use App\Helpers\LogName;
use App\Http\Controllers\Controller;
use App\Models\Vote;
use App\Models\VoteOption;
use App\Services\ActivityLogService;
use Carbon\Carbon;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VoteController extends Controller
{
    /**
     * List staff votes.
     */
    #[Response(status: 200, description: 'All votes.')]
    public function index(): JsonResponse
    {
        $votes = Vote::query()
            ->with('option')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Vote $vote) => $this->mapVote($vote));

        return response()->json(['data' => $votes], 200);
    }

    /**
     * Show a vote.
     */
    #[Response(status: 200, description: 'A single vote with options.')]
    public function show(Vote $vote): JsonResponse
    {
        $vote->load('option');

        return response()->json(['data' => $this->mapVote($vote)], 200);
    }

    /**
     * Create a staff vote. Casting a ballot stays on the web.
     */
    #[Response(status: 201, description: 'The vote was created.')]
    #[Response(status: 422, description: 'Fewer than two options were provided.')]
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'question' => 'required|string',
            'end_at' => 'required|date|after:now',
            'options' => 'required|array|min:2',
            'options.*' => 'required|string',
            'require_active' => 'sometimes|boolean',
            'require_member' => 'sometimes|boolean',
        ]);

        $vote = new Vote;
        $vote->question = $data['question'];
        $vote->require_active = (bool) ($data['require_active'] ?? false);
        $vote->require_member = (bool) ($data['require_member'] ?? false);
        $vote->closed = false;
        $vote->end_at = Carbon::parse($data['end_at']);
        $vote->save();

        foreach ($data['options'] as $optionText) {
            $option = new VoteOption;
            $option->vote_id = $vote->id;
            $option->option = $optionText;
            $option->voted = 0;
            $option->save();
        }

        ActivityLogService::danger(LogName::Other, 'Created vote ' . $vote->id . ' ― Question: ' . $vote->question);

        return response()->json(['data' => $this->mapVote($vote->fresh('option'))], 201);
    }

    /**
     * Update a staff vote's question, expiry, or closed flag.
     */
    #[Response(status: 200, description: 'The vote was updated.')]
    public function update(Request $request, Vote $vote): JsonResponse
    {
        $data = $request->validate([
            'question' => 'sometimes|string',
            'end_at' => 'sometimes|date',
            'closed' => 'sometimes|boolean',
            'require_active' => 'sometimes|boolean',
            'require_member' => 'sometimes|boolean',
        ]);

        if (isset($data['question'])) {
            $vote->question = $data['question'];
        }
        if (isset($data['end_at'])) {
            $vote->end_at = Carbon::parse($data['end_at']);
        }
        if (array_key_exists('closed', $data)) {
            $vote->closed = (bool) $data['closed'];
        }
        if (array_key_exists('require_active', $data)) {
            $vote->require_active = (bool) $data['require_active'];
        }
        if (array_key_exists('require_member', $data)) {
            $vote->require_member = (bool) $data['require_member'];
        }

        $vote->save();

        return response()->json(['data' => $this->mapVote($vote->fresh('option'))], 200);
    }

    /**
     * @return array<string, mixed>
     */
    private function mapVote(Vote $vote): array
    {
        return [
            'id' => $vote->id,
            'question' => $vote->question,
            'require_active' => (bool) $vote->require_active,
            'require_member' => (bool) $vote->require_member,
            'closed' => (bool) $vote->closed,
            'end_at' => $this->formatTimestamp($vote->end_at),
            'options' => $vote->option->map(fn (VoteOption $option) => [
                'id' => $option->id,
                'option' => $option->option,
                'voted' => (int) $option->voted,
            ])->values(),
        ];
    }

    private function formatTimestamp(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value->toIso8601String();
        }

        return Carbon::parse($value)->toIso8601String();
    }
}
