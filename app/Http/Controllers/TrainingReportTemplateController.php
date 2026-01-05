<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\TrainingReportTemplate;
use Illuminate\Http\Request;

/**
 * Controller for managing training report templates
 */
class TrainingReportTemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index()
    {
        $this->authorize('viewAny', TrainingReportTemplate::class);

        $templates = TrainingReportTemplate::with('areas')->orderBy('created_at', 'desc')->get();
        $areas = Area::all();

        return view('admin.reporttemplates', compact('templates', 'areas'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function create()
    {
        $this->authorize('create', TrainingReportTemplate::class);

        $areas = Area::all();

        return view('admin.reporttemplates.create', compact('areas'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(Request $request)
    {
        $this->authorize('create', TrainingReportTemplate::class);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|string',
            'contentimprove' => 'nullable|string',
            'draft' => 'sometimes|boolean',
            'areas' => 'sometimes|array',
            'areas.*' => 'exists:areas,id',
        ]);

        $template = TrainingReportTemplate::create([
            'name' => $data['name'],
            'content' => $data['content'],
            'contentimprove' => $data['contentimprove'] ?? null,
            'draft' => $request->has('draft') ? (bool) $request->input('draft') : false,
        ]);

        if (isset($data['areas'])) {
            $template->areas()->sync($data['areas']);
        }

        return redirect()->route('admin.reporttemplates')->withSuccess('Training report template created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function edit(TrainingReportTemplate $template)
    {
        $this->authorize('update', $template);

        $areas = Area::all();
        $template->load('areas');

        return view('admin.reporttemplates.edit', compact('template', 'areas'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(Request $request, TrainingReportTemplate $template)
    {
        $this->authorize('update', $template);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|string',
            'contentimprove' => 'nullable|string',
            'draft' => 'sometimes|boolean',
            'areas' => 'sometimes|array',
            'areas.*' => 'exists:areas,id',
        ]);

        $template->update([
            'name' => $data['name'],
            'content' => $data['content'],
            'contentimprove' => $data['contentimprove'] ?? null,
            'draft' => $request->has('draft') ? (bool) $request->input('draft') : false,
        ]);

        if (isset($data['areas'])) {
            $template->areas()->sync($data['areas']);
        } else {
            $template->areas()->detach();
        }

        return redirect()->route('admin.reporttemplates')->withSuccess('Training report template updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function destroy(TrainingReportTemplate $template)
    {
        $this->authorize('delete', $template);

        $template->delete();

        return redirect()->route('admin.reporttemplates')->withSuccess('Training report template deleted successfully.');
    }
}

