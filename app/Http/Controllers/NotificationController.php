<?php

namespace App\Http\Controllers;

use App\Country;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notification;

/**
 * This controller manages each FIR's notifications settings to append
 */
class NotificationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param int $filterCountry countryId to filter the index by
     * @return \Illuminate\Contracts\Support\Renderable
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index($filterCountry = 1)
    {
        $this->authorize('viewTemplates', Notification::class);

        $countries = Country::all();
        $currentCountry = Country::find($filterCountry);
        
        $template_newreq = Country::find($filterCountry)->template_newreq;
        $template_newmentor = Country::find($filterCountry)->template_newmentor;
        $template_pretraining = Country::find($filterCountry)->template_pretraining;

        return view('admin.notificationtemplates', compact('countries', 'currentCountry', 'template_newreq', 'template_newmentor', 'template_pretraining'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(Request $request)
    {

        $data = request()->validate([
            'country' => 'required|int',
            'newrequestaddition' => 'sometimes',
            'newmentoraddition' => 'sometimes',
            'pretrainingaddition' => 'sometimes',
        ]);

        $country = Country::find($data['country']);

        $this->authorize('modifyCountryTemplate', [Notification::class, $country]);

        $country->template_newreq = $data['newrequestaddition'];
        $country->template_newmentor = $data['newmentoraddition'];
        $country->template_pretraining = $data['pretrainingaddition'];
        
        $country->save();

        return redirect()->intended(route('admin.templates.country', $country->id))->withSuccess($country->name."'s notifications updated.");
    }

}
