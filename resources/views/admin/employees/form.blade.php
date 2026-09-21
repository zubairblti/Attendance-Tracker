@extends('layouts.app')
@php($editing=isset($employee))
@section('heading',$editing?'Edit Employee':'Add New User')
@section('subheading','Set account role, department, reporting manager and employment status.')
@section('content')
<form class="card form-card" method="post" action="{{ $editing?route('admin.employees.update',$employee):route('admin.employees.store') }}">
 @csrf @if($editing)@method('PUT')@endif
 <div class="form-grid"><label>Name<input name="name" value="{{ old('name',$employee->name??'') }}" required></label><label>Email<input type="email" name="email" value="{{ old('email',$employee->email??'') }}" required></label></div>
 <div class="form-grid"><label>Role / status<select name="role" id="employee-role">@foreach(['Executive'=>'Employee','Manager'=>'Manager','HOD'=>'HOD','Director'=>'Director','admin'=>'Admin','manage_admin'=>'Manage Admin','qa_admin'=>'QA Admin'] as $value=>$label)<option value="{{ $value }}" @selected(old('role',$employee->role??'Executive')===$value)>{{ $label }}</option>@endforeach</select></label><label>Department<select name="department_id"><option value="">No department</option>@foreach($departments as $department)<option value="{{ $department->id }}" @selected(old('department_id',$employee->department_id??null)==$department->id)>{{ $department->name }}</option>@endforeach</select></label></div>
 <label>Reports to manager/HOD<select name="manager_id"><option value="">No direct manager</option>@foreach($managers as $manager)<option value="{{ $manager->id }}" @selected(old('manager_id',$employee->manager_id??null)==$manager->id)>{{ $manager->name }} — {{ $manager->role }}</option>@endforeach</select></label>
 <label id="hod-departments">HOD departments<select name="hod_departments[]" multiple size="6">@foreach($departments as $department)<option value="{{ $department->id }}" @selected(isset($employee)&&$employee->hodDepartments->contains($department))>{{ $department->name }}</option>@endforeach</select></label>
 <div class="form-grid"><label>Shift time<input type="time" name="shift_time" value="{{ old('shift_time',isset($employee)?$employee->shift_time?->format('H:i'):'18:00') }}" required></label><label>Phone<input name="phone_number" value="{{ old('phone_number',$employee->phone_number??'') }}"></label></div>
 <label>Address<textarea name="address">{{ old('address',$employee->address??'') }}</textarea></label>
 <div class="form-grid"><label>Password<input type="password" name="password" {{ $editing?'':'required' }}></label><label>Confirm password<input type="password" name="password_confirmation" {{ $editing?'':'required' }}></label></div>
 <label class="check"><input type="checkbox" name="employeed" value="1" @checked(old('employeed',$employee->employeed??true))> Currently employed</label>
 <button class="btn primary">{{ $editing?'Update user':'Create user' }}</button>
</form>
@endsection
@push('scripts')<script>document.addEventListener('DOMContentLoaded',()=>{const role=document.querySelector('#employee-role'),hod=document.querySelector('#hod-departments');const sync=()=>hod.hidden=!['HOD','Director'].includes(role.value);role.addEventListener('change',sync);sync()})</script>@endpush
