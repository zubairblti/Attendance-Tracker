@extends('layouts.app')
@section('heading','Edit Approvals')
@section('subheading','Two-stage attendance and break correction approvals.')
@section('content')
<div class="card table-wrap"><table><thead><tr><th>Employee</th><th>Requested</th><th>Type / reason</th><th>Status/history</th><th class="actions-heading">Action</th></tr></thead><tbody>
@forelse($requests as $item)
@php $finalizer=auth()->user()->hasPermission('requests.finalize'); $canAct=auth()->user()->can('decide',$item)&&($finalizer?in_array($item->status,['pending','pending_manager','pending_admin'],true):in_array($item->status,['pending','pending_manager'],true)); @endphp
<tr><td><strong>{{ $item->user->name }}</strong><small class="block">{{ $item->department }}</small></td><td>{{ $item->requested_clock_in->format('d M Y, h:i A') }}@if($item->requested_break_in)<small class="block">Break: {{ $item->requested_break_in->format('h:i A') }} – {{ $item->requested_break_out?->format('h:i A')??'Open' }}</small>@endif</td><td>{{ ucwords(str_replace('_',' ',$item->request_type)) }}<small class="block">{{ $item->reason }}</small></td><td><span class="badge">{{ str_replace('_',' ',$item->status) }}</span>@foreach($item->histories as $history)<small class="block"><strong>{{ ucfirst($history->stage) }} {{ $history->action==='approve'?'approved':'rejected' }}</strong> by {{ $history->actor?->name??'Unknown user' }} · {{ $history->created_at->format('d M, h:i A') }}</small>@endforeach</td><td class="actions-cell">@if($canAct)<div class="approval-actions">@foreach(['approve','reject'] as $decision)<form class="approval-form" method="post" action="{{ route('edits.decision',[$item,$decision]) }}">@csrf @method('PATCH')<button class="mini {{ $decision==='approve'?'ok':'no' }}">{{ ucfirst($decision) }}</button></form>@endforeach</div>@else<span class="muted-text">No action</span>@endif</td></tr>
@empty<tr><td colspan="5" class="empty">No requests found.</td></tr>@endforelse
</tbody></table></div>{{ $requests->links() }}
@endsection
