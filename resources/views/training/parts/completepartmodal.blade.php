<div class="modal fade" id="completePartialTraining" tabindex="-1" aria-labelledby="completePartialTrainingLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="completePartialTrainingLabel">Complete partial training</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">

                <form action="{{ route('training.action.completepart', ['training' => $training->id, 'rating' => $completablePart->id]) }}" method="POST">

                    @csrf

                    <div class="alert alert-primary">
                        <i class="fas fa-check"></i> Sign off the <b>{{ $completablePart->name }}</b> part of this training
                    </div>

                    <p>The training stays open for {{ $otherOutstandingRatings->pluck('name')->implode(', ') }}.</p>
                    <p>The student is marked active for {{ $training->area->name }}.</p>

                    @if(! $upgradeRequestedForPart)
                        <div class="alert alert-warning mb-0">
                            No rating upgrade request found for {{ $completablePart->name }}. Request the upgrade first, or complete it anyway if it was requested outside Control Center.
                        </div>
                    @endif

                    <div class="modal-footer border-top-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Complete {{ $completablePart->name }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
