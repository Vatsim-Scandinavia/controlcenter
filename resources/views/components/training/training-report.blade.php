@props([
    'report',
    // Set when the report is shown outside its own training page: adds whose
    // training it belongs to and links out to it, rather than offering Edit.
    'showContext' => false,
])

@can('view', $report)

    @php
        $panelId = "training-report-{$report->id}";
    @endphp

    <div class="card">
        <div class="card-header p-0">
            <h5 class="mb-0">
                <button class="btn btn-link collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $panelId }}" aria-expanded="false" aria-controls="{{ $panelId }}">
                    <i class="fas fa-fw fa-chevron-right me-2"></i>
                    {{ $report->report_date->toEuropeanDate() }}
                    @if($showContext)
                        | {{ $report->training->user?->first_name ?? 'Unknown' }}'s
                        {{ $report->training->ratings->pluck('name')->implode(' + ') }}
                        Training
                    @endif
                    @if($report->draft)
                        <span class="badge bg-danger">Draft</span>
                    @endif
                </button>
            </h5>
        </div>

        <div id="{{ $panelId }}" class="collapse" data-bs-parent="#reportAccordion">
            <article class="card-body">

                <small class="text-muted">
                    @if(filled($report->position))
                        <i class="fas fa-map-marker-alt"></i> {{ $report->position }}&emsp;
                    @endif
                    <i class="fas fa-user-edit"></i> {{ $report->author?->name ?? 'Unknown' }}
                    @if($showContext)
                        @can('view', $report->training)
                            <a class="float-end" href="{{ route('training.show', $report->training->id) }}"><i class="fa fa-eye"></i><span class="ms-1">View training</span></a>
                        @endcan
                    @else
                        @can('update', $report)
                            <a class="float-end" href="{{ route('training.report.edit', $report->id) }}"><i class="fa fa-pen-square"></i><span class="ms-1">Edit</span></a>
                        @endcan
                    @endif
                </small>

                <div class="mt-2 markdown-content">
                    @markdown($report->content)
                </div>

                @if(filled($report->contentimprove))
                    <hr>
                    <p class="fw-bold text-primary-emphasis">
                        <i class="fas fa-clipboard-check"></i><span class="mx-1">Areas to improve</span>
                    </p>
                    <div class="markdown-improve">
                        @markdown($report->contentimprove)
                    </div>
                @endif

                @if($report->attachments->count())
                    <hr>
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
