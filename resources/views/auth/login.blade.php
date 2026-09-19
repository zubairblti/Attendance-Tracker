<!doctype html>
<html lang="en">
<head>
 <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
 <title>Sign in · QuadRoots</title>
 <link rel="stylesheet" href="{{ asset('css/app.css') }}">
 <link rel="stylesheet" href="{{ asset('vendor/sweetalert2/sweetalert2.min.css') }}">
</head>
<body class="login-page">
 <div class="login-art"><div class="login-copy"><span class="brand-mark large">Q</span><h1>Work smarter.<br>Track better.</h1><p>A simple, focused attendance workspace for the QuadRoots team.</p></div></div>
 <div class="login-panel"><form class="login-card" method="post" action="{{ route('login.attempt') }}">@csrf<h2>Welcome back</h2><p>Enter your details to access your workspace.</p><label>Email address<input type="email" name="email" value="{{ old('email') }}" placeholder="you@quadroots.com" required autofocus></label><label>Password<input type="password" name="password" placeholder="••••••••" required></label><label class="check"><input type="checkbox" name="remember" value="1"> Remember me</label><button class="btn primary full">Sign in</button><small class="login-help">Demo: employee@example.com / password</small></form></div>
 <script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
 @if($errors->any())<script>Swal.fire({icon:'error',title:'Sign in failed',text:@json($errors->first()),confirmButtonColor:'#7357b5'});</script>@endif
</body>
</html>
