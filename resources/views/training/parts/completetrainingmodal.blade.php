<div class="modal fade" id="completeWholeTraining" tabindex="-1" aria-labelledby="completeWholeTrainingLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="completeWholeTrainingLabel">Mark training as completed</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">

                <form action="{{ route('training.action.complete', $training) }}" method="POST">

                    @csrf

                    <div class="alert alert-primary">
                        @if($outstandingRatings->isEmpty())
                            <i class="fas fa-check"></i> Complete this training and close it
                        @elseif($outstandingRatings->count() === 1)
                            <i class="fas fa-check"></i> <b>Complete the final {{ $outstandingRatings->first()->name }} rating and close this training</b>
                        @else
                            <i class="fas fa-check"></i> <b>Complete the outstanding {{ $outstandingRatings->pluck('name')->implode(', ') }} ratings and close this training</b>
                        @endif
                    </div>

                    @if($outstandingEndorsementRatings->isNotEmpty())
                        <p>This grants the {{ $outstandingEndorsementRatings->pluck('name')->implode(', ') }} {{ Str::plural('endorsement', $outstandingEndorsementRatings->count()) }}.</p>
                    @endif
                    <p>The student is marked active for {{ $training->area->name }} and emailed a confirmation.</p>

                    @php($outstandingVatsimRatings = $outstandingRatings->whereNotNull('vatsim_rating'))

                    @if($outstandingVatsimRatings->isNotEmpty())
                        @php($vatsimCount = $outstandingVatsimRatings->count())
                        <div class="alert alert-warning mb-0">{{ $outstandingVatsimRatings->pluck('name')->implode(', ') }} {{ $vatsimCount === 1 ? 'is' : 'are' }} signed off with the training rather than part by part. Check that the rating {{ Str::plural('upgrade', $vatsimCount) }} {{ $vatsimCount === 1 ? 'has' : 'have' }} been requested.</div>
                    @endif

                    <div class="modal-footer border-top-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Mark as completed</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
