@extends('layouts.app')
@section('heading','Timesheets')
@section('subheading','Search and review complete attendance history across the company.')
@section('content')
<form class="card report-filters" method="get">
 <div class="filter-field"><label>Date</label><input type="date" name="date" value="{{ request('date') }}"></div>
 <div class="filter-field"><label>Employee</label><select name="employee_id"><option value="">All employees</option>@foreach($employees as $employee)<option value="{{ $employee->id }}" @selected(request('employee_id')==$employee->id)>{{ $employee->name }}</option>@endforeach</select></div>
 <div class="filter-field"><label>Department</label><select name="department_id"><option value="">All departments</option>@foreach($departments as $department)<option value="{{ $department->id }}" @selected(request('department_id')==$department->id)>{{ $department->name }}</option>@endforeach</select></div>
 <div class="filter-field"><label>Status</label><select name="status"><option value="">All statuses</option><option value="on_time" @selected(request('status')==='on_time')>On time</option><option value="late" @selected(request('status')==='late')>Late</option></select></div>
 <div class="filter-field compact"><label>Per page</label><select name="per_page"><option @selected($perPage===20)>20</option><option @selected($perPage===50)>50</option><option @selected($perPage===100)>100</option></select></div>
 <div class="filter-actions"><button class="btn primary">Apply filters</button><a class="btn secondary" href="{{ route('admin.timesheets') }}">Reset</a></div>
</form>
<div class="results-summary">Showing {{ $clocks->firstItem()??0 }}–{{ $clocks->lastItem()??0 }} of {{ $clocks->total() }} records</div>
<div class="card table-wrap"><table><thead><tr><th>Employee</th><th>Date</th><th>Clock in</th><th>Clock out</th><th>Breaks</th><th>Worked</th><th>Status</th></tr></thead><tbody>
@forelse($clocks as $clock)<tr><td><strong>{{ $clock->user->name }}</strong><small class="block">{{ $clock->user->departmentRelation?->name??$clock->user->department }}</small></td><td>{{ $clock->clock_in->format('d M Y') }}</td><td>{{ $clock->clock_in->format('h:i A') }}</td><td>{{ $clock->clock_out?->format('h:i A')??'Active' }}</td><td>{{ $clock->breaks->count() }}</td><td>{{ $clock->formattedDuration() }}</td><td><span class="badge {{ $clock->status==='late'?'warning':'success' }}">{{ str_replace('_',' ',$clock->status) }}</span></td></tr>@empty<tr><td colspan="7" class="empty">No timesheets match these filters.</td></tr>@endforelse
</tbody></table></div>
{{ $clocks->links() }}
@endsection
