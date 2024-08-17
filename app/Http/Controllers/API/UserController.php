<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Endorsement;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class UserController extends Controller
{
    /**
     * Return data based on request parameters
     *
     * @return array
     */
    public function index(Request $request)
    {
        $returnUsers = collect();

        //
        // Validate data and set paramters to defaults
        //
        $parameters = $request->validate([
            'onlyAtcActive' => 'sometimes|boolean',
            'include' => 'sometimes|array',
        ]);

        $paramIncludeAllUsers = (isset($parameters['include']) && in_array('allUsers', $parameters['include'])) ?? false;
        $paramIncludeName = (isset($parameters['include']) && in_array('name', $parameters['include'])) ?? false;
        $paramIncludeEmail = (isset($parameters['include']) && in_array('email', $parameters['include'])) ?? false;
        $paramIncludeDivisions = (isset($parameters['include']) && in_array('divisions', $parameters['include'])) ?? false;
        $paramIncludeEndorsements = (isset($parameters['include']) && in_array('endorsements', $parameters['include'])) ?? false;
        $paramIncludeRoles = (isset($parameters['include']) && in_array('roles', $parameters['include'])) ?? false;
        $paramIncludeTraining = (isset($parameters['include']) && in_array('training', $parameters['include'])) ?? false;
        $paramOnlyActive = (isset($parameters['onlyAtcActive']) && boolval($parameters['onlyAtcActive'])) ? $paramOnlyActive = true : $paramOnlyActive = false;

        // Gather which data to include in queries for optimisation
        $queryInclude = [];
        ($paramIncludeEndorsements) ? array_push($queryInclude, 'endorsements', 'endorsements.areas', 'endorsements.ratings', 'endorsements.positions') : null;
        ($paramIncludeRoles) ? array_push($queryInclude, 'groups') : null;
        ($paramIncludeTraining) ? array_push($queryInclude, 'trainings', 'trainings.ratings', 'trainings.area') : null;

        //
        // Get all needed users based on criteria
        //

        if ($paramIncludeAllUsers) {
            $returnUsers = User::where('subdivision', config('app.owner_code'));
            if ($paramOnlyActive) {
                $returnUsers = $returnUsers->whereHas('atcActivity', function ($query) {
                    $query->where('atc_active', true);
                });
            }
            $returnUsers = $returnUsers->with($queryInclude)->get();
        }

        if ($paramIncludeEndorsements) {
            $endorsementUsers = User::whereHas('endorsements', function (Builder $q) {
                $q->where('expired', false)->where('revoked', false);
            })->whereNotIn('id', $returnUsers->pluck('id'));
            if ($paramOnlyActive) {
                $endorsementUsers = $endorsementUsers->whereHas('atcActivity', function ($query) {
                    $query->where('atc_active', true);
                });
            }
            $endorsementUsers = $endorsementUsers->with($queryInclude)->get();

            $returnUsers = $returnUsers->merge($endorsementUsers);
        }

        if ($paramIncludeRoles) {
            $roleUsers = User::whereHas('groups')->whereNotIn('id', $returnUsers->pluck('id'));
            if ($paramOnlyActive) {
                $roleUsers = $roleUsers->whereHas('atcActivity', function ($query) {
                    $query->where('atc_active', true);
                });
            }
            $roleUsers = $roleUsers->with($queryInclude)->get();

            $returnUsers = $returnUsers->merge($roleUsers);
        }

        if ($paramIncludeTraining) {
            $trainingUsers = User::whereHas('trainings', function (Builder $q) {
                $q->where('status', '>=', 0);
            })->whereNotIn('id', $returnUsers->pluck('id'));
            if ($paramOnlyActive) {
                $trainingUsers = $trainingUsers->whereHas('atcActivity', function ($query) {
                    $query->where('atc_active', true);
                });
            }
            $trainingUsers = $trainingUsers->with($queryInclude)->get();

            $returnUsers = $returnUsers->merge($trainingUsers);
        }

        //
        // Process the paramters
        //

        // Endorsements
        if ($paramIncludeEndorsements) {
            foreach ($returnUsers as $user) {
                $user->endorsements = $this->mapEndorsements($user->endorsements->whereIn('type', ['EXAMINER', 'FACILITY', 'SOLO', 'VISITING'])->where('expired', false)->where('revoked', false));
            }
        }

        // Roles
        if ($paramIncludeRoles) {
            foreach ($returnUsers as $user) {
                $user->roles = collect();

                foreach (Area::all() as $area) {
                    $areaRoles = $user->groups->where('pivot.area_id', $area->id)->pluck('name');

                    if ($areaRoles->count()) {
                        $user->roles[$area->name] = ($user->groups->where('pivot.area_id', $area->id)->pluck('name'));
                    } else {
                        $user->roles[$area->name] = null;
                    }
                }
            }
        }

        // Trainings
        if ($paramIncludeTraining) {
            foreach ($returnUsers as $user) {
                $user->training = $this->mapTrainings($user->trainings->where('status', '>=', 0));
            }
        }

        //
        // Return the final result
        //
        $returnUsers = $this->mapUsers($returnUsers, $paramIncludeName, $paramIncludeEmail, $paramIncludeDivisions, $paramIncludeEndorsements, $paramIncludeRoles, $paramIncludeTraining);
        $returnUsers = $returnUsers->sortBy('id')->values();

        return response()->json(['data' => $returnUsers], 200);
    }

    /**
     * Map out the required user data
     *
     * @return Collection
     */
    private function mapUsers(Collection $users, bool $includeName, bool $includeEmail, bool $includeDivisions, bool $includeEndorsements, bool $includeRoles, bool $includeTraining)
    {
        return $users->map(function ($user) use ($includeName, $includeEmail, $includeDivisions, $includeEndorsements, $includeRoles, $includeTraining) {

            $returnData = [];

            $returnData['id'] = $user->id;
            ($includeEmail) ? $returnData['email'] = $user->email : null;
            ($includeName) ? $returnData['first_name'] = $user->first_name : null;
            ($includeName) ? $returnData['last_name'] = $user->last_name : null;
            $returnData['rating'] = $user->rating_short;
            ($includeDivisions) ? $returnData['region'] = $user->region : null;
            ($includeDivisions) ? $returnData['division'] = $user->division : null;
            ($includeDivisions) ? $returnData['subdivision'] = $user->subdivision : null;
            $returnData['atc_active'] = $user->isAtcActive();
            ($includeEndorsements) ? $returnData['endorsements'] = $user->endorsements : null;
            ($includeRoles) ? $returnData['roles'] = $user->roles : null;
            ($includeTraining) ? $returnData['training'] = $user->training : null;

            return $returnData;
        });
    }

    /**
     * Map out the endorsements and put them in a category for each type
     *
     * @return array
     */
    private function mapEndorsements(Collection $endorsements)
    {

        $returnData = [
            'visiting' => [],
            'examiner' => [],
            'solo' => [],
            'facility' => [],
        ];

        // Remembner adding the related rating/area to the query
        foreach ($endorsements as $endorsement) {

            switch ($endorsement->type) {
                case 'VISITING':
                    array_push($returnData['visiting'], $this->mapEndorsementDetails($endorsement, $endorsement->type));
                    break;
                case 'EXAMINER':
                    array_push($returnData['examiner'], $this->mapEndorsementDetails($endorsement, $endorsement->type));
                    break;
                case 'SOLO':
                    array_push($returnData['solo'], $this->mapEndorsementDetails($endorsement, $endorsement->type));
                    break;
                case 'FACILITY':
                    array_push($returnData['facility'], $this->mapEndorsementDetails($endorsement, $endorsement->type));
                    break;
            }

        }

        empty($returnData['visiting']) ? $returnData['visiting'] = null : null;
        empty($returnData['examiner']) ? $returnData['examiner'] = null : null;
        empty($returnData['solo']) ? $returnData['solo'] = null : null;
        empty($returnData['facility']) ? $returnData['facility'] = null : null;

        return $returnData;

    }

    /**
     * Map out the endorsement details
     *
     * @param  Endorsement  $endorsements
     * @param  string  $type  Type of endorsement
     * @return array
     */
    private function mapEndorsementDetails(Endorsement $endorsement, string $type)
    {
        $returnData = [
            'valid_from' => $endorsement->valid_from,
            'valid_to' => $endorsement->valid_to,
        ];

        switch ($type) {
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
            case 'FACILITY':
                $returnData['rating'] = $endorsement->ratings->first()->name;
                break;
        }

        return $returnData;
    }

    /**
     * Map out the training and put them in a category for each type
     *
     * @return array
     */
    private function mapTrainings(Collection $trainings)
    {
        return $trainings->map(function ($training) {
            return [
                'area' => $training->area->name,
                'type' => \App\Http\Controllers\TrainingController::$types[$training->type]['text'],
                'status' => $training->status,
                'status_description' => \App\Http\Controllers\TrainingController::$statuses[$training->status]['text'],
                'created_at' => $training->created_at,
                'started_at' => $training->started_at,
                'ratings' => $training->ratings->pluck('name'),
            ];
        })->values();
    }
}
