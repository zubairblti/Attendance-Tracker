@extends('layouts.app')
@section('heading','My Profile')
@section('subheading','Manage your personal details and account security.')
@section('content')
<div class="profile-layout">
 <form class="card form-card" method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data">
  @csrf @method('PATCH')
  <div class="profile-head">
   @if($user->image_path)<img class="avatar xl avatar-image" src="{{ Storage::url($user->image_path) }}" alt="Profile">@else<span class="avatar xl">{{ $user->initials() }}</span>@endif
   <div><h2>{{ $user->name }}</h2><p>{{ $user->role }} · {{ $user->department }}</p></div>
  </div>
  <label>Name<input name="name" value="{{ old('name',$user->name) }}" required></label>
  <label>Email<input value="{{ $user->email }}" disabled></label>
  <label>Phone number<input name="phone_number" value="{{ old('phone_number',$user->phone_number) }}"></label>
  <label>Address<textarea name="address" rows="3">{{ old('address',$user->address) }}</textarea></label>
  <label>Profile image<input type="file" name="image" accept="image/*"></label>
  <button class="btn primary">Save profile</button>
 </form>
 <form class="card form-card" method="post" action="{{ route('profile.password') }}">
  @csrf @method('PATCH')
  <h2>Change password</h2>
  <label>Current password<input type="password" name="current_password" required></label>
  <label>New password<input type="password" name="password" required></label>
  <label>Confirm new password<input type="password" name="password_confirmation" required></label>
  <button class="btn primary">Update password</button>
 </form>
</div>
@endsection
