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
            ->assertDontSee(route('organizer.login'));
    }

    public function test_check_leaves_own_number_off_the_wheel(): void
    {
        $this->seedNames();

        // "juan" matches "Juan Dela Cruz" (number 1 in Matanda) by first name
        $this->postJson('/check', ['name' => 'juan', 'group' => 'matanda'])
            ->assertOk()->assertJson(['numbers' => [2, 3]]);
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
        $this->assertSame(2, $res->json('number'));
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

    public function test_each_person_spins_only_once_even_in_the_other_group(): void
    {
        $this->seedNames();
        $this->postJson('/spin', ['name' => 'Maria', 'group' => 'matanda'])->assertOk();
        $this->postJson('/spin', ['name' => 'maria', 'group' => 'bata'])
            ->assertStatus(409)->assertJson(['message' => 'maria, nakapag-ikot ka na. Isang beses lang puwedeng mag-ikot ang bawat isa.']);
    }

    public function test_unlisted_name_can_spin_once(): void
    {
        $this->seedNames();
        $this->postJson('/spin', ['name' => 'Tito Boy', 'group' => 'matanda'])->assertOk();
        $this->postJson('/check', ['name' => 'tito  boy', 'group' => 'matanda'])->assertStatus(409);
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

    public function test_start_over_keeps_names(): void
    {
        $this->seedNames();
        $this->postJson('/spin', ['name' => 'Maria', 'group' => 'matanda'])->assertOk();

        $this->withSession(['roulette_organizer' => true])->post('/admin/reset')->assertRedirect();
        $this->assertSame(0, Draw::count());
        $this->assertSame(5, Participant::count());
    }
}
