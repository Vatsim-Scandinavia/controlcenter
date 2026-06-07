<?php

namespace App\Http\Controllers;

use App\Models\Area;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notification;
use Illuminate\View\View;

/**
 * This controller manages each FIR's notifications settings to append
 */
class NotificationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  int  $filterArea  areaId to filter the index by
     * @return View
     *
     * @throws AuthorizationException
     */
    public function index($filterArea = 1)
    {
        $this->authorize('viewTemplates', Notification::class);

        $areas = Area::all();
        $currentArea = Area::find($filterArea);

        $template_newreq = Area::find($filterArea)->template_newreq;
        $template_newmentor = Area::find($filterArea)->template_newmentor;
        $template_pretraining = Area::find($filterArea)->template_pretraining;

        return view('admin.notificationtemplates', compact('areas', 'currentArea', 'template_newreq', 'template_newmentor', 'template_pretraining'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @return RedirectResponse
     *
     * @throws AuthorizationException
     */
    public function update(Request $request)
    {
        $data = request()->validate([
            'area' => 'required|int',
            'newrequestaddition' => 'sometimes',
            'newmentoraddition' => 'sometimes',
            'pretrainingaddition' => 'sometimes',
        ]);

        $area = Area::find($data['area']);

        $this->authorize('modifyAreaTemplate', [Notification::class, $area]);

        $area->template_newreq = $data['newrequestaddition'];
        $area->template_newmentor = $data['newmentoraddition'];
        $area->template_pretraining = $data['pretrainingaddition'];

        $area->save();

        ActivityLogController::warning('OTHER', 'Training Notification Text Updated ― Area: ' . $area->name);

        return redirect()->intended(route('admin.templates.area', $area->id))->withSuccess($area->name . "'s notifications updated.");
    }
}
