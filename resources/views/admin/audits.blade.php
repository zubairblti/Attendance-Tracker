@extends('layouts.app')
@section('title','Audit History')
@section('heading','Audit History')
@section('subheading','Complete record of configuration changes.')
@section('content')
<div class="section-head"><div><h2>Configuration audit</h2><p>Who changed what and when</p></div></div>
<div class="audit-grid">
@forelse($audits as $audit)
<article class="card audit-card">
 <header><div><strong>{{ str($audit->configuration_type)->replace('_',' ')->title() }} updated</strong><small>@if($audit->scope_type==='department'){{ $departmentNames[$audit->scope_id]??'Department' }}@elseif($audit->scope_type==='employee'){{ $employeeNames[$audit->scope_id]??'Employee' }}@else Entire organization @endif · Effective {{ $audit->effective_from->format('d M Y') }}</small></div><time>{{ $audit->changed_at->format('d M Y, h:i A') }}</time></header>
 <div class="change-grid"><div><small>PREVIOUS</small><x-configuration-value :value="$audit->previous_value" /></div><div><small>NEW</small><x-configuration-value :value="$audit->new_value" /></div></div>
 <footer>Changed by {{ $audit->actor?->name??'Unknown user' }}</footer>
</article>
@empty
<div class="card empty">No audit records yet.</div>
@endforelse
</div>
{{ $audits->links() }}
@endsection
