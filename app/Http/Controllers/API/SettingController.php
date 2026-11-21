<?php

namespace App\Http\Controllers\API;

use anlutro\LaravelSettings\Facade as Setting;
use App\Helpers\LogName;
use App\Http\Controllers\Controller;
use App\Rules\InactivityReminderHours;
use App\Services\ActivityLogService;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * Keys editable through the admin settings screen (not .env secrets).
     *
     * @var array<int, string>
     */
    private const KEYS = [
        'trainingEnabled',
        'trainingSOP',
        'trainingExamTemplate',
        'trainingSubDivisions',
        'trainingInterval',
        'trainingSoloRequirement',
        'trainingSoloMaxDays',
        'atcActivityQualificationPeriod',
        'atcActivityGracePeriod',
        'atcActivityRequirement',
        'atcActivityInactivityReminder',
        'atcActivityContact',
        'atcActivityBasedOnTotalHours',
        'atcActivityNotifyInactive',
        'atcActivityAllowReactivation',
        'atcActivityAllowInactiveControlling',
        'linkDomain',
        'linkHome',
        'linkJoin',
        'linkContact',
        'linkVisiting',
        'linkDiscord',
        'linkMoodle',
        'divisionApiEnabled',
        'feedbackEnabled',
        'feedbackForwardEmail',
        'telemetryEnabled',
    ];

    /**
     * List admin settings.
     */
    #[Response(status: 200, description: 'The admin settings keys and values.')]
    public function index(): JsonResponse
    {
        $settings = [];
        foreach (self::KEYS as $key) {
            $settings[$key] = Setting::get($key);
        }

        return response()->json(['data' => $settings], 200);
    }

    /**
     * Update admin settings. Only provided keys are changed.
     */
    #[Response(status: 200, description: 'The settings were updated.')]
    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'trainingEnabled' => 'sometimes|boolean',
            'trainingSOP' => 'sometimes|url',
            'trainingExamTemplate' => 'sometimes|nullable|string',
            'trainingSubDivisions' => 'sometimes|nullable|string',
            'trainingInterval' => 'sometimes|integer|min:1',
            'trainingSoloRequirement' => 'sometimes|string|max:200',
            'trainingSoloMaxDays' => 'sometimes|integer|min:1',
            'atcActivityQualificationPeriod' => 'sometimes|integer|min:1',
            'atcActivityGracePeriod' => 'sometimes|integer|min:0',
            'atcActivityRequirement' => 'sometimes|integer|min:0',
            'atcActivityInactivityReminder' => ['sometimes', 'integer', new InactivityReminderHours],
            'atcActivityContact' => 'sometimes|string|max:40',
            'atcActivityBasedOnTotalHours' => 'sometimes|boolean',
            'atcActivityNotifyInactive' => 'sometimes|boolean',
            'atcActivityAllowReactivation' => 'sometimes|boolean',
            'atcActivityAllowInactiveControlling' => 'sometimes|boolean',
            'linkDomain' => 'sometimes|string',
            'linkHome' => 'sometimes|url',
            'linkJoin' => 'sometimes|url',
            'linkContact' => 'sometimes|url',
            'linkVisiting' => 'sometimes|url',
            'linkDiscord' => 'sometimes|url',
            'linkMoodle' => 'sometimes|nullable|string',
            'divisionApiEnabled' => 'sometimes|boolean',
            'feedbackEnabled' => 'sometimes|boolean',
            'feedbackForwardEmail' => 'sometimes|nullable|email',
            'telemetryEnabled' => 'sometimes|boolean',
        ]);

        foreach ($data as $key => $value) {
            if ($value === null && in_array($key, ['linkMoodle', 'feedbackForwardEmail', 'trainingExamTemplate', 'trainingSubDivisions'], true)) {
                $value = false;
            }
            Setting::set($key, $value);
        }

        Setting::save();
        ActivityLogService::danger(LogName::Other, 'Global Settings Updated via API');

        return $this->index();
    }
}
