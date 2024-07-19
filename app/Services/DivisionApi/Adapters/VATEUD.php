<?php

namespace App\Services\DivisionApi\Adapters;

use App\Contracts\DivisionApiContract;
use App\Helpers\VatsimRating;
use App\Models\Area;
use App\Models\Endorsement;
use App\Models\Group;
use App\Models\Position;
use App\Models\Rating;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class VATEUD implements DivisionApiContract
{
    protected $baseUrl;

    protected $apiToken;

    protected $name = 'VATEUD';

    public function __construct()
    {
        $this->baseUrl = config('vatsim.division_api_url');
        $this->apiToken = config('vatsim.division_api_token');
    }

    /**
     * Get the name of the adapter
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Call the API with all headers predefined
     *
     * @return \Illuminate\Http\Client\Response
     */
    private function callApi(string $url, string $method = 'GET', ?array $data = null, ?array $multipartData = null)
    {
        $userAgent = 'Control Center v' . config('app.version') . ' (' . config('app.owner_code') . ')';

        // Run a normal request if no multipart data is provided
        if ($multipartData === null) {
            return Http::withHeaders([
                'Accept' => 'application/json',
                'User-Agent' => $userAgent,
                'X-API-KEY' => $this->apiToken,
            ])->$method($this->baseUrl . $url, $data);
        } else {
            return Http::withHeaders([
                'Accept' => 'application/json',
                'User-Agent' => $userAgent,
                'X-API-KEY' => $this->apiToken,
            ])->asMultipart()->$method($this->baseUrl . $url, $multipartData);
        }

        return false;
    }

    /**
     * Assign a mentor to a user
     *
     * @return \Illuminate\Http\Client\Response
     */
    public function assignMentor(User $user, int $requesterId)
    {
        return $this->callApi('/facility/training/assign/' . $user->id . '/mentor', 'POST', [
            'user_cid' => $requesterId,
        ]);
    }

    /**
     * Remove a mentor from a user
     *
     * @return \Illuminate\Http\Client\Response
     */
    public function removeMentor(User $user, int $requesterId)
    {
        // Only remove from API if this is the last area in CC.
        $mentorAssignments = Group::mentors()->where('id', $user->id)->count();
        if ($mentorAssignments <= 1) {
            return $this->callApi('/facility/training/remove/' . $user->id . '/mentor', 'POST', [
                'user_cid' => $requesterId,
            ]);
        }

        return false;
    }

    /**
     * Assign an examiner to a user
     *
     * @return \Illuminate\Http\Client\Response
     */
    public function assignExaminer(User $user, Rating $rating, int $requesterId)
    {
        // Only assign if the user is S3 or higher, this is VATEUD's definition of examiner
        if ($user->rating >= VatsimRating::S3->value) {
            return $this->callApi('/facility/training/assign/' . $user->id . '/examiner', 'POST', [
                'user_cid' => $requesterId,
            ]);
        }

        return false;
    }

    /**
     * Remove an examiner from a user
     *
     * @return \Illuminate\Http\Client\Response
     */
    public function removeExaminer(User $user, Endorsement $endorsement, int $requesterId)
    {
        // Only revoke if the endorsement rating is S3 or higher, this is VATEUD's definition of examiner
        if ($endorsement->ratings->first()->vatsim_rating >= VatsimRating::S3->value) {
            return $this->callApi('/facility/training/remove/' . $user->id . '/examiner', 'POST', [
                'user_cid' => $requesterId,
            ]);
        }

        return false;
    }

    /**
     * Get the user's tier endorsements
     *
     * @return \Illuminate\Http\Client\Response
     */
    public function getTierEndorsements(int $tier)
    {
        return $this->callApi('/facility/endorsements/tier-' . $tier, 'GET');
    }

    /**
     * Assign a training position to a user
     *
     * @return \Illuminate\Http\Client\Response
     */
    public function assignTierEndorsement(User $user, Rating $rating, int $requesterId)
    {

        // Check for endorsement type to call correct endpoint, Special Center is not supported
        if ($rating->endorsement_type == 'T1') {
            return $this->callApi('/facility/endorsements/tier-1', 'POST', [
                'user_cid' => $user->id,
                'position' => $rating->name,
                'instructor_cid' => $requesterId,
            ]);
        } elseif ($rating->endorsement_type == 'T2') {
            return $this->callApi('/facility/endorsements/tier-2', 'POST', [
                'user_cid' => $user->id,
                'position' => $rating->name,
                'instructor_cid' => $requesterId,
            ]);
        }

        return false;
    }

    /**
     * Remove a training position from a user
     *
     * @return \Illuminate\Http\Client\Response
     */
    public function revokeTierEndorsement(string $tier, int $userId, string $endorsementName)
    {

        $endpointTierString = 'tier-1';
        if ($tier == 'T2') {
            $endpointTierString = 'tier-2';
        }

        $externalEndorsements = $this->callApi('/facility/endorsements/' . $endpointTierString, 'GET')->json()['data'];

        foreach ($externalEndorsements as $externalEndorsement) {
            if ($externalEndorsement['user_cid'] == $userId && $externalEndorsement['position'] == $endorsementName) {
                return $this->callApi('/facility/endorsements/' . $endpointTierString . '/' . $externalEndorsement['id'], 'DELETE');
            }
        }

        return false;
    }

    /**
     * Assign a solo endorsement to a user
     *
     * @return \Illuminate\Http\Client\Response
     */
    public function assignSoloEndorsement(User $user, Position $position, int $requesterId, ?Carbon $expireAt = null)
    {
        return $this->callApi('/facility/endorsements/solo', 'POST', [
            'user_cid' => $user->id,
            'position' => $position->callsign,
            'instructor_cid' => $requesterId,
            'expire_at' => $expireAt->toDateTimeString(),
        ]);
    }

    /**
     * Remove a solo endorsement from a user
     *
     * @return \Illuminate\Http\Client\Response
     */
    public function revokeSoloEndorsement(Endorsement $endorsement)
    {
        $externalEndorsements = $this->callApi('/facility/endorsements/solo', 'GET')->json()['data'];
        foreach ($externalEndorsements as $externalEndorsement) {
            if ($externalEndorsement['user_cid'] == $endorsement->user_id && $externalEndorsement['position'] == $endorsement->positions->first()->callsign) {
                return $this->callApi('/facility/endorsements/solo/' . $externalEndorsement['id'], 'DELETE');
            }
        }

        return false;
    }

    /**
     * Upload exam results
     *
     * @return \Illuminate\Http\Client\Response
     */
    public function uploadExamResults(int $studentId, int $examinerId, bool $pass, string $positionName, string $filePath)
    {
        return $this->callApi('/facility/user/' . $studentId . '/notes/cpt', 'POST', null, [
            [
                'name' => 'examiner_cid',
                'contents' => $examinerId,
            ],
            [
                'name' => 'position',
                'contents' => $positionName,
            ],
            [
                'name' => 'cpt_pass',
                'contents' => (int) $pass,
            ],
            [
                'name' => 'file',
                'contents' => fopen($filePath, 'r'),
            ],
        ]);
    }

    /**
     * Request a rating upgrade for a user
     *
     * @return \Illuminate\Http\Client\Response
     */
    public function requestRatingUpgrade(User $user, Rating $rating, int $requesterId)
    {
        return $this->callApi('/facility/user/' . $user->id . '/upgrade', 'POST', [
            'new_rating' => $rating->vatsim_rating,
            'instructor_cid' => $requesterId,
        ]);
    }

    /**
     * Assign a theory exam for a user
     *
     * @return \Illuminate\Http\Client\Response
     */
    public function assignTheoryExam(User $user, Rating $rating, int $requesterId)
    {

        // call facility/training/exams to get different exams, and assign the one that has flag_exam_type corresponding with the rating
        $availableExams = $this->callApi('/facility/training/exams', 'GET')->json()['data'];
        foreach ($availableExams as $exam) {
            // If the flag exam type is the same as the rating - 1, assign it. This is because VATEUD calls S2 = 2 instead of 3 like VATSIM does.
            if ($exam['flag_exam_type'] == $rating->vatsim_rating - 1) {
                return $this->callApi('/facility/training/exams/assign', 'POST', [
                    'user_cid' => $user->id,
                    'exam_id' => $exam['id'],
                    'instructor_cid' => $requesterId,
                ]);
            }
        }
    }

    /**
     * Get the user's exams
     *
     * @return \Illuminate\Http\Client\Response
     */
    public function getUserExams(User $user)
    {
        return $this->callApi('/facility/user/' . $user->id . '/exams', 'GET');
    }

    /**
     * Return if the selected user has passed the theory exam for given rating.
     *
     * @return bool
     */
    public function userHasPassedTheoryExam(User $user, Rating $rating)
    {
        $exams = $this->getUserExams($user);
        if ($exams && $exams->successful()) {
            foreach ($exams->json()['data']['results'] as $exam) {
                if ($exam['flag_exam_type'] == $rating->vatsim_rating - 1 && $exam['passed'] == true) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get the roster
     *
     * @return \Illuminate\Http\Client\Response
     */
    public function getRoster()
    {
        return $this->callApi('/facility/roster', 'GET');
    }

    /**
     * Assign a user to the roster
     *
     * @return \Illuminate\Http\Client\Response
     */
    public function assignRosterUser(int $userId)
    {
        return $this->callApi('/facility/roster/' . $userId, 'POST');
    }

    /**
     * Remove a user from the roster
     *
     * @return \Illuminate\Http\Client\Response
     */
    public function removeRosterUser(int $userId)
    {
        return $this->callApi('/facility/roster/' . $userId, 'DELETE');
    }

    /**
     * Get the link to the theory exam
     *
     * @return string
     */
    public function getExamLink()
    {
        return 'https://core.vateud.net/training/exams';
    }
}
