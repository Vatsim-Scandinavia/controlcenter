<?php

namespace App\Http\Controllers;

use anlutro\LaravelSettings\Facade as Setting;
use Illuminate\Http\Request;

/**
 * This controller controls the global, app-specific and toggleble settings, such as if trainings are enabled.
 */
class GlobalSettingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  anlutro\LaravelSettings\Facade  $setting
     * @return \Illuminate\View\View
     *
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
     * @param  anlutro\LaravelSettings\Facade  $setting
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function edit(Request $request, Setting $setting)
    {
        $this->authorize('edit', $setting);

        $data = $request->validate([
            'trainingEnabled' => '',
            'trainingSOP' => 'required|url',
            'trainingExamTemplate' => '',
            'trainingSubDivisions' => 'required',
            'trainingInterval' => 'required|integer|min:1',
            'trainingSoloRequirement' => 'required|max:200',
            'atcActivityQualificationPeriod' => 'required|integer|min:1',
            'atcActivityGracePeriod' => 'required|integer|min:0',
            'atcActivityRequirement' => 'required|integer|min:0',
            'atcActivityContact' => 'max:40',
            'atcActivityBasedOnTotalHours' => '',
            'atcActivityNotifyInactive' => '',
            'atcActivityAllowReactivation' => '',
            'atcActivityAllowInactiveControlling' => '',
            'linkDomain' => 'required',
            'linkHome' => 'required|url',
            'linkJoin' => 'required|url',
            'linkContact' => 'required|url',
            'linkVisiting' => 'required|url',
            'linkDiscord' => 'required|url',
            'linkMoodle' => '',
            'divisionApiEnabled' => '',
            'feedbackEnabled' => '',
            'feedbackForwardEmail' => 'nullable|email',
            'telemetryEnabled' => '',
        ]);

        isset($data['trainingEnabled']) ? $trainingEnabled = true : $trainingEnabled = false;
        isset($data['telemetryEnabled']) ? $telemetryEnabled = true : $telemetryEnabled = false;
        isset($data['atcActivityBasedOnTotalHours']) ? $atcActivityBasedOnTotalHours = true : $atcActivityBasedOnTotalHours = false;
        isset($data['atcActivityNotifyInactive']) ? $atcActivityNotifyInactive = true : $atcActivityNotifyInactive = false;
        isset($data['atcActivityAllowReactivation']) ? $atcActivityAllowReactivation = true : $atcActivityAllowReactivation = false;
        isset($data['atcActivityAllowInactiveControlling']) ? $atcActivityAllowInactiveControlling = true : $atcActivityAllowInactiveControlling = false;
        isset($data['divisionApiEnabled']) ? $divisionApiEnabled = true : $divisionApiEnabled = false;
        isset($data['feedbackEnabled']) ? $feedbackEnabled = true : $feedbackEnabled = false;

        // The setting dependency doesn't support null values, so we need to set it to false if it's not set
        isset($data['linkMoodle']) ? $linkMoodle = $data['linkMoodle'] : $linkMoodle = false;
        isset($data['feedbackForwardEmail']) ? $feedbackForwardEmail = $data['feedbackForwardEmail'] : $feedbackForwardEmail = false;
        isset($data['trainingExamTemplate']) ? $trainingExamTemplate = $data['trainingExamTemplate'] : $trainingExamTemplate = false;

        Setting::set('trainingEnabled', $trainingEnabled);
        Setting::set('trainingSOP', $data['trainingSOP']);
        Setting::set('trainingExamTemplate', $trainingExamTemplate);
        Setting::set('trainingSubDivisions', $data['trainingSubDivisions']);
        Setting::set('trainingInterval', $data['trainingInterval']);
        Setting::set('trainingSoloRequirement', $data['trainingSoloRequirement']);
        Setting::set('atcActivityQualificationPeriod', $data['atcActivityQualificationPeriod']);
        Setting::set('atcActivityGracePeriod', $data['atcActivityGracePeriod']);
        Setting::set('atcActivityRequirement', $data['atcActivityRequirement']);
        Setting::set('atcActivityContact', $data['atcActivityContact']);
        Setting::set('atcActivityBasedOnTotalHours', $atcActivityBasedOnTotalHours);
        Setting::set('atcActivityNotifyInactive', $atcActivityNotifyInactive);
        Setting::set('atcActivityAllowReactivation', $atcActivityAllowReactivation);
        Setting::set('atcActivityAllowInactiveControlling', $atcActivityAllowInactiveControlling);
        Setting::set('linkDomain', $data['linkDomain']);
        Setting::set('linkHome', $data['linkHome']);
        Setting::set('linkJoin', $data['linkJoin']);
        Setting::set('linkContact', $data['linkContact']);
        Setting::set('linkVisiting', $data['linkVisiting']);
        Setting::set('linkDiscord', $data['linkDiscord']);
        Setting::set('linkMoodle', $linkMoodle);
        Setting::set('divisionApiEnabled', $divisionApiEnabled);
        Setting::set('feedbackEnabled', $feedbackEnabled);
        Setting::set('feedbackForwardEmail', $feedbackForwardEmail);
        Setting::set('telemetryEnabled', $telemetryEnabled);
        Setting::save();

        ActivityLogController::danger('OTHER', 'Global Settings Updated');

        return redirect()->intended(route('admin.settings'))->withSuccess('Server settings successfully changed');
    }
}
