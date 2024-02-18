<?php

namespace App\Services\DivisionApi\Adapters;

use App\Contracts\DivisionApiContract;
use App\Models\Area;
use App\Models\User;
use Illuminate\Support\Facades\Http;

class VATEUD implements DivisionApiContract
{
    protected $baseUrl;

    protected $apiToken;

    protected $name = "VATEUD";

    public function __construct()
    {
        $this->baseUrl = config('vatsim.division_api_url');
        $this->apiToken = config('vatsim.division_api_token');
    }

    public function getName(){
        return $this->name;
    }

    public function assignMentor(Area $area, User $user, int $requesterId)
    {
        $url = $this->baseUrl . '/facility/training/assign/' . $user->id . '/mentor';
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'X-API-KEY' => $this->apiToken,
        ])->post($url, [
            'user_cid' => $requesterId,
        ]);

        return $response;
    }

    public function removeMentor(Area $area, User $user, int $requesterId)
    {
        $url = $this->baseUrl . '/facility/training/remove/' . $user->id . '/mentor';
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'X-API-KEY' => $this->apiToken,
        ])->post($url, [
            'user_cid' => $requesterId,
        ]);

        return $response;
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
