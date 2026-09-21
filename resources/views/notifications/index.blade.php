@extends('layouts.app')
@section('title','Notifications')
@section('heading','Notifications')
@section('subheading','Configuration changes and important attendance updates.')
@section('content')
<div class="section-head"><div><h2>Notification history</h2><p>{{ auth()->user()->notifications()->whereNull('read_at')->count() }} unread notifications</p></div><form method="post" action="{{ route('notifications.read-all') }}">@csrf @method('PATCH')<button class="btn secondary">Mark all as read</button></form></div>
<div class="notification-list">
@forelse($notifications as $item)
<article class="card notification-item {{ $item->read_at?'':'unread' }}">
 <div class="notification-icon"><svg viewBox="0 0 24 24"><path d="M12 8v5m0 3v.01M10.3 3.8 2.8 17a2 2 0 0 0 1.73 3h14.94a2 2 0 0 0 1.73-3L13.7 3.8a2 2 0 0 0-3.4 0Z"/></svg></div>
 <div class="notification-body">
  <div class="notification-title"><h3>{{ $item->title }}</h3><span>{{ $item->created_at->format('d M Y, h:i A') }}</span></div>
  <p>{{ $item->message }}</p>
  @if($item->type==='configuration_changed')
   <div class="change-grid"><div><small>PREVIOUS CONFIGURATION</small><x-configuration-value :value="$item->previous_value" /></div><div><small>NEW CONFIGURATION</small><x-configuration-value :value="$item->new_value" /></div></div>
   <div class="notification-meta"><span>Effective from <b>{{ $item->effective_date?->format('d F Y')??'—' }}</b></span><span>Scope <b>{{ $item->department_name??ucfirst($item->scope_type??'global') }}</b></span></div>
  @elseif(in_array($item->type,['request_created','request_decided'],true))
   @if($item->details)
    @php($details=$item->details)
    <div class="request-notification-details">
     @if(($details['request_kind']??null)==='leave')
      <span><small>LEAVE TYPE</small><strong>{{ $details['leave_type']??'—' }}</strong></span><span><small>DATES</small><strong>{{ $details['start_date']??'—' }} to {{ $details['end_date']??'—' }}</strong></span>
     @else
      <span><small>REQUEST TYPE</small><strong>{{ $details['request_type']??'—' }}</strong></span><span><small>REQUESTED CLOCK-IN</small><strong>{{ $details['requested_clock_in']??'—' }}</strong></span>
      @if($details['requested_break_in']??null)<span><small>BREAK START</small><strong>{{ $details['requested_break_in'] }}</strong></span>@endif
      @if($details['requested_break_out']??null)<span><small>BREAK END</small><strong>{{ $details['requested_break_out'] }}</strong></span>@endif
     @endif
     <span><small>STATUS</small><strong>{{ str($details['status']??'pending')->replace('_',' ')->title() }}</strong></span>
     @if($details['decision_by']??null)<span><small>DECIDED BY</small><strong>{{ $details['decision_by'] }}</strong></span>@endif
     @if($details['reason']??null)<span class="request-notification-reason"><small>REASON</small><strong>{{ $details['reason'] }}</strong></span>@endif
    </div>
   @endif
   @if($item->type==='request_created'&&$item->url)<a class="notification-link" href="{{ $item->url }}">View request &rarr;</a>@endif
  @endif
 </div>
 <div class="notification-actions">@unless($item->read_at)<form method="post" action="{{ route('notifications.read',$item) }}">@csrf @method('PATCH')<button class="mini ok">Mark read</button></form>@endunless<form method="post" action="{{ route('notifications.destroy',$item) }}" onsubmit="return confirm('Delete this notification?')">@csrf @method('DELETE')<button class="mini danger">Delete</button></form></div>
</article>
@empty<div class="card empty">No notifications yet.</div>@endforelse
</div>{{ $notifications->links() }}
@endsection
