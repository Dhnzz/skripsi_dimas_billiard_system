<?php

namespace Tests\Feature;

use App\Models\Table;
use App\Models\User;
use Tests\TestCase;

class LoginAndDashboardTest extends TestCase
{
    protected ?User $owner = null;
    protected ?User $kasir = null;
    protected ?User $member = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::role('owner')->first();
        if (!$this->owner) {
            $this->owner = User::factory()->create([
                'email' => 'test.owner@swiss.id',
                'password' => bcrypt('password123'),
            ]);
            $this->owner->assignRole('owner');
        }

        $this->kasir = User::role('kasir')->first();
        if (!$this->kasir) {
            $this->kasir = User::factory()->create([
                'email' => 'test.kasir@swiss.id',
                'password' => bcrypt('password123'),
            ]);
            $this->kasir->assignRole('kasir');
        }

        $this->member = User::role('member')->first();
        if (!$this->member) {
            $this->member = User::factory()->create([
                'email' => 'test.member@swiss.id',
                'password' => bcrypt('password123'),
            ]);
            $this->member->assignRole('member');
        }
    }

    /**
     * Test login screen renders successfully.
     */
    public function test_login_screen_renders_successfully(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Swiss 18 Pool');
    }

    /**
     * Test owner login redirects directly to owner dashboard.
     */
    public function test_owner_login_redirects_to_owner_dashboard(): void
    {
        $response = $this->post('/login', [
            'email' => $this->owner->email,
            'password' => 'owner123',
        ]);

        $this->assertAuthenticatedAs($this->owner);
        $response->assertRedirect(route('owner.dashboard'));
    }

    /**
     * Test kasir login redirects directly to kasir dashboard.
     */
    public function test_kasir_login_redirects_to_kasir_dashboard(): void
    {
        $response = $this->post('/login', [
            'email' => $this->kasir->email,
            'password' => 'kasir123',
        ]);

        $this->assertAuthenticatedAs($this->kasir);
        $response->assertRedirect(route('kasir.dashboard'));
    }

    /**
     * Test invalid login credentials keeps user as guest and returns error.
     */
    public function test_invalid_login_credentials_returns_error(): void
    {
        $response = $this->post('/login', [
            'email' => $this->owner->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    /**
     * Test /dashboard route exists and redirects owner to owner dashboard.
     */
    public function test_dashboard_route_redirects_owner_to_owner_dashboard(): void
    {
        $response = $this->actingAs($this->owner)->get(route('dashboard'));

        $response->assertRedirect(route('owner.dashboard'));
    }

    /**
     * Test /dashboard route exists and redirects kasir to kasir dashboard.
     */
    public function test_dashboard_route_redirects_kasir_to_kasir_dashboard(): void
    {
        $response = $this->actingAs($this->kasir)->get(route('dashboard'));

        $response->assertRedirect(route('kasir.dashboard'));
    }

    /**
     * Test owner can view owner dashboard without error (200 OK).
     */
    public function test_owner_can_view_owner_dashboard_with_statistics(): void
    {
        $response = $this->actingAs($this->owner)->get(route('owner.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Dashboard Owner');
    }

    /**
     * Test kasir can view kasir dashboard without error (200 OK).
     */
    public function test_kasir_can_view_kasir_dashboard_with_data(): void
    {
        $response = $this->actingAs($this->kasir)->get(route('kasir.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Dashboard Kasir');
    }

    /**
     * Test livewire cache and compiled directories are writable.
     */
    public function test_livewire_cache_directories_are_writable(): void
    {
        $livewireCache = storage_path('framework/views/livewire');
        $this->assertTrue(is_dir($livewireCache), "Directory {$livewireCache} must exist");
        $this->assertTrue(is_writable($livewireCache), "Directory {$livewireCache} must be writable");

        foreach (['classes', 'views', 'scripts'] as $sub) {
            $subPath = $livewireCache . '/' . $sub;
            if (is_dir($subPath)) {
                $this->assertTrue(is_writable($subPath), "Subdirectory {$subPath} must be writable");
            }
        }
    }
}
