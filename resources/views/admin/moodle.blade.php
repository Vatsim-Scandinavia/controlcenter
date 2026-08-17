@extends('layouts.app')

@section('title', 'Moodle Integration')

@section('content')
    @if(! $configured)
        <div class="alert alert-warning">
            Moodle is disabled or missing its URL/token configuration. Course refresh and enrolment will not run.
        </div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-header bg-primary py-3 d-flex align-items-center justify-content-between">
            <h6 class="m-0 fw-bold text-white">Moodle courses</h6>
            <form action="{{ route('admin.moodle.courses.sync') }}" method="POST">
                @csrf
                <button class="btn btn-sm btn-light" type="submit" @disabled(! $configured)>
                    <i class="fas fa-rotate"></i>&nbsp;Refresh courses
                </button>
            </form>
        </div>
        <div class="card-body">
            <p class="text-muted">
                Add only the trainings that should receive automatic Moodle assignments when they enter Pre-training.
                Assign the same course to both S1 and S2 when they share a course. Hold Ctrl/Cmd to select multiple courses.
            </p>

            @if($courses->isEmpty())
                <div class="alert alert-info mb-0">Refresh the Moodle course catalogue before configuring assignments.</div>
            @else
                <form action="{{ route('admin.moodle.rules.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row align-items-end mb-4">
                        <div class="col-lg-8 col-xl-6">
                            <label class="form-label" for="moodle-training-picker">Add automatic assignment</label>
                            <select class="form-select" id="moodle-training-picker">
                                <option value="">Select a training...</option>
                                @foreach($areas as $area)
                                    @if($area->ratings->isNotEmpty())
                                        <optgroup label="{{ $area->name }}">
                                            @foreach($area->ratings->sortBy('name') as $rating)
                                                @php
                                                    $ruleKey = $area->id.'.'.$rating->id;
                                                    $isConfigured = $rules->has($ruleKey);
                                                    $trainingLabel = $rating->name.' training';
                                                @endphp
                                                <option
                                                    value="{{ $ruleKey }}"
                                                    data-training-label="{{ $trainingLabel }}"
                                                    data-moodle-rule-configured="{{ $isConfigured ? 'true' : 'false' }}"
                                                >
                                                    {{ $trainingLabel }}{{ $isConfigured ? ' (configured)' : '' }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                @endforeach
                            </select>
                            <div class="form-text">Select an area and training first, then choose its Moodle courses below.</div>
                        </div>
                    </div>

                    <div id="moodle-rule-summary" class="alert alert-info">
                        @if($rules->isEmpty())
                            No automatic Moodle assignments are configured.
                        @else
                            {{ $rules->count() }} automatic {{ Str::plural('assignment', $rules->count()) }} configured. Select a training above to view or edit its courses.
                        @endif
                    </div>

                    <div id="moodle-rule-list">
                        @foreach($areas as $area)
                            @foreach($area->ratings->sortBy('name') as $rating)
                                @php
                                    $ruleKey = $area->id.'.'.$rating->id;
                                    $selectedCourseIds = $rules->get($ruleKey, collect())->pluck('moodle_course_id');
                                    $isConfigured = $selectedCourseIds->isNotEmpty();
                                @endphp
                                <div
                                    class="border rounded p-3 mb-3 d-none"
                                    data-moodle-rule-panel="{{ $ruleKey }}"
                                    data-moodle-rule-configured="{{ $isConfigured ? 'true' : 'false' }}"
                                >
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <label class="form-label fw-bold mb-0" for="moodle-rules-{{ $area->id }}-{{ $rating->id }}">
                                            {{ $area->name }} &mdash; {{ $rating->name }} training
                                        </label>
                                        <button class="btn btn-sm btn-outline-danger" type="button" data-remove-moodle-rule>
                                            <i class="fas fa-trash"></i>&nbsp;Remove
                                        </button>
                                    </div>
                                    <div class="row">
                                        <div class="col-xl-8">
                                            <select
                                                class="form-select"
                                                id="moodle-rules-{{ $area->id }}-{{ $rating->id }}"
                                                name="rules[{{ $area->id }}][{{ $rating->id }}][]"
                                                multiple
                                                size="4"
                                            >
                                                @foreach($courses as $course)
                                                    <option value="{{ $course->id }}" @selected($selectedCourseIds->contains($course->id))>
                                                        {{ $course->full_name }} ({{ $course->short_name }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @endforeach
                    </div>

                    <button class="btn btn-primary" type="submit">Save course assignments</button>
                </form>
            @endif
        </div>
    </div>
@endsection

@section('js')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const picker = document.getElementById('moodle-training-picker');
            const summary = document.getElementById('moodle-rule-summary');
            const panels = Array.from(document.querySelectorAll('[data-moodle-rule-panel]'));

            const pickerOption = (ruleKey) => Array.from(picker.options).find((option) => option.value === ruleKey);
            const updateSummary = () => {
                const configuredCount = panels.filter((panel) => panel.dataset.moodleRuleConfigured === 'true').length;
                summary.textContent = configuredCount === 0
                    ? 'No automatic Moodle assignments are configured.'
                    : `${configuredCount} automatic ${configuredCount === 1 ? 'assignment' : 'assignments'} configured. Select a training above to view or edit its courses.`;
            };
            const setConfigured = (panel, configured) => {
                const option = pickerOption(panel.dataset.moodleRulePanel);

                panel.dataset.moodleRuleConfigured = configured ? 'true' : 'false';
                option.dataset.moodleRuleConfigured = configured ? 'true' : 'false';
                option.textContent = `${option.dataset.trainingLabel}${configured ? ' (configured)' : ''}`;
                updateSummary();
            };

            picker.addEventListener('change', () => {
                if (! picker.value) {
                    return;
                }

                const panel = panels.find((candidate) => candidate.dataset.moodleRulePanel === picker.value);

                panels.forEach((candidate) => candidate.classList.add('d-none'));
                panel.classList.remove('d-none');
            });

            panels.forEach((panel) => {
                const coursePicker = panel.querySelector('select');

                coursePicker.addEventListener('change', () => {
                    setConfigured(panel, coursePicker.selectedOptions.length > 0);
                });

                panel.querySelector('[data-remove-moodle-rule]').addEventListener('click', () => {
                    Array.from(coursePicker.options).forEach((option) => option.selected = false);
                    panel.classList.add('d-none');
                    setConfigured(panel, false);
                    picker.value = '';
                });
            });
        });
    </script>
@endsection
