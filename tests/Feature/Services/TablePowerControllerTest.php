<?php

namespace Tests\Feature\Services;

use App\Events\TableStatusUpdated;
use App\Models\Billing;
use App\Models\Table;
use App\Models\User;
use App\Services\TablePowerController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class TablePowerControllerTest extends TestCase
{
    use RefreshDatabase;

    protected TablePowerController $powerController;
    protected Table $table;

    protected function setUp(): void
    {
        parent::setUp();
        $this->powerController = app(TablePowerController::class);

        $this->table = Table::create([
            'table_number'  => 'T10',
            'name'          => 'Table 10',
            'description'   => 'Test Table',
            'status'        => 'available',
            'device_status' => false,
            'is_active'     => 1,
        ]);
    }

    public function test_turn_on_powers_table_and_broadcasts(): void
    {
        Event::fake([TableStatusUpdated::class]);

        $result = $this->powerController->turnOn($this->table, 'test');

        $this->assertInstanceOf(Table::class, $result);
        $this->assertTrue((bool)$this->table->fresh()->device_status);

        Event::assertDispatched(TableStatusUpdated::class, function ($event) {
            return $event->tableId === $this->table->id;
        });
    }

    public function test_turn_off_powers_table_down_and_broadcasts(): void
    {
        $this->table->update(['device_status' => true]);
        Event::fake([TableStatusUpdated::class]);

        $result = $this->powerController->turnOff($this->table, 'session_expired');

        $this->assertInstanceOf(Table::class, $result);
        $this->assertFalse((bool)$this->table->fresh()->device_status);

        Event::assertDispatched(TableStatusUpdated::class, function ($event) {
            return $event->tableId === $this->table->id;
        });
    }
}
