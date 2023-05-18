<?php

namespace App\Http\Controllers;

use App\Models\OneTimeLink;
use App\Models\Training;
use App\Models\TrainingObject;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Controller for generating one time link for training and exams
 */
class OneTimeLinkController extends Controller
{
    /**
     * Store the one time link in the database
     *
     * @param  TrainingObject  $object
     * @return \Illuminate\Http\RedirectResponse|string
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(Request $request, Training $training)
    {
        $data = $request->validate([
            'type' => ['required', 'string', Rule::in([OneTimeLink::TRAINING_REPORT_TYPE, OneTimeLink::TRAINING_EXAMINATION_TYPE])],
        ]);

        $this->authorize('create', [\App\Models\OneTimeLink::class, $training, $data['type']]);

        $key = sha1($training->id . now());

        OneTimeLink::create([
            'training_id' => $training->id,
            'training_object_type' => $data['type'],
            'key' => $key,
            'expires_at' => now()->addDays(7)->ceilDay(1),
        ]);

        if ($request->expectsJson()) {
            return json_encode(['key' => $key]);
        }

        return redirect()->back()->with(['key' => $key, 'success' => 'One time link successfully created']);
    }

    /**
     * Redirect the user to the appropriate URL
     *
     * @return \Illuminate\Http\RedirectResponse|void
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function redirect(Request $request, string $key)
    {
        $link = OneTimeLink::where('key', $key)->get()->first();

        session()->put('onetimekey', $key);

        // We can't find a link that matches the key provided
        if ($link == null) {
            return abort(404);
        }

        // Authorize that the user can access the link
        $this->authorize('access', [\App\Models\OneTimeLink::class, $link]);

        // Check if the link has expired
        if ($link->expires_at < now()) {
            return abort(400, 'The one time link provided has expired');
        }

        // Do the redirect
        return redirect()->to($link->getRelatedLink());
    }
}
