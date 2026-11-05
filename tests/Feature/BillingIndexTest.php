<?php

namespace Tests\Feature;

use App\Models\Billing;
use App\Models\Pricing;
use App\Models\Table;
use App\Models\User;
use App\Services\BillingSessionManager;
use Livewire\Livewire;
use Tests\TestCase;

class BillingIndexTest extends TestCase
{
    protected ?User $kasir = null;
    protected ?User $owner = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kasir = User::role('kasir')->first();
        if (!$this->kasir) {
            $this->kasir = User::factory()->create();
            $this->kasir->assignRole('kasir');
        }

        $this->owner = User::role('owner')->first();
        if (!$this->owner) {
            $this->owner = User::factory()->create();
            $this->owner->assignRole('owner');
        }
    }

    /**
     * Test kasir can view billing index with custom layout title and breadcrumbs.
     */
    public function test_kasir_can_view_billing_index_with_custom_layout_title_and_breadcrumbs(): void
    {
        $response = $this->actingAs($this->kasir)->get(route('kasir.billing.index'));

        $response->assertStatus(200);

        // Layout title injected to navbar
        $response->assertSee('Manajemen Billing', false);
        $response->assertDontSee('<h4 class="mb-0 text-white">Page Title</h4>', false);

        // Breadcrumbs rendered
        $response->assertSee('Monitoring', false);
        $response->assertSee('Billing', false);

        // Main content headers
        $response->assertSee('Daftar Billing', false);
        $response->assertSee('Tambah Billing Manual (Walk-In)', false);
    }

    /**
     * Test owner can view billing index with custom layout title and breadcrumbs.
     */
    public function test_owner_can_view_billing_index_with_custom_layout_title_and_breadcrumbs(): void
    {
        $response = $this->actingAs($this->owner)->get(route('owner.billing.index'));

        $response->assertStatus(200);

        // Layout title injected to navbar
        $response->assertSee('Manajemen Billing', false);

        // Breadcrumbs rendered
        $response->assertSee('Monitoring', false);
        $response->assertSee('Billing', false);

        // Main content headers
        $response->assertSee('Daftar Billing', false);
    }

    /**
     * Test billing index livewire component filters by status and search.
     */
    public function test_billing_index_livewire_component_filters_and_searches(): void
    {
        $table = Table::where('status', 'available')->first() ?? Table::first();
        $table->update(['status' => 'available']);
        $pricing = Pricing::first() ?? Pricing::factory()->create(['type' => 'regular', 'price_per_hour' => 30000]);

        $this->actingAs($this->kasir);

        $manager = app(BillingSessionManager::class);
        $billing = $manager->start([
            'table_id'   => $table->id,
            'guest_name' => 'Guest TDD UniqueName',
            'pricing_id' => $pricing->id,
            'started_by' => $this->kasir->id,
        ]);

        try {
            // Livewire component test
            Livewire::actingAs($this->kasir)
                ->test('pages.billing.index')
                ->assertSee($billing->billing_code)
                ->assertSee('Guest TDD UniqueName')
                // Test filter by active status (should display active session)
                ->set('filterStatus', 'active')
                ->assertSee($billing->billing_code)
                // Test filter by completed status (should exclude active session)
                ->set('filterStatus', 'completed')
                ->assertDontSee($billing->billing_code)
                // Reset filter and search by name
                ->set('filterStatus', '')
                ->set('search', 'Guest TDD UniqueName')
                ->assertSee($billing->billing_code)
                // Search non-existent
                ->set('search', 'NonExistentRandomSearch999XYZ')
                ->assertDontSee($billing->billing_code);
        } finally {
            $billing->payment?->delete();
            $billing->delete();
            $table->update(['status' => 'available', 'device_status' => false]);
        }
    }
}
