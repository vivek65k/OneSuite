{{--
    Bulk fill menu options for the planning board.
    Expects: $employeeIds, $bulkDates; optional $bulkShifts (defaults to all active $shifts).
--}}
@php $bulkShifts = $bulkShifts ?? $shifts; @endphp
<form method="POST" action="{{ route('admin.shift-roster.bulk') }}" class="roster-bulk">
    @csrf
    @foreach ($employeeIds as $id)
        <input type="hidden" name="employee_ids[]" value="{{ $id }}">
    @endforeach
    @foreach ($bulkDates as $date)
        <input type="hidden" name="dates[]" value="{{ $date }}">
    @endforeach
    @foreach ($bulkShifts as $shift)
        @php $tone = \App\Support\ShiftTime::style($shift)['tone']; @endphp
        <button type="submit" name="shift_id" value="{{ $shift->id }}" class="dropdown-item d-flex align-items-center gap-2">
            <span class="kanban-dot tone-{{ $tone }}"></span> Set all to <strong>{{ $shift->code }}</strong>
        </button>
    @endforeach
    <button type="submit" name="shift_id" value="" class="dropdown-item d-flex align-items-center gap-2 text-danger" onclick="return confirm('Clear these shifts?');">
        <i class="ti ti-eraser"></i> Clear all
    </button>
</form>
