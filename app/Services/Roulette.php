<?php

namespace App\Services;

use App\Models\Draw;
use App\Models\Participant;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Roulette
{
    /** Lowercase, trimmed, single-spaced, for comparing names. */
    public function normalize(string $name): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $name)));
    }

    /** Clean a typed name for display and storage. */
    public function clean(string $name): string
    {
        return mb_substr(trim(preg_replace('/\s+/u', ' ', $name)), 0, 60);
    }

    /**
     * Split a pasted list into names. New lines, commas, semicolons and tabs
     * separate names; list markers like "1.", "2)", "-", "•" are dropped;
     * repeats within the paste are removed. Spaces never split, so
     * "Juan Dela Cruz" stays one name.
     *
     * @return list<string>
     */
    public function splitNames(string $text): array
    {
        $names = [];
        foreach (preg_split('/[\r\n\t,;]+/u', $text) as $part) {
            $part = preg_replace('/^\s*(\d+\s*[.):\-]|[-•*·–])\s*/u', '', $part);
            $part = $this->clean($part);
            if ($part === '' || preg_match('/^\d+$/', $part)) {
                continue;
            }
            $names[$this->normalize($part)] ??= $part;
        }

        return array_values($names);
    }

    /**
     * Participants in wheel order. Each group has its own wheel, so a
     * participant's number is their position within their group.
     */
    public function participants(?string $group = null): Collection
    {
        $all = Participant::orderBy('id')->get();
        $counters = [];
        foreach ($all as $p) {
            $p->number = $counters[$p->group] = ($counters[$p->group] ?? 0) + 1;
        }

        return $group ? $all->where('group', $group)->values() : $all;
    }

    /**
     * Find the participant a typed name refers to. The spelling must match the
     * list; only capital letters and extra spaces are ignored.
     */
    public function match(string $typed, Collection $participants): ?Participant
    {
        $typed = $this->normalize($typed);

        return $participants->first(fn ($p) => $this->normalize($p->name) === $typed);
    }

    /**
     * The participant a typed name refers to within the chosen group, or a
     * message telling the player to copy their name from that group's list.
     *
     * @throws RuntimeException
     */
    public function findOnList(string $typed, string $group, Collection $participants): Participant
    {
        $me = $this->match($typed, $participants);
        $label = Participant::GROUPS[$group];

        if (! $me) {
            throw new RuntimeException('Wala sa listahan ng '.$label.' ang "'.$typed.'". Kopyahin ang eksaktong spelling ng pangalan mo mula sa listahan.');
        }
        if ($me->group !== $group) {
            throw new RuntimeException('Nasa listahan ng '.Participant::GROUPS[$me->group].' ang "'.$me->name.'". Bumalik at piliin ang '.Participant::GROUPS[$me->group].'.');
        }

        return $me;
    }

    public function spinnerKey(Participant $me): string
    {
        return 'p:'.$me->id;
    }

    /**
     * Number and name of each slice on a wheel, in wheel order.
     *
     * @return list<array{number:int, name:string}>
     */
    public function entries(Collection $available): array
    {
        return $available->map(fn ($p) => ['number' => $p->number, 'name' => $p->name])->values()->all();
    }

    /** Numbers still on a group's wheel for this person: not picked yet and not their own. */
    public function availableFor(Participant $me, Collection $groupParticipants): Collection
    {
        $picked = Draw::whereNotNull('picked_participant_id')->pluck('picked_participant_id')->all();

        return $groupParticipants
            ->reject(fn ($p) => in_array($p->id, $picked) || $p->id === $me->id)
            ->values();
    }

    public function hasSpun(string $key): bool
    {
        return Draw::where('spinner_key', $key)->exists();
    }

    public function alreadySpunMessage(string $name): string
    {
        return $name.', nakapag-ikot ka na. Isang beses lang puwedeng mag-ikot ang bawat isa.';
    }

    /**
     * Check a typed name before showing the wheel.
     *
     * @return array{name:string, numbers:list<int>, entries:list<array{number:int, name:string}>}
     *
     * @throws RuntimeException with a message meant for the player
     */
    public function check(string $typed, string $group): array
    {
        $typed = $this->clean($typed);
        $all = $this->participants();
        $groupParticipants = $all->where('group', $group)->values();

        if ($groupParticipants->isEmpty()) {
            throw new RuntimeException('Wala pang pangalan para sa '.Participant::GROUPS[$group].'. Pakibalikan mamaya.');
        }

        $me = $this->findOnList($typed, $group, $all);
        if ($this->hasSpun($this->spinnerKey($me))) {
            throw new RuntimeException($this->alreadySpunMessage($me->name));
        }

        $available = $this->availableFor($me, $groupParticipants);
        if ($available->isEmpty()) {
            throw new RuntimeException('Wala nang natitirang pangalan na mabubunot.');
        }

        return [
            'name' => $me->name,
            'numbers' => $available->pluck('number')->all(),
            'entries' => $this->entries($available),
        ];
    }

    /**
     * Pick a random name from the group's wheel and save it.
     *
     * @return array{number:int, name:string, numbers:list<int>, entries:list<array{number:int, name:string}>}
     *
     * @throws RuntimeException with a message meant for the player
     */
    public function spin(string $typed, string $group): array
    {
        $typed = $this->clean($typed);

        try {
            return DB::transaction(function () use ($typed, $group) {
                // lock the list so two spins at the same moment run one after the other
                Participant::orderBy('id')->lockForUpdate()->get();
                $all = $this->participants();
                $me = $this->findOnList($typed, $group, $all);
                $key = $this->spinnerKey($me);

                if ($this->hasSpun($key)) {
                    throw new RuntimeException($this->alreadySpunMessage($me->name));
                }

                $available = $this->availableFor($me, $all->where('group', $group)->values());
                if ($available->isEmpty()) {
                    throw new RuntimeException('Wala nang natitirang pangalan na mabubunot.');
                }

                $pick = $available->random();
                Draw::create([
                    'spinner_name' => $me->name,
                    'group' => $group,
                    'spinner_key' => $key,
                    'spinner_participant_id' => $me->id,
                    'picked_participant_id' => $pick->id,
                    'picked_name' => $pick->name,
                    'picked_number' => $pick->number,
                ]);

                return [
                    'number' => $pick->number,
                    'name' => $pick->name,
                    'numbers' => $available->pluck('number')->all(),
                    'entries' => $this->entries($available),
                ];
            });
        } catch (UniqueConstraintViolationException) {
            // the database rules caught a double spin or a name picked twice
            throw new RuntimeException('May kasabay kang nag-ikot. Pakipindot ulit ang Paikutin.');
        }
    }
}
