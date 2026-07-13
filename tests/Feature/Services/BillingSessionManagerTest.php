<?php

namespace Tests\Feature\Services;

use App\Models\User;
use App\Models\Table;
use App\Models\Pricing;
use App\Models\Package;
use App\Models\Addon;
use App\Models\Booking;
use App\Models\Billing;
use App\Services\BillingSessionManager;
use App\Exceptions\DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class BillingSessionManagerTest extends TestCase
{
    use RefreshDatabase;

    protected BillingSessionManager $manager;
    protected User $user;
    protected Table $table;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = new BillingSessionManager();

        // Create a test user
        $this->user = User::factory()->create(['phone' => '08123456789']);
        $this->actingAs($this->user);

        // Create a test table
        $this->table = Table::create([
            'table_number' => 'T99',
            'name'         => 'Table 99',
            'description'  => 'Test Table',
            'status'       => 'available',
            'is_active'    => 1,
        ]);
    }

    public function test_can_start_walk_in_billing_session(): void
    {
        Event::fake([
            \App\Events\TableStatusUpdated::class,
            \App\Events\BillingUpdated::class
        ]);

        $billing = $this->manager->start([
            'guest_name' => 'Alice',
            'table_id'   => $this->table->id,
        ]);

        $this->assertDatabaseHas('billings', [
            'id'         => $billing->id,
            'guest_name' => 'Alice',
            'table_id'   => $this->table->id,
            'status'     => 'active',
        ]);

        $this->table->refresh();
        $this->assertEquals('occupied', $this->table->status);
        $this->assertTrue((bool)$this->table->device_status);
    }

    public function test_cannot_start_on_occupied_table(): void
    {
        $this->table->update(['status' => 'occupied']);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Meja tidak tersedia lagi');

        $this->manager->start([
            'guest_name' => 'Bob',
            'table_id'   => $this->table->id,
        ]);
    }

    public function test_can_start_session_from_booking(): void
    {
        Event::fake([
            \App\Events\TableStatusUpdated::class,
            \App\Events\BillingUpdated::class
        ]);

        $booking = Booking::create([
            'guest_name'      => 'Booking customer',
            'table_id'        => $this->table->id,
            'scheduled_date'  => now()->format('Y-m-d'),
            'scheduled_start' => '10:00:00',
            'scheduled_end'   => '12:00:00',
            'status'          => 'confirmed',
            'confirmed_by'    => $this->user->id,
            'confirmed_at'    => now(),
        ]);

        $billing = $this->manager->start([
            'booking_id' => $booking->id,
        ]);

        $this->assertDatabaseHas('billings', [
            'id'         => $billing->id,
            'booking_id' => $booking->id,
            'guest_name' => 'Booking customer',
            'table_id'   => $this->table->id,
            'status'     => 'active',
        ]);

        $this->table->refresh();
        $this->assertEquals('occupied', $this->table->status);
    }

    public function test_can_extend_active_billing_session(): void
    {
        Event::fake([
            \App\Events\TableStatusUpdated::class,
            \App\Events\BillingUpdated::class
        ]);

        $pricing = Pricing::create([
            'name'           => 'Standard Rate',
            'price_per_hour' => 50000,
            'is_active'      => 1,
            'created_by'     => $this->user->id,
        ]);

        $billing = Billing::create([
            'guest_name'       => 'Charlie',
            'table_id'         => $this->table->id,
            'pricing_id'       => $pricing->id,
            'started_at'       => now()->subHour(),
            'ended_at'         => now()->subHour(),
            'scheduled_end_at' => now(),
            'status'           => 'active',
            'started_by'       => $this->user->id,
        ]);

        $this->manager->extend($billing, 2);

        $this->assertEquals(now()->addHours(2)->format('Y-m-d H:i'), $billing->fresh()->scheduled_end_at->format('Y-m-d H:i'));

        $this->assertDatabaseHas('billing_time_extensions', [
            'billing_id'           => $billing->id,
            'added_hours'          => 2,
            'price_per_hour'       => 50000,
            'total_price'          => 100000,
            'extended_by'          => $this->user->id,
            'new_scheduled_end_at' => $billing->fresh()->scheduled_end_at,
        ]);
    }

    public function test_cannot_extend_session_if_booking_conflicts(): void
    {
        Event::fake([
            \App\Events\TableStatusUpdated::class,
            \App\Events\BillingUpdated::class
        ]);

        $billing = Billing::create([
            'guest_name'       => 'Dave',
            'table_id'         => $this->table->id,
            'started_at'       => now()->subHour(),
            'ended_at'         => now()->subHour(),
            'scheduled_end_at' => now()->addHour(),
            'status'           => 'active',
            'started_by'       => $this->user->id,
        ]);

        // Create a booking that overlaps with extension (e.g. starting 2 hours from now)
        Booking::create([
            'guest_name'      => 'Booking User',
            'table_id'        => $this->table->id,
            'scheduled_date'  => now()->format('Y-m-d'),
            'scheduled_start' => now()->addHours(2)->format('H:i:s'),
            'scheduled_end'   => now()->addHours(4)->format('H:i:s'),
            'status'          => 'confirmed',
            'confirmed_by'    => $this->user->id,
            'confirmed_at'    => now(),
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Ada booking lain yang menempati meja ini');

        // Trying to extend by 2 hours (making new end at now + 3 hours, which overlaps booking starting at now + 2 hours)
        $this->manager->extend($billing, 2);
    }

    public function test_can_finish_billing_session(): void
    {
        Event::fake([
            \App\Events\TableStatusUpdated::class,
            \App\Events\BillingUpdated::class
        ]);

        $pricing = Pricing::create([
            'name'           => 'Standard Rate',
            'price_per_hour' => 40000,
            'is_active'      => 1,
            'created_by'     => $this->user->id,
        ]);

        $this->table->update(['status' => 'occupied', 'device_status' => true]);

        $billing = Billing::create([
            'guest_name'       => 'Eve',
            'table_id'         => $this->table->id,
            'pricing_id'       => $pricing->id,
            'started_at'       => now()->subHours(2), // 2 hours ago
            'ended_at'         => now()->subHours(2),
            'scheduled_end_at' => now()->addHour(),
            'status'           => 'active',
            'started_by'       => $this->user->id,
        ]);

        $this->manager->finish($billing, 'cash', 100000);

        $billing->refresh();
        $this->assertEquals('completed', $billing->status);
        $this->assertEquals(80000, (float)$billing->grand_total);

        $this->table->refresh();
        $this->assertEquals('available', $this->table->status);
        $this->assertFalse((bool)$this->table->device_status);

        $this->assertDatabaseHas('payments', [
            'billing_id'    => $billing->id,
            'amount'        => 80000,
            'amount_paid'   => 100000,
            'change_amount' => 20000,
            'method'        => 'cash',
            'status'        => 'paid',
        ]);
    }
}
