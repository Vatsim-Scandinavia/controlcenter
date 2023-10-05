<tr>
    <td><span data-bs-toggle="tooltip" data-bs-placement="top" title="{{ $task->created_at->toEuropeanDateTime() }}">{{ $task->created_at->diffForHumans() }}</span></td>
    <td><a href="{{ route('user.show', $task->reference_user_id) }}">{{ \App\Models\User::find($task->reference_user_id)->name }} ({{ $task->reference_user_id }})</a></td>
    <td>
        <i class="fas {{ $task->type()->getIcon() }}" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ $task->type()->getName() }}"></i>

        @if($task->type()->getLink($task))
            <a href="{{ $task->type()->getLink($task) }}" target="_blank" class="link-offset-1 dotted-underline">{{ $task->type()->getText($task) }}</a>
        @else
            {{ $task->type()->getText($task) }}
        @endif
    </td>
    <td>
        @isset($task->sender_user_id)
            <a href="{{ route('user.show', $task->sender_user_id) }}">{{ \App\Models\User::find($task->sender_user_id)->name }} ({{ $task->sender_user_id }})</a>
        @else
            System
        @endisset
    </td>
    
    <td>
        @if($activeFilter != 'sent' && $activeFilter != 'archived')
            <a href="{{ route('task.complete', $task->id) }}" class="btn btn-sm btn-outline-success text-decoration-none"><i class="fas fa-check"></i> Complete</a>
            <a href="{{ route('task.decline', $task->id) }}" class="btn btn-sm btn-outline-danger text-decoration-none"><i class="fas fa-xmark"></i> Decline</a>
        @else
            @if($task->status == \App\Helpers\TaskStatus::COMPLETED->value)
                <span class="badge bg-success">Completed</span>
            @elseif($task->status == \App\Helpers\TaskStatus::DECLINED->value)
                <span class="badge bg-danger">Declined</span>
            @elseif($task->status == \App\Helpers\TaskStatus::PENDING->value)
                <span class="badge bg-warning">Pending</span>
            @endif
        @endif
    </td>
</tr>