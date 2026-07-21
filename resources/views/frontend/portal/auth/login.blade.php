@extends('frontend.layout.app')
@section('title', 'Masuk')

@section('content')
<div class="portal-page">
    <div class="portal-wrap" style="max-width:460px">
        <div class="portal-crumb">
            <a href="{{ route('portal.hub') }}">Portal</a> <i class="fas fa-chevron-right"></i> <span>Masuk</span>
        </div>

        <div class="cy-auth">
            <p class="portal-kicker">// AKSES TERBATAS · SUPERADMIN</p>
            <h1 class="portal-title" style="font-size:1.7rem;margin:.2rem 0 .5rem"><i class="fas fa-lock"></i> Masuk</h1>
            <p class="cy-auth-note">Halaman <strong>18+</strong> hanya untuk akun <strong>Superadmin</strong>. Login dulu untuk melanjutkan — setelah login kamu akan kembali ke halaman yang dituju.</p>

            @if($errors->any())
                <div class="cy-notice cy-notice-err"><i class="fas fa-triangle-exclamation"></i><p>{{ $errors->first() }}</p></div>
            @endif

            <form method="POST" action="{{ route('portal.login.attempt') }}" class="cy-authform">
                @csrf
                <label>Email / Username / No. WA</label>
                <input type="text" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
                <label>Password</label>
                <input type="password" name="password" required autocomplete="current-password">
                <label class="cy-remember"><input type="checkbox" name="remember" value="1"> Ingat saya</label>
                <button type="submit" class="cy-btn" style="width:100%;justify-content:center"><i class="fas fa-right-to-bracket"></i> Masuk</button>
            </form>

            <a href="{{ route('portal.hub') }}" class="cy-auth-back"><i class="fas fa-arrow-left"></i> Kembali ke Portal</a>
        </div>
    </div>
</div>
@endsection
