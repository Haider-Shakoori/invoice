<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Create your business</title></head>
<body>
<main>
    <h1>Start your 7-day trial</h1>
    <form method="post" action="{{ route('central.register.store') }}">
        @csrf
        <label>Company name <input name="company_name" value="{{ old('company_name') }}" required></label>
        <label>Your name <input name="owner_name" value="{{ old('owner_name') }}" required></label>
        <label>Email <input type="email" name="owner_email" value="{{ old('owner_email') }}" required></label>
        <label>Phone <input name="owner_phone" value="{{ old('owner_phone') }}"></label>
        <label>Subdomain <input name="slug" value="{{ old('slug') }}" required></label>
        <label>Password <input type="password" name="password" required></label>
        <label>Confirm password <input type="password" name="password_confirmation" required></label>
        <button type="submit">Create business</button>
    </form>
    @if($errors->any())<ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
</main>
</body>
</html>
