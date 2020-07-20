<?php

namespace App\Http\Controllers;

use App\Country;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MentorController extends Controller
{

    /**
     * Add a country to the list of countries that mentor has
     *
     * @param Request $request
     * @param User $user
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function addCountry(Request $request, User $user)
    {
        // TODO auth

        $data = $request->validate([
            'country' => 'required|integer'
        ]);

        $user->mentor_countries()->attach($data['country'], ['inserted_by' => Auth::id()]);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Mentor successfully updated']);
        }

        return redirect()->back()->withSuccess('Mentor successfully updated');

    }

    /**
     * Add a user to the list of mentors for the given country
     *
     * @param Request $request
     * @param Country $country
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function addMentor(Request $request, Country $country)
    {
        // TODO auth

        $data = $request->validate([
            'mentor' => 'required|integer'
        ]);

        $country->mentors()->attach($data['mentor'], ['inserted_by' => Auth::id()]);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Mentor successfully added']);
        }

        return redirect()->back()->withSuccess('Mentor successfully added');
    }

    /**
     * Removes the given country from the list of countries the mentor has
     *
     * @param Request $request
     * @param User $user
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function removeCountry(Request $request, User $user)
    {
        // TODO auth

        $data = $request->validate([
            'country' => 'required|integer'
        ]);

        $user->mentor_countries()->detach($data['country']);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Mentor successfully updated']);
        }

        return redirect()->back()->withSuccess('Mentor successfully updated');

    }

    /**
     * Removes the given user from the list of mentors in the given country
     *
     * @param Request $request
     * @param Country $country
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function removeMentor(Request $request, Country $country)
    {
        // TODO auth

        $data = $request->validate([
            'mentor' => 'required|integer'
        ]);

        $country->mentors()->detach($data['mentor']);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Mentor successfully removed']);
        }

        return redirect()->back()->withSuccess('Mentor successfully removed');

    }

}
