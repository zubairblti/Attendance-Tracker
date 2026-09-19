@props(['value'])
@php($labels=['start_time'=>'Start time','end_time'=>'End time','grace_minutes'=>'Grace period','early_clock_in_minutes'=>'Early clock-in','minimum_full_day_minutes'=>'Minimum full day','weekdays'=>'Working days'])
@if(!$value)
 <span class="muted-text">Initial configuration</span>
@elseif(isset($value['options']))
 <div class="break-summary">@foreach($value['options'] as $option)<div class="break-card {{ ($option['active']??true)?'':'disabled' }}"><strong>{{ $option['label'] }}</strong>@if(!is_null($option['duration_minutes']??null))<span>{{ $option['duration_minutes'] }} minutes</span>@else<span>No fixed time</span>@endif</div>@endforeach</div>
@else
 <div class="readable-config">@foreach($value as $key=>$item)@continue(in_array($key,['auto_clock_out_time','auto_clock_out_source']))<div><small>{{ $labels[$key]??str($key)->replace('_',' ')->title() }}</small><strong>@if($key==='weekdays'){{ collect($item)->map(fn($d)=>[1=>'Mon',2=>'Tue',3=>'Wed',4=>'Thu',5=>'Fri',6=>'Sat',7=>'Sun'][$d]??$d)->join(', ') }}@elseif(str_contains($key,'minutes')){{ $item }} minutes @elseif(str_contains($key,'time')){{ \Illuminate\Support\Carbon::parse($item)->format('h:i A') }}@elseif(is_bool($item)){{ $item?'Enabled':'Disabled' }}@else{{ is_array($item)?json_encode($item):$item }}@endif</strong></div>@endforeach</div>
@endif
