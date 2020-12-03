<?php

namespace App\Http\Controllers;

use App\GlobalSetting;
use Illuminate\Http\Request;
use anlutro\LaravelSettings\Facade as Setting;

/**
 * This controller controls the global, app-specific and toggleble settings, such as if trainings are enabled.
 */
class GlobalSettingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param anlutro\LaravelSettings\Facade $setting
     * @return \Illuminate\View\View
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Setting $setting)
    {
        $this->authorize('index', $setting);

        return view('admin.globalsettings');
    }

    /**
     * Edit the requested resource
     *
     * @param  \Illuminate\Http\Request $request
     * @param anlutro\LaravelSettings\Facade $setting
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
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
