<?php

namespace App\Http\Controllers;

use App\Country;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notification;

class NotificationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
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
     * @param  int  $id
     * @return \Illuminate\Http\Response
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
