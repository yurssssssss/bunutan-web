@extends('layouts.app')

@section('title', 'Organizer · Bunutan')

@section('content')
<div class="wrap">
    <h1>Para sa organizer</h1>

    @if (! $configured)
        <p class="center error">Wala pang password. Ilagay ang <code>ROULETTE_ORGANIZER_PASSWORD</code> sa <code>.env</code> file, saka i-refresh ang page.</p>
    @else
        <form class="stack" method="POST" action="{{ route('organizer.login.submit') }}">
            @csrf
            <input type="password" name="password" id="password" placeholder="Password" aria-label="Password" autofocus required>
            @error('password') <p class="msg">{{ $message }}</p> @enderror
            <button class="primary" type="submit">Pumasok</button>
        </form>
    @endif

    <p class="center"><a class="link" href="{{ route('roulette') }}">Bumalik sa bunutan</a></p>
</div>
@endsection
