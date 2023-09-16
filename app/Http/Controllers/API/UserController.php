<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Endorsement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Collection;

class UserController extends Controller
{
    /**
     * Get data based on GET request
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $returnUsers = collect();

        //
        // Validate data
        //
        $parameters = $request->validate([
            'include' => 'sometimes|array',
        ]);

        $paramIncludeName = (isset($parameters['include']) && in_array('name', $parameters['include'])) ?? false;
        $paramIncludeEmail = (isset($parameters['include']) && in_array('email', $parameters['include'])) ?? false;
        $paramIncludeDivisions = (isset($parameters['include']) && in_array('divisions', $parameters['include'])) ?? false;
        $paramIncludeEndorsements = (isset($parameters['include']) && in_array('endorsements', $parameters['include'])) ?? false;
        $paramIncludeRole = (isset($parameters['include']) && in_array('role', $parameters['include'])) ?? false;
        $paramIncludeTraining = (isset($parameters['include']) && in_array('training', $parameters['include'])) ?? false;
        $paramIncludeBookings = (isset($parameters['include']) && in_array('bookings', $parameters['include'])) ?? false;

        //
        // Get all division users
        //
        $returnUsers = User::where('subdivision', config('app.owner_short'))->get();

        //
        // Endorsements
        //
        if($paramIncludeEndorsements){

            $endorsementUsers = User::whereHas('endorsements', function (Builder $q){
                $q->where('expired', false)->where('revoked', false);
            })->whereNotIn('id', $returnUsers->pluck('id'))->get();

            if($endorsementUsers->count()){

                // Get all users and enrich with endorsements
                $returnUsers = $returnUsers->merge($endorsementUsers);

                foreach($returnUsers as $user){
                    $user->endorsements = $this->mapEndorsements($user->endorsements->where('expired', false)->where('revoked', false));
                }
            }
        }
        
        //
        // Return the final result
        //
        $returnUsers = $this->mapUsers($returnUsers, $paramIncludeName, $paramIncludeDivisions, $paramIncludeEmail, $paramIncludeEndorsements);
        $returnUsers = $returnUsers->sortBy('id')->values();

        return response()->json(['data' => [
            $returnUsers
        ]], 200);
    }

    /** 
     * Map out the required user data
     * 
     * @param Collection $users
     * @return Collection
     */
    private function mapUsers(Collection $users, Bool $includeName, Bool $includeDivisions, Bool $includeEmail, Bool $includeEndorsements){
        return $users->map(function ($user) use ($includeName, $includeDivisions, $includeEmail, $includeEndorsements) {

            $returnData = [];

            $returnData['id'] = $user->id;
            ($includeEmail) ? $returnData['email'] = $user->email : null;
            ($includeName) ? $returnData['first_name'] = $user->first_name : null;
            ($includeName) ? $returnData['last_name'] = $user->last_name : null;
            $returnData['rating'] = $user->rating_short;
            ($includeDivisions) ? $returnData['region'] = $user->region : null;
            ($includeDivisions) ? $returnData['division'] = $user->division : null;
            ($includeDivisions) ? $returnData['subdivision'] = $user->subdivision : null;
            $returnData['atc_active'] = boolval($user->atc_active);
            ($includeEndorsements) ? $returnData['endorsements'] = $user->endorsements : null;

            return $returnData;
        });
    }

    /**
     * Map out the endorsements and put them in a category for each type
     * 
     * @param Collection $endorsements
     * @return Collection
     */
    private function mapEndorsements(Collection $endorsements){

        $returnData = [
            'visiting' => [],
            'examiner' => [],
            'training' => [
                'solo' => [],
                's1' => [],
            ],
            'masc' => [],
        ];

        // Remembner adding the related rating/area to the query
        foreach($endorsements as $endorsement){

            switch($endorsement->type){
                case 'VISITING':
                    array_push($returnData['visiting'], $this->mapEndorsementDetails($endorsement, $endorsement->type));
                    break;
                case 'EXAMINER':
                    array_push($returnData['examiner'], $this->mapEndorsementDetails($endorsement, $endorsement->type));
                    break;
                case 'SOLO':
                    array_push($returnData['training']['solo'], $this->mapEndorsementDetails($endorsement, $endorsement->type));
                    break;
                case 'S1':
                    array_push($returnData['training']['s1'], $this->mapEndorsementDetails($endorsement, $endorsement->type));
                    break;
                case 'MASC':
                    array_push($returnData['masc'], $this->mapEndorsementDetails($endorsement, $endorsement->type));
                    break;
            }

        }

        empty($returnData['visiting']) ? $returnData['visiting'] = null : null;
        empty($returnData['examiner']) ? $returnData['examiner'] = null : null;
        empty($returnData['training']['solo']) ? $returnData['training']['solo'] = null : null;
        empty($returnData['training']['s1']) ? $returnData['training']['s1'] = null : null;
        empty($returnData['masc']) ? $returnData['masc'] = null : null;

        return $returnData;

    }

    /**
     * Map out the endorsement details
     * 
     * @param Endorsement $endorsements
     * @return Endorsement
     */
     private function mapEndorsementDetails(Endorsement $endorsement, String $type){
        $returnData = [
            'valid_from' => $endorsement->valid_from,
            'valid_to' => $endorsement->valid_to,
        ];

        switch($type){
            case 'VISITING':
                $returnData['rating'] = $endorsement->ratings->first()->name;
                $returnData['areas'] = $endorsement->areas->pluck('name');
                break;
            case 'EXAMINER':
                $returnData['rating'] = $endorsement->ratings->first()->name;
                $returnData['areas'] = $endorsement->areas->pluck('name');
                break;
            case 'SOLO':
                $returnData['positions'] = $endorsement->positions->pluck('callsign');
                break;
            case 'S1':
                $returnData['positions'] = $endorsement->positions->pluck('callsign');
                break;
            case 'MASC':
                $returnData['rating'] = $endorsement->ratings->first()->name;
                break;
        }

        return $returnData;
     }
}
