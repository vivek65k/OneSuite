<div class="table-responsive mb-2">
    <table class="table table-sm mb-0">
        <thead class="thead-light"><tr><th>Title</th><th>Assigned to</th><th>Due date</th><th>Status</th><th></th></tr></thead>
        <tbody>
            @forelse ($tasks as $task)
                <tr class="{{ $task->is_completed ? 'text-muted' : '' }}">
                    <td class="{{ $task->is_completed ? 'text-decoration-line-through' : '' }}">{{ $task->title }}</td>
                    <td>{{ $task->assignedTo?->name ?: '—' }}</td>
                    <td>{{ optional($task->due_date)->format('M j, Y') ?: '—' }}</td>
                    <td>
                        @if ($task->is_completed)
                            <span class="badge badge-soft-success">Completed</span>
                        @else
                            <span class="badge badge-soft-warning">Pending</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <form action="{{ route('admin.onboarding.tasks.toggle', $task) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn-icon" title="{{ $task->is_completed ? 'Reopen' : 'Mark complete' }}">
                                <i class="ti {{ $task->is_completed ? 'ti-rotate' : 'ti-circle-check' }} font-16"></i>
                            </button>
                        </form>
                        @can('delete', $task)
                            <form action="{{ route('admin.onboarding.tasks.destroy', $task) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this task?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-icon" title="Delete"><i class="ti ti-trash font-16"></i></button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-muted text-center py-2">No tasks yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
