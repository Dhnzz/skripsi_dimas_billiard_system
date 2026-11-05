<?php

namespace Tests\Feature\Api;

use App\Models\Table;
use Tests\TestCase;

class TableLightBatchTest extends TestCase
{
    public function test_batch_tables_light_endpoint_returns_correct_json_format()
    {
        $response = $this->getJson('/api/microcontroller/tables/light');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'table_id',
                    'table_name',
                    'light_on',
                ]
            ]
        ]);
    }

    public function test_table_light_changes_reflect_in_batch_endpoint()
    {
        $table = Table::where('is_active', true)->first();
        $this->assertNotNull($table);

        $table->update(['device_status' => true]);
        $resOn = $this->getJson('/api/microcontroller/tables/light');
        $resOn->assertJsonFragment([
            'table_id' => $table->id,
            'light_on' => true,
        ]);

        $table->update(['device_status' => false]);
        $resOff = $this->getJson('/api/microcontroller/tables/light');
        $resOff->assertJsonFragment([
            'table_id' => $table->id,
            'light_on' => false,
        ]);
    }
}
