<?php

namespace App\Http\Controllers;

use App\Models\OneTimeLink;
use App\Models\Training;
use App\Models\TrainingObject;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
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
     * @return RedirectResponse|string
     *
     * @throws AuthorizationException
     */
    public function store(Request $request, Training $training)
    {
        $data = $request->validate([
            'type' => ['required', 'string', Rule::in([OneTimeLink::TRAINING_REPORT_TYPE, OneTimeLink::TRAINING_EXAMINATION_TYPE])],
        ]);

        $this->authorize('create', [OneTimeLink::class, $training, $data['type']]);

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
     * @return RedirectResponse|void
     *
     * @throws AuthorizationException
     */
    public function redirect(Request $request, string $key)
    {
        $link = OneTimeLink::where('key', $key)->get()->first();

        // We can't find a link that matches the key provided
        if ($link == null) {
            return abort(404);
        }

        // Authorize that the user can access the link
        $this->authorize('access', [OneTimeLink::class, $link]);

        // Do the redirect once we insert the one-time key into the session
        $request->session()->put('onetimekey', $key);

        return redirect()->to($link->getRelatedLink());
    }
}
