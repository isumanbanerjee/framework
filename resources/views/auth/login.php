@extends('layouts.guest')

@section('title')
Sign in
@endsection

@section('content')
<div class="auth-card">
    <h1>Sign in</h1>

    @if($error ?? null)
        <p style="color: #f87171;">{{ $error }}</p>
    @endif

    <form method="POST" action="/login">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required autofocus>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>

        <button type="submit">Sign in</button>
    </form>

    <p><a href="/register">Create an account</a></p>
</div>
@endsection
