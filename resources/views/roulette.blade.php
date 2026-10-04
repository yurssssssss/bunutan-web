@extends('layouts.app')

@section('content')
<div class="wrap" id="app"
     data-check-url="{{ route('roulette.check') }}"
     data-spin-url="{{ route('roulette.spin') }}">
    <div class="player-bar">
        <button class="back-btn" type="button" id="backBtn" hidden>
            <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M15 18l-6-6 6-6" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Bumalik
        </button>
    </div>
    <h1>Exchange Gift Bunutan</h1>

    {{-- Hakbang 1: Matanda o Bata --}}
    <section class="stack" id="stGroup">
        <h2>Ikaw ba ay Matanda o Bata?</h2>
        <div class="choices">
            @foreach ($groups as $group)
                <button class="choice" type="button" data-group="{{ $group['key'] }}">{{ $group['label'] }}</button>
            @endforeach
        </div>
    </section>

    {{-- Hakbang 2: pangalan, mula sa listahan ng napiling grupo --}}
    <section class="stack" id="stName" hidden>
        <h2 id="askName">Ano ang pangalan mo?</h2>
        <form class="stack" id="nameForm" autocomplete="off">
            <input id="nameInput" type="text" placeholder="I-type ang pangalan mo o palayaw(nickname)" aria-label="Pangalan mo" maxlength="60">
            <div class="btn-row">
                <button class="secondary" type="button" id="clearBtn" disabled>Burahin</button>
                <button class="primary" type="submit" id="nameBtn">Susunod</button>
            </div>
        </form>
        <p class="msg" id="nameMsg" aria-live="polite"></p>

        {{-- Listahan: pindutin ang pangalan para makopya ang tamang spelling --}}
        <div class="namelist">
            <p class="center muted">Hanapin ang pangalan mo sa listahan at pindutin ang <strong>Kopyahin</strong> para makopya ang tamang spelling.</p>
            @foreach ($groups as $group)
                <div class="namelist-group" data-group="{{ $group['key'] }}" hidden>
                    <h3>Listahan ng {{ $group['label'] }}</h3>
                    @if ($group['names']->isEmpty())
                        <p class="muted">Wala pang pangalan.</p>
                    @else
                        <ul>
                            @foreach ($group['names'] as $name)
                                <li class="name-row">
                                    <span class="name-text">{{ $name }}</span>
                                    <button type="button" class="name-pick" data-name="{{ $name }}" aria-label="Kopyahin ang {{ $name }}">Kopyahin</button>
                                </li>
                            @endforeach
                        </ul>
                        <nav class="pager" aria-label="Mga pahina ng listahan" hidden>
                            <button type="button" class="pager-btn" data-step="-1">‹ Nakaraan</button>
                            <span class="pager-info" aria-live="polite"></span>
                            <button type="button" class="pager-btn" data-step="1">Kasunod ›</button>
                        </nav>
                    @endif
                    <p class="center muted no-match" hidden>Walang tugmang pangalan sa listahan.</p>
                </div>
            @endforeach
        </div>
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
<script src="{{ asset('js/roulette.js') }}?v={{ substr(md5_file(public_path('js/roulette.js')), 0, 10) }}"></script>
@endpush
