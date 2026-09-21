<!doctype html>
<html lang="en">
<head>
 <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
 <title>Sign in · {{ $organizationSetting?->name??config('app.name') }}</title>
 <link rel="stylesheet" href="{{ asset('css/app.css') }}">
 <link rel="stylesheet" href="{{ asset('vendor/sweetalert2/sweetalert2.min.css') }}">
 <style>
  :root{--purple:{{ $organizationSetting?->primary_color??'#7357b5' }};--purple-dark:{{ $organizationSetting?->accent_color??'#58399d' }}}
  .login-art{background:radial-gradient(circle at 70% 30%,color-mix(in srgb,var(--purple) 72%,white) 0,transparent 30%),linear-gradient(145deg,var(--purple-dark),var(--purple))}
  .login-brand-logo{display:block;width:72px;height:72px;padding:8px;border-radius:17px;background:#ffffff18;object-fit:contain;box-shadow:0 8px 24px #2414472e}
 </style>
</head>
<body class="login-page">
 <div class="login-art">
  <div class="login-copy">
   @if($organizationSetting?->logo_path)<img class="login-brand-logo" src="{{ Storage::url($organizationSetting->logo_path) }}" alt="{{ $organizationSetting->name }} logo">@else<span class="brand-mark large">{{ mb_strtoupper(mb_substr($organizationSetting?->name??config('app.name'),0,1)) }}</span>@endif
   <h1>Work smarter.<br>Track better.</h1>
   <p>A simple, focused attendance workspace for the {{ $organizationSetting?->name??config('app.name') }} team.</p>
   @if($organizationSetting?->tagline)<small>{{ $organizationSetting->tagline }}</small>@endif
  </div>
 </div>
 <div class="login-panel">
  <form class="login-card" method="post" action="{{ route('login.attempt') }}">
   @csrf
   <h2>Welcome back</h2><p>Enter your details to access your workspace.</p>
   <label>Email address<input type="email" name="email" value="{{ old('email') }}" placeholder="you@company.com" required autofocus></label>
   <label>Password<input type="password" name="password" placeholder="••••••••" required></label>
   <label class="check"><input type="checkbox" name="remember" value="1"> Remember me</label>
   <button class="btn primary full">Sign in</button>
  </form>
 </div>
 <script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
 @if($errors->any())<script>Swal.fire({icon:'error',title:'Sign in failed',text:@json($errors->first()),confirmButtonColor:@json($organizationSetting?->primary_color??'#7357b5')});</script>@endif
</body>
</html>
