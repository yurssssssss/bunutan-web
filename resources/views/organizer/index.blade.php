@extends('layouts.app')

@section('title', 'Organizer · Bunutan')

@section('content')
<div class="wrap wide">
    <div class="topbar">
        <h1>Organizer</h1>
        <form method="POST" action="{{ route('organizer.logout') }}">
            @csrf
            <button class="small-btn ghost" type="submit">Lumabas</button>
        </form>
    </div>

    @if (session('status'))
        <p class="status" role="status">{{ session('status') }}</p>
    @endif
    @if ($errors->any())
        <p class="error" role="alert">{{ $errors->first() }}</p>
    @endif

    {{-- Magdagdag ng pangalan --}}
    <section class="stack">
        <h3>Magdagdag ng pangalan</h3>
        <form class="stack" method="POST" action="{{ route('organizer.participants.store') }}" autocomplete="off">
            @csrf
            <textarea name="names" id="addInput" rows="5" aria-label="Mga pangalan"
                      placeholder="I-type o i-paste ang mga pangalan dito.&#10;Isang pangalan bawat linya, o paghiwalayin ng kuwit.">{{ old('names') }}</textarea>
            <p class="muted" id="addPreview" aria-live="polite"></p>
            <fieldset class="group-pick">
                <legend>Idagdag bilang:</legend>
                @foreach (\App\Models\Participant::GROUPS as $key => $label)
                    <label><input type="radio" name="group" value="{{ $key }}" @checked(old('group', 'matanda') === $key)> {{ $label }}</label>
                @endforeach
            </fieldset>
            <button class="small-btn" type="submit">Idagdag</button>
        </form>
    </section>

    {{-- Listahan bawat grupo --}}
    @foreach ($groups as $group)
        <section class="stack">
            <h3>{{ $group['label'] }} ({{ $group['participants']->count() }})</h3>
            <ul class="plist">
                @forelse ($group['participants'] as $p)
                    <li>
                        <span class="pnum">{{ $p->number }}</span>
                        <span class="pname">{{ $p->name }}</span>
                        <span class="tag">
                            {{ collect([
                                in_array($p->id, $spunIds) ? 'nakapag-ikot' : null,
                                in_array($p->id, $pickedIds) ? 'nabunot na' : null,
                            ])->filter()->implode(' · ') }}
                        </span>
                        <span class="row-actions">
                            <form method="POST" action="{{ route('organizer.participants.group', $p) }}">
                                @csrf @method('PATCH')
                                <button class="remove" type="submit">Ilipat sa {{ $group['key'] === 'matanda' ? 'Bata' : 'Matanda' }}</button>
                            </form>
                            <form method="POST" action="{{ route('organizer.participants.destroy', $p) }}">
                                @csrf @method('DELETE')
                                <button class="remove" type="submit" aria-label="Alisin si {{ $p->name }}">Alisin</button>
                            </form>
                        </span>
                    </li>
                @empty
                    <li class="empty">Wala pang pangalan dito.</li>
                @endforelse
            </ul>
        </section>
    @endforeach

    {{-- Resulta --}}
    <section class="stack">
        <h3>Resulta ({{ $draws->count() }})</h3>
        <details class="reveal-results">
            <summary class="small-btn ghost">Ipakita kung sino ang nakabunot kanino</summary>
            <div class="results">
                @if ($draws->isEmpty())
                    <p class="muted">Wala pang nag-iikot.</p>
                @else
                    <table>
                        <thead><tr><th>Nag-ikot</th><th>Grupo</th><th>Nabunot</th></tr></thead>
                        <tbody>
                            @foreach ($draws as $d)
                                <tr>
                                    <td>{{ $d->spinner_name }}</td>
                                    <td>{{ \App\Models\Participant::GROUPS[$d->group] ?? $d->group }}</td>
                                    <td class="num">Blg. {{ $d->picked_number }} · {{ $d->picked_name }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </details>
    </section>

    {{-- Magsimula ulit --}}
    <section class="stack">
        <details class="confirm">
            <summary class="small-btn ghost">Magsimula ulit</summary>
            <form class="confirm-body" method="POST" action="{{ route('organizer.reset') }}">
                @csrf
                <span>Buburahin ang lahat ng ikot at resulta. Mananatili ang mga pangalan.</span>
                <button class="small-btn danger" type="submit">Oo, magsimula ulit</button>
            </form>
        </details>
    </section>

    <p class="center"><a class="link" href="{{ route('roulette') }}">Pumunta sa bunutan</a></p>
</div>
@endsection

@push('scripts')
<script>
// Preview how a pasted list will be split (the server does the real split).
(function () {
  const input = document.getElementById("addInput"), out = document.getElementById("addPreview");
  const norm = s => s.trim().replace(/\s+/g, " ").toLowerCase();
  function splitNames(text) {
    const seen = new Set();
    return text.split(/[\r\n\t,;]+/)
      .map(s => s.replace(/^\s*(\d+\s*[.):\-]|[-•*·–])\s*/, "").trim().replace(/\s+/g, " ").slice(0, 60))
      .filter(s => s && !/^\d+$/.test(s) && !seen.has(norm(s)) && seen.add(norm(s)));
  }
  function update() {
    const n = splitNames(input.value);
    out.textContent = n.length > 1 ? n.length + " pangalan ang nakita: " + n.join(", ") : "";
  }
  input.addEventListener("input", update);
  update();
})();
</script>
@endpush
