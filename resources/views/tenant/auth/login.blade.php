<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Business Login</title></head>
<body>
<main>
    <h1>Business login</h1>
    <form method="post" action="{{ route('tenant.login.store') }}">
        @csrf
        <label>Email <input type="email" name="email" value="{{ old('email') }}" required autofocus></label>
        <label>Password <input type="password" name="password" required></label>
        <label><input type="checkbox" name="remember" value="1"> Remember me</label>
        <button type="submit">Sign in</button>
    </form>
    @if($errors->any())<p>{{ $errors->first() }}</p>@endif
</main>
</body>
</html>
