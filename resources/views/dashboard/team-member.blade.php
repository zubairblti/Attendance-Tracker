@extends('layouts.app')
@section('title',$employee->name.' Attendance')
@section('heading',$employee->name)
@section('subheading',($employee->departmentRelation?->name??$employee->department??'No department').' · '.$employee->role.' · Attendance details')
@section('content')
<div class="card employee-detail-head">
 <span class="avatar xl">{{ $employee->initials() }}</span>
 <div><h2>{{ $employee->name }}</h2><p>{{ $employee->email }}</p><span class="employee-meta">{{ $employee->departmentRelation?->name??$employee->department??'No department' }} · {{ $employee->role }}@if($employee->manager) · Reports to {{ $employee->manager->name }}@endif</span></div>
 <a class="btn secondary" href="{{ route('team') }}">Back to team</a>
</div>
<form class="card report-filters employee-attendance-filters" method="get" data-range-filter>
 <div class="filter-field"><label>Period</label><select name="range" data-range-select><option value="month" @selected($range==='month')>Month</option><option value="7_days" @selected($range==='7_days')>Last 7 days</option><option value="30_days" @selected($range==='30_days')>Last 30 days</option><option value="custom" @selected($range==='custom')>Custom dates</option></select></div>
 <div class="filter-field" data-range-month><label>Month</label><input type="month" name="month" value="{{ request('month',$start->format('Y-m')) }}"></div>
 <div class="filter-field" data-range-custom><label>From</label><input type="date" name="start_date" value="{{ request('start_date',$start->toDateString()) }}"></div>
 <div class="filter-field" data-range-custom><label>To</label><input type="date" name="end_date" value="{{ request('end_date',$end->toDateString()) }}"></div>
 <div class="filter-field"><label>Status</label><select name="status"><option value="">All statuses</option><option value="on_time" @selected(request('status')==='on_time')>On time</option><option value="late" @selected(request('status')==='late')>Late</option></select></div>
 <div class="filter-field compact"><label>Per page</label><select name="per_page"><option value="20" @selected(request('per_page','20')==='20')>20</option><option value="50" @selected(request('per_page')==='50')>50</option><option value="100" @selected(request('per_page')==='100')>100</option></select></div>
 <div class="filter-actions"><button class="btn primary">Apply filters</button><a class="btn secondary" href="{{ route('team.member',$employee) }}">Reset</a></div>
</form>
<div class="attendance-scope"><span>Showing {{ $start->format('d M Y') }} – {{ $end->format('d M Y') }}</span><strong>{{ (int) $start->diffInDays($end)+1 }} calendar days</strong></div>
<div class="stats-grid employee-attendance-stats">
 <article class="card stat"><small>ATTENDANCE DAYS</small><strong class="purple-text">{{ $stats['records'] }}</strong><span>clock-in records</span></article>
 <article class="card stat"><small>WORKING HOURS</small><strong class="blue-text">{{ sprintf('%dh %02dm',intdiv($stats['worked_seconds'],3600),intdiv($stats['worked_seconds']%3600,60)) }}</strong><span>selected period</span></article>
 <article class="card stat"><small>ON TIME</small><strong class="green-text">{{ $stats['on_time'] }}</strong><span>arrivals</span></article>
 <article class="card stat"><small>LATE</small><strong class="orange-text">{{ $stats['late'] }}</strong><span>arrivals</span></article>
 <article class="card stat"><small>APPROVED LEAVE</small><strong>{{ number_format($stats['leave_days'],1) }}</strong><span>days in period</span></article>
</div>
<div class="section-head"><div><h2>Attendance records</h2><p>Daily clock-in, breaks and worked time for the selected period.</p></div></div>
<div class="results-summary">Showing {{ $clocks->firstItem()??0 }}–{{ $clocks->lastItem()??0 }} of {{ $clocks->total() }} records</div>
<div class="card table-wrap"><table><thead><tr><th>Date</th><th>Clock in</th><th>Clock out</th><th>Breaks</th><th>Break duration</th><th>Worked</th><th>Status</th></tr></thead><tbody>
@forelse($clocks as $clock)<tr><td><strong>{{ $clock->clock_in->format('D, d M Y') }}</strong></td><td>{{ $clock->clock_in->format('h:i A') }}</td><td>{{ $clock->clock_out?->format('h:i A')??'Active' }}</td><td>{{ $clock->breaks->count() }}</td><td>{{ $clock->formattedDuration($clock->totalBreakSeconds()) }}</td><td><strong>{{ $clock->formattedDuration() }}</strong></td><td><span class="badge {{ $clock->status==='late'?'warning':'success' }}">{{ str_replace('_',' ',$clock->status) }}</span></td></tr>@empty<tr><td colspan="7" class="empty">No attendance records found for this period.</td></tr>@endforelse
</tbody></table></div>
{{ $clocks->links() }}
@endsection
