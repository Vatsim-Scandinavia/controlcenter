<tr>
    <td>{{ $task->id }}</td>
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
        <a href="" class="btn btn-sm btn-outline-success"><i class="fas fa-check"></i> Complete</a>
        <a href="" class="btn btn-sm btn-outline-danger"><i class="fas fa-xmark"></i> Decline</a>
    </td>
</tr>