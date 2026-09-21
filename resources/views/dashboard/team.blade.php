@extends('layouts.app')
@section('heading','Team Status')
@section('subheading',$scopeLabel.' · Live attendance for '.today()->format('d M Y'))
@section('content')
<div class="stats-grid team-stats">
 <article class="card stat"><small>IN SCOPE</small><strong class="purple-text">{{ $stats['total'] }}</strong><span>active employees</span></article>
 <article class="card stat"><small>WORKING</small><strong class="green-text">{{ $stats['working'] }}</strong><span>clocked in now</span></article>
 <article class="card stat"><small>ON BREAK</small><strong class="orange-text">{{ $stats['break'] }}</strong><span>currently paused</span></article>
 <article class="card stat"><small>OFF / NOT IN</small><strong>{{ $stats['off']+$stats['not_in'] }}</strong><span>{{ $stats['off'] }} clocked out · {{ $stats['not_in'] }} not in</span></article>
</div>
<form class="card report-filters team-filters" method="get">
 <div class="filter-field"><label>Search employee</label><input name="q" value="{{ request('q') }}" placeholder="Name or email"></div>
 <div class="filter-field"><label>Department</label><select name="department_id"><option value="">All departments</option>@foreach($departments as $department)<option value="{{ $department->id }}" @selected(request('department_id')==$department->id)>{{ $department->name }}</option>@endforeach</select></div>
 <div class="filter-field"><label>Today's status</label><select name="state"><option value="">All statuses</option><option value="working" @selected(request('state')==='working')>Working</option><option value="break" @selected(request('state')==='break')>On break</option><option value="off" @selected(request('state')==='off')>Clocked out</option><option value="not_in" @selected(request('state')==='not_in')>Not clocked in</option></select></div>
 <div class="filter-actions"><button class="btn primary">Apply filters</button><a class="btn secondary" href="{{ route('team') }}">Reset</a></div>
</form>
@if($team->isEmpty())
 <div class="card team-empty"><div class="empty-state-icon">&#128101;</div><h2>No employees found</h2><p>@if(request()->hasAny(['q','department_id','state']))No employees match the selected filters.@elseif(auth()->user()->isSuperAdmin())No active employees have been created yet. Add employees to start using Team Status.@else No active employees are assigned to your reporting scope yet.@endif</p>@if(auth()->user()->hasPermission('employees.manage'))<a class="btn primary" href="{{ route('admin.employees.create') }}">Add employee</a>@endif</div>
@else
 <div class="team-grid team-status-grid">
 @foreach($team as $member)
  @php $today=$member->timeClocks->first(); $state=!$today?'not_in':($today->clock_out?'off':($today->current_state==='working'?'working':'break')); @endphp
  <a class="card person large team-person" href="{{ route('team.member',$member) }}" aria-label="View {{ $member->name }} attendance details">
   <span class="avatar">{{ $member->initials() }}</span><div><strong>{{ $member->name }}</strong><small>{{ $member->departmentRelation?->name??$member->department??'No department' }} · {{ $member->role }}</small>@if($today)<small>{{ $today->clock_in->format('h:i A') }} – {{ $today->clock_out?->format('h:i A')??'Now' }}</small>@endif</div>
   <span class="badge {{ $state==='working'?'success':($state==='break'?'warning':'neutral') }}">{{ ['working'=>'Working','break'=>'On break','off'=>'Clocked out','not_in'=>'Not in'][$state] }}</span><span class="team-card-arrow" aria-hidden="true">&rsaquo;</span>
  </a>
 @endforeach
 </div>
@endif
@endsection
