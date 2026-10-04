<?php

namespace Tests\Feature;

use App\Models\Draw;
use App\Models\Participant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RouletteTest extends TestCase
{
    use RefreshDatabase;

    private function seedNames(): void
    {
        foreach (['Juan Dela Cruz', 'Maria', 'Pedro'] as $n) {
            Participant::create(['name' => $n, 'group' => 'matanda']);
        }
        foreach (['Bea', 'Carlo'] as $n) {
            Participant::create(['name' => $n, 'group' => 'bata']);
        }
    }

    public function test_player_page_loads_in_tagalog(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('Ano ang pangalan mo?')
            ->assertSee('PAKI TANDAAN OR SCREENSHOT PARA DI MALIMUTAN')
            ->assertDontSee('Para sa organizer')
            ->assertDontSee(route('organizer.login'))
            // versioned so phones load the new script after a deploy instead of a cached old one
            ->assertSee('js/roulette.js?v=', false)
            ->assertSee('css/roulette.css?v=', false);
    }

    public function test_player_page_lists_the_names(): void
    {
        $this->seedNames();

        $this->get('/')->assertOk()
            ->assertSeeInOrder(['Ikaw ba ay Matanda o Bata?', 'Listahan ng Matanda', 'Juan Dela Cruz', 'Maria', 'Pedro', 'Listahan ng Bata', 'Bea', 'Carlo']);
    }

    public function test_check_leaves_own_number_off_the_wheel(): void
    {
        $this->seedNames();

        // capital letters and extra spaces don't matter; Juan's wheel holds only Maria and Pedro
        $this->postJson('/check', ['name' => 'juan  dela cruz', 'group' => 'matanda'])
            ->assertOk()->assertExactJson(['name' => 'Juan Dela Cruz', 'numbers' => [1, 2]]);
    }

    public function test_wheel_does_not_reveal_who_was_already_drawn(): void
    {
        $this->seedNames();
        $maria = Participant::where('name', 'Maria')->first();
        Draw::create([
            'spinner_name' => 'Pedro', 'group' => 'matanda', 'spinner_key' => 'p:test',
            'picked_participant_id' => $maria->id, 'picked_name' => 'Maria', 'picked_number' => 1,
        ]);

        // only Pedro is left for Juan: the wheel shows 1 (not Pedro's list number 3) and no names
        $this->postJson('/check', ['name' => 'Juan Dela Cruz', 'group' => 'matanda'])
            ->assertOk()->assertExactJson(['name' => 'Juan Dela Cruz', 'numbers' => [1]])
            ->assertDontSee('Pedro');
    }

    public function test_name_must_be_spelled_as_on_the_list(): void
    {
        $this->seedNames();

        foreach (['Juan', 'Juan Santos', 'Tito Boy'] as $name) {
            $this->postJson('/check', ['name' => $name, 'group' => 'matanda'])
                ->assertStatus(409)->assertJson(['message' => 'Wala sa listahan ng Matanda ang "'.$name.'". Kopyahin ang eksaktong spelling ng pangalan mo mula sa listahan.']);
            $this->postJson('/spin', ['name' => $name, 'group' => 'matanda'])->assertStatus(409);
        }
        $this->assertSame(0, Draw::count());
    }

    public function test_group_must_be_chosen(): void
    {
        $this->seedNames();
        $this->postJson('/check', ['name' => 'Juan'])
            ->assertStatus(422)->assertJson(['message' => 'Pumili: Matanda o Bata.']);
    }

    public function test_bata_only_draws_bata_names(): void
    {
        $this->seedNames();
        $res = $this->postJson('/spin', ['name' => 'Bea', 'group' => 'bata'])->assertOk();
        $this->assertSame('Carlo', $res->json('name'));
        $this->assertSame(1, $res->json('number'));
        $this->assertSame([1], $res->json('numbers'));
        $this->assertSame(1, Draw::first()->picked_number);
    }

    public function test_last_person_to_spin_is_never_left_with_only_their_own_name(): void
    {
        $this->seedNames();
        [$juan, $maria, $pedro] = Participant::where('group', 'matanda')->orderBy('id')->get()->all();
        Draw::create([
            'spinner_name' => $juan->name, 'group' => 'matanda', 'spinner_key' => 'p:'.$juan->id,
            'spinner_participant_id' => $juan->id,
            'picked_participant_id' => $maria->id, 'picked_name' => $maria->name, 'picked_number' => 1,
        ]);

        // Maria could draw Juan or Pedro; drawing Juan would leave Pedro with only himself
        $this->postJson('/spin', ['name' => 'Maria', 'group' => 'matanda'])
            ->assertOk()->assertJson(['name' => 'Pedro']);
        $this->postJson('/spin', ['name' => 'Pedro', 'group' => 'matanda'])
            ->assertOk()->assertJson(['name' => 'Juan Dela Cruz']);
    }

    public function test_never_draws_self_and_each_name_only_once(): void
    {
        $this->seedNames();
        $got = [];
        foreach (['Juan Dela Cruz', 'Maria', 'Pedro'] as $spinner) {
            $name = $this->postJson('/spin', ['name' => $spinner, 'group' => 'matanda'])->assertOk()->json('name');
            $this->assertNotSame($spinner, $name);
            $got[] = $name;
        }
        $this->assertCount(3, array_unique($got));
        $this->assertSame(3, Draw::count());
    }

    public function test_name_must_be_in_the_chosen_group(): void
    {
        $this->seedNames();

        // Maria is on the Matanda list, so she can't spin as Bata
        foreach (['/check', '/spin'] as $url) {
            $this->postJson($url, ['name' => 'maria', 'group' => 'bata'])
                ->assertStatus(409)->assertJson(['message' => 'Nasa listahan ng Matanda ang "Maria". Bumalik at piliin ang Matanda.']);
        }
        $this->assertSame(0, Draw::count());
    }

    public function test_each_person_spins_only_once(): void
    {
        $this->seedNames();
        $this->postJson('/spin', ['name' => 'Maria', 'group' => 'matanda'])->assertOk();
        $this->postJson('/spin', ['name' => 'maria', 'group' => 'matanda'])
            ->assertStatus(409)->assertJson(['message' => 'Maria, nakapag-ikot ka na. Isang beses lang puwedeng mag-ikot ang bawat isa.']);
    }

    public function test_organizer_pages_need_the_password(): void
    {
        config(['roulette.organizer_password' => 'test-secret']);

        $this->get('/admin')->assertRedirect('/admin/login');
        $this->post('/admin/login', ['password' => 'wrong'])->assertSessionHasErrors('password');
        $this->post('/admin/login', ['password' => 'test-secret'])->assertRedirect('/admin');
        $this->get('/admin')->assertOk()->assertSee('Magdagdag ng pangalan');
    }

    public function test_organizer_pastes_a_list_and_it_is_split(): void
    {
        $this->withSession(['roulette_organizer' => true])
            ->post('/admin/participants', [
                'names' => "1. Juan Dela Cruz\n2) Maria\n- Tita Bing\n• Pedro, Ana; Carlo\n\njuan dela cruz",
                'group' => 'bata',
            ])->assertSessionHas('status', '6 pangalan ang naidagdag sa Bata.');

        $this->assertSame(
            ['Juan Dela Cruz', 'Maria', 'Tita Bing', 'Pedro', 'Ana', 'Carlo'],
            Participant::orderBy('id')->pluck('name')->all()
        );
        $this->assertSame(6, Participant::where('group', 'bata')->count());
    }

    public function test_organizer_fixes_the_spelling_of_a_name(): void
    {
        $this->seedNames();
        $maria = Participant::where('name', 'Maria')->first();
        $this->postJson('/spin', ['name' => 'Pedro', 'group' => 'matanda'])->assertOk();
        $this->postJson('/spin', ['name' => 'Maria', 'group' => 'matanda'])->assertOk();

        $this->withSession(['roulette_organizer' => true])
            ->patch('/admin/participants/'.$maria->id, ['name' => '  Maria   Clara '])
            ->assertSessionHas('status', 'Napalitan ang "Maria" ng "Maria Clara".');

        $this->assertSame('Maria Clara', $maria->fresh()->name);
        // her saved result shows the new spelling too
        $this->assertSame('Maria Clara', Draw::where('spinner_participant_id', $maria->id)->value('spinner_name'));
        $this->postJson('/check', ['name' => 'Maria Clara', 'group' => 'matanda'])->assertStatus(409);
    }

    public function test_organizer_cannot_rename_to_a_name_already_on_the_list(): void
    {
        $this->seedNames();
        $pedro = Participant::where('name', 'Pedro')->first();

        $this->withSession(['roulette_organizer' => true])
            ->patch('/admin/participants/'.$pedro->id, ['name' => 'maria'])
            ->assertSessionHasErrors(['name' => 'Nasa listahan na ang "maria".']);
        $this->assertSame('Pedro', $pedro->fresh()->name);
    }

    public function test_renaming_needs_the_organizer_password(): void
    {
        $this->seedNames();
        $pedro = Participant::where('name', 'Pedro')->first();

        $this->patch('/admin/participants/'.$pedro->id, ['name' => 'Hacker'])->assertRedirect('/admin/login');
        $this->assertSame('Pedro', $pedro->fresh()->name);
    }

    public function test_start_over_keeps_names(): void
    {
        $this->seedNames();
        $this->postJson('/spin', ['name' => 'Maria', 'group' => 'matanda'])->assertOk();

        $this->withSession(['roulette_organizer' => true])->post('/admin/reset')->assertRedirect();
        $this->assertSame(0, Draw::count());
        $this->assertSame(5, Participant::count());
    }
}
