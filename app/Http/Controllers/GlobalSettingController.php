<?php

namespace App\Http\Controllers;

use App\GlobalSetting;
use Illuminate\Http\Request;
use anlutro\LaravelSettings\Facade as Setting;

class GlobalSettingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Setting $setting)
    {
        $this->authorize('index', $setting);

        return view('admin.globalsettings');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Group  $group
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, Setting $setting)
    {

        $this->authorize('edit', $setting);

        $data = $request->validate([
            'trainingEnabled' => '',
            'trainingShowEstimate' => '',
            'trainingSOP' => 'required|url',
            'trainingSubDivisions' => 'required',
            'trainingQueue' => 'required|min:10|max:250'
        ]);

        isset($data['trainingEnabled']) ? $trainingEnabled = true : $trainingEnabled = false;
        isset($data['trainingShowEstimate']) ? $trainingShowEstimate = true : $trainingShowEstimate = false;

        Setting::set('trainingEnabled', $trainingEnabled);
        Setting::set('trainingShowEstimate', $trainingShowEstimate);
        Setting::set('trainingSOP', $data['trainingSOP']);
        Setting::set('trainingSubDivisions', $data['trainingSubDivisions']);
        Setting::set('trainingQueue', $data['trainingQueue']);
        Setting::save();

        return redirect()->intended(route('admin.settings'))->withSuccess("Server settings successfully changed");
    }

}
