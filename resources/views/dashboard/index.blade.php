@extends('layouts.app')
@section('title','Dashboard')
@section('heading','Good '.(now()->hour<12?'morning':(now()->hour<17?'afternoon':'evening')).', '.strtok($u->name,' '))
@section('subheading','Here is your attendance overview for today.')
@section('content')
@php
 $activeBreak=$latest&&!$latest->clock_out?$latest->activeBreak():null;
 $closedBreakSeconds=$latest?(int)$latest->breaks->whereNotNull('break_out')->reject(fn($break)=>$break->break_type==='Meeting')->sum(fn($break)=>$break->break_in->diffInSeconds($break->break_out)):0;
@endphp
<div class="attendance-hero">
 <article class="card status-card attendance-clock-card">
  <div class="card-head"><div><span class="eyebrow">CURRENT STATUS</span><h2>{{ $latest&&!$latest->clock_out?$latest->current_state:'Not clocked in' }}</h2></div><span class="live-dot {{ $latest&&!$latest->clock_out?'online':'' }}"></span></div>
  @if($latest&&!$latest->clock_out)
   <div class="timer-label">WORKED TIME</div>
   <div class="timer" data-start="{{ $latest->clock_in->toIso8601String() }}" data-break="{{ $closedBreakSeconds }}" @if($activeBreak&&$activeBreak->break_type!=='Meeting') data-active-break="{{ $activeBreak->break_in->toIso8601String() }}" @endif aria-live="off">00:00:00</div>
   <p class="clock-meta">Started at {{ $latest->clock_in->format('h:i A') }} · {{ $latest->status==='late'?'Late arrival':'On time' }}</p>
   @if($activeBreak)
    <div class="active-break-banner"><span class="break-choice-icon" data-break-icon="{{ $activeBreak->break_type }}"></span><div><small>ACTIVE BREAK</small><strong>{{ $activeBreak->break_type }}</strong></div></div>
    <form method="post" action="{{ route('breaks.stop',[$latest,$activeBreak]) }}">@csrf @method('PATCH')<button class="btn danger full">End break</button></form>
   @else
    <div class="action-row"><form method="post" action="{{ route('clock-out') }}">@csrf<button class="btn danger">Clock out</button></form><button class="btn secondary" data-modal="break-modal">Take a break</button></div>
   @endif
  @else
   <div class="empty-clock">◷</div><p>Your shift has not started yet.</p><form method="post" action="{{ route('clock-in') }}">@csrf<button class="btn primary full">Clock in now</button></form>
  @endif
 </article>
 <div class="attendance-metrics">
  <article class="card metric dashboard-metric"><span class="metric-icon purple">◷</span><div><small>THIS WEEK</small><strong>{{ count($week) }} days</strong><p>{{ number_format($week->sum(fn($clock)=>$clock->workedSeconds())/3600,1) }} hours worked</p></div></article>
  <article class="card metric dashboard-metric"><span class="metric-icon green">✓</span><div><small>ON TIME</small><strong>{{ $month->where('status','on_time')->count() }}</strong><p>This month</p></div></article>
  <article class="card metric dashboard-metric"><span class="metric-icon orange">!</span><div><small>LATE ARRIVALS</small><strong>{{ $month->where('status','late')->count() }}</strong><p>This month</p></div></article>
 </div>
</div>
<div class="section-head"><div><h2>This week's activity</h2><p>Monday to Friday attendance summary</p></div><button class="btn secondary" data-modal="leave-modal">Request leave</button></div>
<div class="card table-wrap"><table><thead><tr><th>Date</th><th>Clock in</th><th>Clock out</th><th>Break</th><th>Worked</th><th>Status</th></tr></thead><tbody>@forelse($week as $clock)<tr><td><a href="{{ route('time-clocks.show',$clock->clock_in->toDateString()) }}"><strong>{{ $clock->clock_in->format('D, d M') }}</strong></a></td><td>{{ $clock->clock_in->format('h:i A') }}</td><td>{{ $clock->clock_out?->format('h:i A')??'—' }}</td><td>{{ $clock->formattedDuration($clock->totalBreakSeconds()) }}</td><td>{{ $clock->formattedDuration() }}</td><td><span class="badge {{ $clock->status==='late'?'warning':'success' }}">{{ str_replace('_',' ',$clock->status) }}</span></td></tr>@empty<tr><td colspan="6" class="empty">No attendance recorded this week.</td></tr>@endforelse</tbody></table></div>
@if($team->isNotEmpty())<div class="section-head"><div><h2>Team status</h2><p>Live view of your department</p></div><a href="{{ route('team') }}">View all →</a></div><div class="team-grid">@foreach($team->take(8) as $member)@php($today=$member->timeClocks->first())<article class="card person"><span class="avatar">{{ $member->initials() }}</span><div><strong>{{ $member->name }}</strong><small>{{ $member->role }}</small></div><span class="badge {{ $today&&!$today->clock_out?'success':'neutral' }}">{{ $today?($today->clock_out?'Off':$today->current_state):'Not in' }}</span></article>@endforeach</div>@endif
<dialog id="break-modal" class="break-dialog"><form method="post" action="{{ $latest?route('breaks.start',$latest):'#' }}">@csrf<header><div><h3>Take a break</h3><p>Choose the break you want to start.</p></div><button type="button" data-close aria-label="Close">×</button></header><div class="break-choice-grid">@forelse($availableBreaks as $option)<label class="break-choice"><input type="radio" name="break_type" value="{{ $option['label'] }}" @required($loop->first)><span class="break-choice-surface"><span class="break-choice-icon" data-break-icon="{{ $option['label'] }}"></span><span class="break-choice-copy"><strong>{{ $option['label'] }}</strong>@if($option['duration_minutes']??null)<small>{{ $option['duration_minutes'] }} minutes</small>@else<small>No fixed limit</small>@endif</span><span class="break-choice-check">✓</span></span></label>@empty<p class="empty">No break options are currently available.</p>@endforelse</div>@if($availableBreaks->isNotEmpty())<button class="btn primary full">Start selected break</button>@endif</form></dialog>
<dialog id="leave-modal"><form method="post" action="{{ route('leaves.store') }}" enctype="multipart/form-data">@csrf<header><h3>Request leave</h3><button type="button" data-close>×</button></header><label>Leave type<select name="leave_type" data-leave-type><option value="casual">Casual</option><option value="medical">Medical</option><option value="half_day">Half day</option><option value="emergency">Emergency</option></select></label><div class="form-grid"><label>Start date<input type="date" name="start_date" required></label><label>End date<input type="date" name="end_date" required></label></div><label>Reason<textarea name="reason" rows="3"></textarea></label><label data-medical-certificate hidden>Medical certificate <small class="block">Optional — JPG, PNG or PDF up to 5 MB.</small><input type="file" name="medical_certificate" accept=".jpg,.jpeg,.png,.pdf" disabled></label><button class="btn primary full">Submit request</button></form></dialog>
@endsection
