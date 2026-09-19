@extends('layouts.guest')

@section('title')
Create an account
@endsection

@section('content')
<div class="auth-card">
    <h1>Create an account</h1>

    @if($error ?? null)
        <p style="color: #f87171;">{{ $error }}</p>
    @endif

    <form method="POST" action="/register">
        <input type="hidden" name="csrf_token" value="{{ $csrfToken }}">

        <label for="name">Name</label>
        <input type="text" id="name" name="name" required autofocus>

        <label for="email">Email</label>
        <input type="email" id="email" name="email" required>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>

        <label for="password_confirmation">Confirm password</label>
        <input type="password" id="password_confirmation" name="password_confirmation" required>

        <button type="submit">Create account</button>
    </form>

    <p><a href="/login">Already have an account? Sign in</a></p>
</div>
@endsection
