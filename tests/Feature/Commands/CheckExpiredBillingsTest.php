<?php

namespace Tests\Feature\Commands;

use App\Events\BillingTimeExpired;
use App\Events\BillingUpdated;
use App\Events\TableStatusUpdated;
use App\Models\Billing;
use App\Models\Table;
use App\Models\User;
use App\Notifications\BillingTimeExpiredNotification;
use App\Services\BillingSessionManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CheckExpiredBillingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'owner']);
        Role::firstOrCreate(['name' => 'kasir']);

        $this->adminUser = User::factory()->create(['phone' => '08999999999']);
    }

    public function test_turns_off_device_and_notifies_when_billing_expired(): void
    {
        Event::fake([
            TableStatusUpdated::class,
            BillingUpdated::class,
            BillingTimeExpired::class,
        ]);
        Notification::fake();

        $owner = User::factory()->create(['phone' => '08111111111']);
        $owner->assignRole('owner');

        $kasir = User::factory()->create(['phone' => '08222222222']);
        $kasir->assignRole('kasir');

        $table = Table::create([
            'table_number'  => 'T1',
            'name'          => 'Meja 1',
            'description'   => 'Test Desc',
            'status'        => 'occupied',
            'device_status' => true,
            'is_active'     => true,
        ]);

        $billing = Billing::create([
            'billing_code'     => 'BIL-TEST-001',
            'table_id'         => $table->id,
            'guest_name'       => 'John Doe',
            'started_at'       => now()->subHours(2),
            'ended_at'         => now()->subHours(2),
            'scheduled_end_at' => now()->subMinute(),
            'status'           => 'active',
            'base_price'       => 50000,
            'grand_total'      => 50000,
            'started_by'       => $this->adminUser->id,
        ]);

        $this->artisan('billing:check-expired')
            ->assertSuccessful();

        // Table device_status should be turned off
        $table->refresh();
        $this->assertFalse((bool)$table->device_status);
        $this->assertEquals('occupied', $table->status);

        // Billing status should remain active (not auto-completed)
        $billing->refresh();
        $this->assertEquals('active', $billing->status);

        // Events broadcasted
        Event::assertDispatched(TableStatusUpdated::class);
        Event::assertDispatched(BillingUpdated::class);
        Event::assertDispatched(BillingTimeExpired::class);

        // Notifications sent to owner and kasir
        Notification::assertSentTo(
            [$owner, $kasir],
            BillingTimeExpiredNotification::class
        );
    }

    public function test_does_not_affect_active_billing_not_yet_expired(): void
    {
        Event::fake();
        Notification::fake();

        $table = Table::create([
            'table_number'  => 'T2',
            'name'          => 'Meja 2',
            'description'   => 'Test Desc',
            'status'        => 'occupied',
            'device_status' => true,
            'is_active'     => true,
        ]);

        $billing = Billing::create([
            'billing_code'     => 'BIL-TEST-002',
            'table_id'         => $table->id,
            'guest_name'       => 'Jane Doe',
            'started_at'       => now(),
            'ended_at'         => now(),
            'scheduled_end_at' => now()->addHour(),
            'status'           => 'active',
            'base_price'       => 50000,
            'grand_total'      => 50000,
            'started_by'       => $this->adminUser->id,
        ]);

        $this->artisan('billing:check-expired')
            ->assertSuccessful();

        $table->refresh();
        $this->assertTrue((bool)$table->device_status);

        Event::assertNotDispatched(BillingTimeExpired::class);
        Notification::assertNothingSent();
    }

    public function test_does_not_affect_loss_billing_without_scheduled_end(): void
    {
        Event::fake();
        Notification::fake();

        $table = Table::create([
            'table_number'  => 'T3',
            'name'          => 'Meja 3',
            'description'   => 'Test Desc',
            'status'        => 'occupied',
            'device_status' => true,
            'is_active'     => true,
        ]);

        Billing::create([
            'billing_code'     => 'BIL-TEST-003',
            'table_id'         => $table->id,
            'guest_name'       => 'Open Player',
            'started_at'       => now()->subHours(3),
            'ended_at'         => now()->subHours(3),
            'scheduled_end_at' => null,
            'status'           => 'active',
            'base_price'       => 0,
            'grand_total'      => 0,
            'started_by'       => $this->adminUser->id,
        ]);

        $this->artisan('billing:check-expired')
            ->assertSuccessful();

        $table->refresh();
        $this->assertTrue((bool)$table->device_status);

        Event::assertNotDispatched(BillingTimeExpired::class);
        Notification::assertNothingSent();
    }

    public function test_extending_expired_billing_turns_light_back_on(): void
    {
        Event::fake([
            TableStatusUpdated::class,
            BillingUpdated::class,
        ]);

        $user = User::factory()->create(['phone' => '08333333333']);
        $this->actingAs($user);

        $table = Table::create([
            'table_number'  => 'T4',
            'name'          => 'Meja 4',
            'description'   => 'Test Desc',
            'status'        => 'occupied',
            'device_status' => false, // turned off because time expired
            'is_active'     => true,
        ]);

        $billing = Billing::create([
            'billing_code'     => 'BIL-TEST-004',
            'table_id'         => $table->id,
            'guest_name'       => 'Player 4',
            'started_at'       => now()->subHours(2),
            'ended_at'         => now()->subHours(2),
            'scheduled_end_at' => now()->subMinute(),
            'status'           => 'active',
            'base_price'       => 50000,
            'grand_total'      => 50000,
            'started_by'       => $this->adminUser->id,
        ]);

        $manager = new BillingSessionManager();
        $manager->extend($billing, 1);

        $table->refresh();
        $this->assertTrue((bool)$table->device_status);
        Event::assertDispatched(TableStatusUpdated::class);
    }
}
