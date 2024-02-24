<tr>
    <td>
        <span data-bs-toggle="tooltip" data-bs-placement="top" title="{{ (in_array($activeFilter, ['archived'])) ? $task->closed_at->toEuropeanDateTime() : $task->created_at->toEuropeanDateTime() }}">
            {{ (in_array($activeFilter, ['archived'])) ? $task->closed_at->diffForHumans() : $task->created_at->diffForHumans() }}
        </span>
    </td>
    <td><a href="{{ route('user.show', $task->subject) }}">{{ $task->subject->name }} ({{ $task->subject->id }})</a></td>
    <td>
        <i class="fas {{ $task->type()->getIcon() }}" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ $task->type()->getName() }}"></i>

        @if($task->type()->getLink($task))
            <a href="{{ $task->type()->getLink($task) }}" target="_blank" class="link-offset-1 dotted-underline">{{ $task->type()->getText($task) }}</a>
        @else
            {{ $task->type()->getText($task) }}
        @endif
    </td>
    <td>
        @if($activeFilter == 'sent')
            <a href="{{ route('user.show', $task->assignee) }}">{{ $task->assignee->name }} ({{ $task->assignee->id }})</a>
        @else
            @isset($task->creator)
                <a href="{{ route('user.show', $task->creator) }}">{{ $task->creator->name }} ({{ $task->creator->id }})</a>
            @else
                System
            @endisset
        @endif
    </td>
    
    <td>
        @if(!in_array($activeFilter, ['sent', 'archived']))
            <div class="btn-toolbar" role="toolbar" aria-label="Task actions">
                <div class="btn-group">
                    @if($task->type()->isApproval())
                        <a href="{{ route('task.complete', $task) }}" class="btn btn-sm btn-outline-success text-center" role="button"><i class="fas fa-check"></i> Approve</a>
                    @else
                        <a href="{{ route('task.complete', $task) }}" class="btn btn-sm btn-outline-success text-center" role="button"><i class="fas fa-check"></i> Complete</a>
                    @endif

                    <a href="{{ route('task.decline', $task) }}" class="btn btn-sm btn-outline-danger text-decoration-none ms-1 text-center" title="Decline task" role="button" onclick="return confirm('Are you sure you want to decline this task?')">
                        <i class="fas fa-xmark"></i><span class="d-block d-md-none">Decline</span>
                    </a>
                </div>
            </div>
        @else
            @if($task->status == \App\Helpers\TaskStatus::COMPLETED)
                <span class="badge bg-success">{{ Str::title(\App\Helpers\TaskStatus::COMPLETED->name) }}</span>
            @elseif($task->status == \App\Helpers\TaskStatus::DECLINED)
                <span class="badge bg-danger">{{ Str::title(\App\Helpers\TaskStatus::DECLINED->name) }}</span>
            @elseif($task->status == \App\Helpers\TaskStatus::PENDING)
                <span class="badge bg-warning">{{ Str::title(\App\Helpers\TaskStatus::PENDING->name) }}</span>
            @endif
        @endif
    </td>
</tr>