@extends('layouts.app')

@section('content')
<div class="wrap" id="app"
     data-check-url="{{ route('roulette.check') }}"
     data-spin-url="{{ route('roulette.spin') }}">
    <div class="player-bar">
        <button class="back-btn" type="button" id="backBtn" data-back hidden>
            <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M15 18l-6-6 6-6" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Bumalik
        </button>
    </div>
    <h1>Bunutan</h1>

    {{-- Hakbang 1: pangalan --}}
    <section class="stack" id="stName">
        <h2>Ano ang pangalan mo?</h2>
        <form class="stack" id="nameForm" autocomplete="off">
            <input id="nameInput" type="text" placeholder="I-type ang pangalan mo" aria-label="Pangalan mo" maxlength="60">
            <button class="primary" type="submit">Susunod</button>
        </form>
        <p class="msg" id="nameMsg" aria-live="polite"></p>

        {{-- Listahan: pindutin ang pangalan para makopya ang tamang spelling --}}
        <div class="namelist" id="nameList">
            <p class="center muted">Hanapin ang pangalan mo sa listahan at pindutin ito para makopya ang tamang spelling.</p>
            @foreach ($groups as $group)
                <div class="namelist-group">
                    <h3>{{ $group['label'] }}</h3>
                    @if ($group['names']->isEmpty())
                        <p class="muted">Wala pang pangalan.</p>
                    @else
                        <ul>
                            @foreach ($group['names'] as $name)
                                <li><button type="button" class="name-pick" data-name="{{ $name }}">{{ $name }}</button></li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach
            <p class="center muted" id="noMatch" hidden>Walang tugmang pangalan sa listahan.</p>
        </div>
    </section>

    {{-- Hakbang 2: Matanda o Bata --}}
    <section class="stack" id="stGroup" hidden>
        <h2 id="askGroup"></h2>
        <div class="choices">
            <button class="choice" type="button" data-group="matanda">Matanda</button>
            <button class="choice" type="button" data-group="bata">Bata</button>
        </div>
        <p class="msg" id="groupMsg" aria-live="polite"></p>
    </section>

    {{-- Hakbang 3: ikot --}}
    <section class="stack" id="stSpin" hidden>
        <h2 id="hello"></h2>
        <p class="center muted">Pindutin ang Paikutin. Lalabas ang pangalan sa likod ng numerong makukuha mo.</p>
        <div class="wheel-box">
            <div class="pointer" aria-hidden="true"></div>
            <canvas id="wheel" aria-label="Gulong ng mga numero"></canvas>
        </div>
        <button class="primary" type="button" id="spinBtn">Paikutin</button>
        <p class="msg" id="spinMsg" aria-live="polite"></p>

        <div class="wheel-list">
            <h3>Mga nasa gulong</h3>
            <ol id="wheelList"></ol>
        </div>
    </section>
</div>

{{-- Resulta --}}
<div class="overlay" id="resultPopup" role="dialog" aria-modal="true" aria-labelledby="resBig" hidden>
    <div class="popup">
        <div class="small" id="resSmall"></div>
        <div class="big" id="resBig"></div>
        <p class="remember">PAKI TANDAAN OR SCREENSHOT PARA DI MALIMUTAN</p>
        <p class="muted">Naka-save na ang resulta mo.</p>
        <button class="primary" type="button" id="doneBtn">Tapos na</button>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/roulette.js') }}"></script>
@endpush
