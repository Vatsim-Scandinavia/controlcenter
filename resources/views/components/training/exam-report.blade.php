@props([
    'report',
    // Set when the exam is shown outside its own training page: adds whose
    // training it belongs to and links out to it, rather than offering Delete.
    'showContext' => false,
])

@can('view', $report)

    @php
        $panelId = "training-exam-{$report->id}";

        $resultVariants = [
            'PASSED' => 'success',
            'FAILED' => 'danger',
            'INCOMPLETE' => 'primary',
            'POSTPONED' => 'warning',
        ];
    @endphp

    <div class="card">
        <div class="card-header p-0">
            <h5 class="mb-0 bg-lightorange">
                <button class="btn btn-link collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $panelId }}" aria-expanded="false" aria-controls="{{ $panelId }}">
                    <i class="fas fa-fw fa-chevron-right me-2"></i>
                    {{ $report->examination_date->toEuropeanDate() }}
                    @if($showContext)
                        | {{ $report->training->user?->first_name ?? 'Unknown' }}'s
                        {{ $report->training->ratings->pluck('name')->implode(' + ') }}
                        Exam
                    @endif
                </button>
            </h5>
        </div>

        <div id="{{ $panelId }}" class="collapse" data-bs-parent="#reportAccordion">
            <article class="card-body">

                <small class="text-muted">
                    @if($report->position !== null)
                        <i class="fas fa-map-marker-alt"></i> {{ $report->position->callsign }}&emsp;
                    @endif
                    <i class="fas fa-user-edit"></i> {{ $report->examiner?->name ?? 'Unknown' }}
                    @if($showContext)
                        @can('view', $report->training)
                            <a class="float-end" href="{{ route('training.show', $report->training->id) }}"><i class="fa fa-eye"></i><span class="ms-1">View training</span></a>
                        @endcan
                    @else
                        @can('delete', $report)
                            <a class="float-end" href="{{ route('training.examination.delete', $report->id) }}" onclick="return confirm('Are you sure you want to delete this examination?')"><i class="fa fa-trash"></i><span class="ms-1">Delete</span></a>
                        @endcan
                    @endif
                </small>

                <div class="mt-2">
                    @if(isset($resultVariants[$report->result]))
                        <span class="badge bg-{{ $resultVariants[$report->result] }}">{{ $report->result }}</span>
                    @endif
                </div>

                @if($report->attachments->count())
                    @foreach($report->attachments as $attachment)
                        <div>
                            <a href="{{ route('training.object.attachment.show', ['attachment' => $attachment]) }}" target="_blank">
                                <i class="fa fa-file"></i><span class="ms-1">{{ $attachment->file->name }}</span>
                            </a>
                        </div>
                    @endforeach
                @endif

            </article>
        </div>
    </div>
@endcan
