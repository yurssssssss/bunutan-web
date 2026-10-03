<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use App\Services\Roulette;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class RouletteController extends Controller
{
    public function __construct(private Roulette $roulette) {}

    /** The player screen: name (copied from the list) → Matanda or Bata → spin → result. */
    public function index(): View
    {
        $all = $this->roulette->participants();

        return view('roulette', [
            'groups' => collect(Participant::GROUPS)->map(fn ($label, $key) => [
                'label' => $label,
                'names' => $all->where('group', $key)->pluck('name')->values(),
            ])->values(),
        ]);
    }

    /** Check the typed name and return the numbers on that person's wheel. */
    public function check(Request $request): JsonResponse
    {
        $data = $this->validatePlayer($request);

        try {
            return response()->json($this->roulette->check($data['name'], $data['group']));
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }
    }

    /** Pick and save a name, then return it so the wheel can land on it. */
    public function spin(Request $request): JsonResponse
    {
        $data = $this->validatePlayer($request);

        try {
            return response()->json($this->roulette->spin($data['name'], $data['group']));
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }
    }

    private function validatePlayer(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'group' => ['required', Rule::in(array_keys(Participant::GROUPS))],
        ], [
            'name.required' => 'Pakisulat ang pangalan mo.',
            'name.max' => 'Masyadong mahaba ang pangalan.',
            'group.required' => 'Pumili: Matanda o Bata.',
            'group.in' => 'Pumili: Matanda o Bata.',
        ]);
    }
}
