<?php

namespace App\Services\DivisionApi\Adapters;

use App\Contracts\DivisionApiContract;
use App\Models\Area;
use App\Models\Group;
use App\Models\User;
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
     * @param  string  $url  without base url
     * @param  string  $method  HTTP method
     * @param  array  $data  to send
     * @return \Illuminate\Http\Client\Response
     */
    private function callApi($url, $method = 'GET', $data = [])
    {
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'X-API-KEY' => $this->apiToken,
        ])->$method($this->baseUrl . $url, $data);

        return $response;
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
        } else {
            return false;
        }
    }

    public function assignTheoryExam($parameters)
    {
        dd('assign theory exam here');
        /*$response = Http::post('https://core-dev.vateud.net/api/assign', [
            "user_cid" => $model->subject_user_id,
            "exam_id" => 1,
            "instructor_cid" => $model->assignee_user_id,
        ]);*/
    }
}
