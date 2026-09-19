@extends('layouts.app')
@section('heading','Attendance Analytics')
@section('subheading','Company, department and employee attendance insights for any period.')
@section('content')
<form class="card report-filters analytics-filters" method="get" id="analytics-filters">
 <div class="filter-field compact"><label>Period</label><select name="range" id="analytics-range"><option value="today" @selected($range==='today')>Today</option><option value="week" @selected($range==='week')>This week</option><option value="month" @selected($range==='month')>This month</option><option value="custom" @selected($range==='custom')>Custom dates</option></select></div>
 <div class="filter-field custom-date"><label>From</label><input type="date" name="start_date" value="{{ request('start_date',$start->toDateString()) }}"></div>
 <div class="filter-field custom-date"><label>To</label><input type="date" name="end_date" value="{{ request('end_date',$end->toDateString()) }}"></div>
 <div class="filter-field"><label>Employee</label><select name="employee_id"><option value="">All employees</option>@foreach($employees as $employee)<option value="{{ $employee->id }}" @selected(request('employee_id')==$employee->id)>{{ $employee->name }}</option>@endforeach</select></div>
 <div class="filter-field"><label>Department</label><select name="department_id"><option value="">All departments</option>@foreach($departments as $department)<option value="{{ $department->id }}" @selected(request('department_id')==$department->id)>{{ $department->name }}</option>@endforeach</select></div>
 <div class="filter-field compact"><label>Status</label><select name="status"><option value="">All statuses</option><option value="on_time" @selected(request('status')==='on_time')>On time</option><option value="late" @selected(request('status')==='late')>Late</option></select></div>
 <div class="filter-actions"><button class="btn primary">View analytics</button><a class="btn secondary" href="{{ route('admin.analytics') }}">Reset</a></div>
</form>
<div class="analytics-scope"><strong>{{ $start->format('d M Y') }} – {{ $end->format('d M Y') }}</strong><span>{{ request('employee_id')?'Selected employee':(request('department_id')?'Selected department':'All employees') }}</span></div>
<div class="analytics-stats">
 <article class="card stat"><small>ATTENDANCE RECORDS</small><strong class="purple-text">{{ $stats['records'] }}</strong><span>in selected period</span></article>
 <article class="card stat"><small>EMPLOYEES PRESENT</small><strong class="blue-text">{{ $stats['employees'] }}</strong><span>unique employees</span></article>
 <article class="card stat"><small>ON TIME</small><strong class="green-text">{{ $stats['on_time'] }}</strong><span>attendance records</span></article>
 <article class="card stat"><small>LATE ARRIVALS</small><strong class="orange-text">{{ $stats['late'] }}</strong><span>attendance records</span></article>
 <article class="card stat"><small>ABSENT DAYS</small><strong>{{ $stats['absent'] }}</strong><span>weekday attendance gaps</span></article>
 <article class="card stat"><small>WORKED HOURS</small><strong>{{ number_format($stats['worked_seconds']/3600,1) }}</strong><span>total net hours</span></article>
</div>
<div class="section-head"><div><h2>Daily attendance</h2><p>Visual bars show attendance against {{ $scopeMaximum }} eligible {{ Str::plural('employee',$scopeMaximum) }}.</p></div><a class="btn secondary" href="{{ route('admin.exports','filters',array_filter(['start_date'=>$start->toDateString(),'end_date'=>$end->toDateString(),'employee_id'=>request('employee_id'),'department_id'=>request('department_id'),'status'=>request('status')])) }}">Export filtered CSV</a></div>
<div class="card table-wrap"><table><thead><tr><th>Date</th><th>Attendance</th><th>On time</th><th>Late</th><th>Worked hours</th><th>Attendance visual</th></tr></thead><tbody>
@forelse($daily as $day)<tr><td><strong>{{ \Carbon\Carbon::parse($day->day)->format('D, d M Y') }}</strong></td><td>{{ $day->total }}</td><td><span class="badge success">{{ $day->on_time }}</span></td><td><span class="badge {{ $day->late?'warning':'neutral' }}">{{ $day->late }}</span></td><td>{{ number_format($day->worked_seconds/3600,1) }}h</td><td><div class="attendance-bar" title="{{ $day->total }} of {{ $scopeMaximum }}"><span style="width:{{ min(100,($day->total/$scopeMaximum)*100) }}%"></span></div></td></tr>@empty<tr><td colspan="6" class="empty">No attendance data matches these filters.</td></tr>@endforelse
</tbody></table></div>
@endsection
@push('scripts')<script>document.addEventListener('DOMContentLoaded',()=>{const range=document.querySelector('#analytics-range'),fields=document.querySelectorAll('.custom-date');const sync=()=>fields.forEach(field=>field.hidden=range.value!=='custom');range.addEventListener('change',sync);sync();});</script>@endpush
