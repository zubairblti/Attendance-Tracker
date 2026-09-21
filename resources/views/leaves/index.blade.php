@extends('layouts.app')
@section('heading','Leave Approvals')
@section('subheading','Leave decisions are made by the employee’s reporting Manager or HOD.')
@section('content')
<div class="card table-wrap"><table><thead><tr><th>Employee</th><th>Type / dates</th><th>Reason</th><th>Status</th><th>History</th><th class="actions-heading">Action</th></tr></thead><tbody>
@forelse($leaves as $leave)
@php $canAct=auth()->user()->can('decide',$leave)&&in_array($leave->status,['pending','pending_manager'],true); @endphp
<tr><td><strong>{{ $leave->user->name }}</strong><small class="block">{{ $leave->user->department }}</small></td><td>{{ ucwords(str_replace('_',' ',$leave->leave_type)) }}<small class="block">{{ $leave->start_date->format('d M') }} – {{ $leave->end_date->format('d M Y') }}</small>@if($leave->medical_certificate_path)<a href="{{ route('leaves.certificate',$leave) }}" target="_blank">Certificate</a>@endif</td><td>{{ $leave->reason?:'—' }}</td><td><span class="badge">{{ str_replace('_',' ',$leave->status) }}</span>@if($leave->resolved_at)<small class="block">{{ $leave->resolved_at->format('d M, h:i A') }}</small>@endif</td><td>@forelse($leave->histories as $history)<small class="block"><strong>{{ $history->stage==='hod'?'HOD':ucfirst($history->stage) }} {{ $history->action==='approve'?'approved':'rejected' }}</strong> by {{ $history->actor?->name??'Unknown user' }} · {{ $history->created_at->format('d M, h:i A') }}</small>@empty—@endforelse</td><td class="actions-cell">@if($canAct)<div class="approval-actions">@foreach(['approve','reject'] as $decision)<form class="approval-form" method="post" action="{{ route('leaves.decision',[$leave,$decision]) }}">@csrf @method('PATCH')<button class="mini {{ $decision==='approve'?'ok':'no' }}">{{ ucfirst($decision) }}</button></form>@endforeach</div>@else<span class="muted-text">No action</span>@endif</td></tr>
@empty<tr><td colspan="6" class="empty">No requests found.</td></tr>@endforelse
</tbody></table></div>{{ $leaves->links() }}
@endsection
