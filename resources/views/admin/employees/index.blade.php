@extends('layouts.app')
@section('heading','Employees')
@section('subheading','Create users, assign roles, managers and departments.')
@section('content')
<div class="employee-page-head">
 <form class="card employee-filters" method="get">
  <div class="filter-field employee-search"><label>Search</label><input name="q" value="{{ request('q') }}" placeholder="Name or email"></div>
  <div class="filter-field"><label>Department</label><select name="department_id"><option value="">All departments</option>@foreach($departments as $department)<option value="{{ $department->id }}" @selected(request('department_id')==$department->id)>{{ $department->name }}</option>@endforeach</select></div>
  <div class="filter-field"><label>Manager / HOD</label><select name="leader_id"><option value="">All managers and HODs</option>@foreach($leaders as $leader)<option value="{{ $leader->id }}" @selected(request('leader_id')==$leader->id)>{{ $leader->name }} — {{ $leader->role }}</option>@endforeach</select></div>
  <div class="filter-actions employee-filter-actions"><button class="btn primary">Apply filters</button><a class="btn secondary" href="{{ route('admin.employees.index') }}">Reset</a><a class="btn primary add-employee" href="{{ route('admin.employees.create') }}">Add new user</a></div>
 </form>
</div>
<div class="results-summary">Showing {{ $users->firstItem()??0 }}–{{ $users->lastItem()??0 }} of {{ $users->total() }} employees</div>
<div class="card table-wrap"><table><thead><tr><th>Employee</th><th>Role</th><th>Department</th><th>Manager / HOD</th><th>Status</th><th class="actions-heading">Actions</th></tr></thead><tbody>
@forelse($users as $user)
 <tr><td><strong>{{ $user->name }}</strong><small class="block">{{ $user->email }}</small></td><td>{{ $user->role }}</td><td>{{ $user->departmentRelation?->name??$user->department??'—' }}</td><td>{{ $user->manager?->name??'—' }}@if($user->manager)<small class="block">{{ $user->manager->role }}</small>@endif</td><td><span class="badge {{ $user->employeed?'success':'neutral' }}">{{ $user->employeed?'Employed':'Unemployed' }}</span></td><td class="actions-cell"><div class="employee-actions"><a class="mini action-edit" href="{{ route('admin.employees.edit',$user) }}">Edit</a><form method="post" action="{{ route('admin.employees.toggle',$user) }}">@csrf @method('PATCH')<button class="mini">{{ $user->employeed?'Unemploy':'Employ' }}</button></form><form method="post" action="{{ route('admin.employees.destroy',$user) }}" onsubmit="return confirm('Delete this user?')">@csrf @method('DELETE')<button class="mini no">Delete</button></form></div></td></tr>
@empty<tr><td colspan="6" class="empty">No employees match these filters.</td></tr>@endforelse
</tbody></table></div>
{{ $users->links() }}
@endsection
