<?php

namespace App\Http\Controllers;

use App\Models\Draw;
use App\Models\Participant;
use App\Services\Roulette;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrganizerController extends Controller
{
    public function __construct(private Roulette $roulette) {}

    public function showLogin(): View|RedirectResponse
    {
        if (session('roulette_organizer')) {
            return redirect()->route('organizer.index');
        }

        return view('organizer.login', ['configured' => filled(config('roulette.organizer_password'))]);
    }

    public function login(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'string']], [
            'password.required' => 'Pakisulat ang password.',
        ]);
        $expected = (string) config('roulette.organizer_password');

        if ($expected === '' || ! hash_equals($expected, (string) $request->input('password'))) {
            return back()->withErrors(['password' => 'Mali ang password.']);
        }

        $request->session()->regenerate();
        $request->session()->put('roulette_organizer', true);

        return redirect()->route('organizer.index');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('roulette_organizer');
        $request->session()->regenerate();

        return redirect()->route('roulette');
    }

    public function index(): View
    {
        $draws = Draw::orderBy('created_at')->orderBy('id')->get();
        $all = $this->roulette->participants();

        return view('organizer.index', [
            'groups' => collect(Participant::GROUPS)->map(fn ($label, $key) => [
                'key' => $key,
                'label' => $label,
                'participants' => $all->where('group', $key)->values(),
            ])->values(),
            'draws' => $draws,
            'spunIds' => $draws->pluck('spinner_participant_id')->filter()->all(),
            'pickedIds' => $draws->pluck('picked_participant_id')->filter()->all(),
        ]);
    }

    /** Add one name or a pasted list to a group; the list is split automatically. */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'names' => ['required', 'string', 'max:20000'],
            'group' => ['required', Rule::in(array_keys(Participant::GROUPS))],
        ], [
            'names.required' => 'Mag-type o mag-paste ng kahit isang pangalan.',
            'group.required' => 'Pumili: Matanda o Bata.',
            'group.in' => 'Pumili: Matanda o Bata.',
        ]);

        $existing = Participant::pluck('name')->map(fn ($n) => $this->roulette->normalize($n))->all();
        $added = 0;
        $dupes = [];
        foreach ($this->roulette->splitNames($request->input('names')) as $name) {
            if (in_array($this->roulette->normalize($name), $existing, true)) {
                $dupes[] = $name;

                continue;
            }
            Participant::create(['name' => $name, 'group' => $request->input('group')]);
            $existing[] = $this->roulette->normalize($name);
            $added++;
        }

        $label = Participant::GROUPS[$request->input('group')];
        $msg = $added ? $added.' pangalan ang naidagdag sa '.$label.'.' : 'Walang bagong pangalan na naidagdag.';
        if ($dupes) {
            $msg .= ' Nasa listahan na: '.implode(', ', $dupes);
        }

        return back()->with('status', $msg);
    }

    /** Fix the spelling of a name. Saved results show the new spelling too. */
    public function update(Request $request, Participant $participant): RedirectResponse
    {
        $request->validate(['name' => ['required', 'string', 'max:60']], [
            'name.required' => 'Hindi puwedeng walang pangalan.',
            'name.max' => 'Masyadong mahaba ang pangalan.',
        ]);
        $name = $this->roulette->clean($request->input('name'));
        $old = $participant->name;

        $taken = Participant::whereKeyNot($participant->id)->pluck('name')
            ->contains(fn ($n) => $this->roulette->normalize($n) === $this->roulette->normalize($name));
        if ($name === '' || $taken) {
            return back()->withErrors(['name' => $name === '' ? 'Hindi puwedeng walang pangalan.' : 'Nasa listahan na ang "'.$name.'".']);
        }

        $participant->update(['name' => $name]);
        Draw::where('picked_participant_id', $participant->id)->update(['picked_name' => $name]);
        Draw::where('spinner_participant_id', $participant->id)->update(['spinner_name' => $name]);

        return back()->with('status', 'Napalitan ang "'.$old.'" ng "'.$name.'".');
    }

    /** Move a name to the other group. */
    public function switchGroup(Participant $participant): RedirectResponse
    {
        $participant->update(['group' => $participant->group === 'matanda' ? 'bata' : 'matanda']);

        return back()->with('status', 'Nailipat si '.$participant->name.' sa '.Participant::GROUPS[$participant->group].'.');
    }

    public function destroy(Participant $participant): RedirectResponse
    {
        $participant->delete();

        return back()->with('status', 'Inalis si '.$participant->name.'.');
    }

    /** Erase every spin and result. The names stay. */
    public function reset(): RedirectResponse
    {
        Draw::query()->delete();

        return back()->with('status', 'Nagsimula ulit. Puwede nang mag-ikot ulit ang lahat.');
    }
}
