<tr>
    <td><span data-bs-toggle="tooltip" data-bs-placement="top" title="{{ $task->created_at->toEuropeanDateTime() }}">{{ $task->created_at->diffForHumans() }}</span></td>
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
        @isset($task->creator)
            <a href="{{ route('user.show', $task->creator) }}">{{ $task->creator->name }} ({{ $task->creator->id }})</a>
        @else
            System
        @endisset
    </td>
    
    <td>
        @if(!in_array($activeFilter, ['sent', 'archived']))
            <div class="btn-toolbar justify-content-end" role="toolbar" aria-label="Task actions">
                <div class="btn-group">
                    <a href="{{ route('task.complete', $task) }}" class="btn btn-sm btn-outline-success text-end" title="Complete task" role="button"><i class="fas fa-check"></i> Complete</a>
                    <a href="{{ route('task.decline', $task) }}" class="btn btn-sm btn-outline-danger text-decoration-none ms-1" title="Decline task" role="button" onclick="return confirm('Are you sure you want to decline this task?')">
                        <i class="fas fa-xmark"></i><span class="visually-hidden">Decline</span>
                    </a>
                </div>
            </div>
        @else
            <div class="text-end">
                @if($task->status == \App\Helpers\TaskStatus::COMPLETED)
                    <span class="badge bg-success">{{ Str::title(\App\Helpers\TaskStatus::COMPLETED->name) }}</span>
                @elseif($task->status == \App\Helpers\TaskStatus::DECLINED)
                    <span class="badge bg-danger">{{ Str::title(\App\Helpers\TaskStatus::DECLINED->name) }}Declined</span>
                @elseif($task->status == \App\Helpers\TaskStatus::PENDING)
                    <span class="badge bg-warning">{{ Str::title(\App\Helpers\TaskStatus::PENDING->name) }}</span>
                @endif
            </div>
        @endif
    </td>
</tr>