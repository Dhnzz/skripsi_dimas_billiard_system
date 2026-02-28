<?php

namespace Database\Seeders;

use App\Models\Addon;
use App\Models\Package;
use App\Models\Pricing;
use App\Models\Table;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // ROLE SEEDER
        $ownerRole = Role::firstOrCreate(['name' => 'owner']);
        $kasirRole = Role::firstOrCreate(['name' => 'kasir']);
        $memberRole = Role::firstOrCreate(['name' => 'member']);

        // USER SEEDER
        // OWNER
        $owner = User::firstOrCreate(
            ['email' => 'owner@swiss.id'],
            [
                'name' => 'Owner',
                'password' => Hash::make('owner123'),
                'phone' => '081231231231',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $owner->assignRole($ownerRole);

        // KASIR
        $kasir = User::firstOrCreate(
            ['email' => 'kasir@swiss.id'],
            [
                'name' => 'Kasir',
                'password' => Hash::make('kasir123'),
                'phone' => '081231231231',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $kasir->assignRole($kasirRole);

        // MEMBER
        $member = User::firstOrCreate(
            ['email' => 'member@swiss.id'],
            [
                'name' => 'Member',
                'password' => Hash::make('member123'),
                'phone' => '081231231231',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $member->assignRole($memberRole);

        // TABLE SEEDER
        $table = [
            ['table_number' => 'M1', 'name' => 'Meja 1', 'description' => 'Meja standar di area depan'],
            ['table_number' => 'M2', 'name' => 'Meja 2', 'description' => 'Meja standar di area depan'],
            ['table_number' => 'M3', 'name' => 'Meja 3', 'description' => 'Meja standar di area tengah'],
            ['table_number' => 'M4', 'name' => 'Meja 4', 'description' => 'Meja standar di area tengah'],
            ['table_number' => 'M5', 'name' => 'Meja 5', 'description' => 'Meja VIP di area belakang'],
            ['table_number' => 'M6', 'name' => 'Meja 6', 'description' => 'Meja VIP di area belakang'],
        ];

        foreach ($table as $t) {
            Table::firstOrCreate(['table_number' => $t['table_number']], $t);
        }

        // PRICING SEEDER
        $pricingReguler = Pricing::firstOrCreate(
            ['name' => 'Harga Reguler'],
            [
                'price_per_hour' => 20000,
                'apply_days'     => ['senin', 'selasa', 'rabu', 'kamis', 'jumat'],
                'start_time'     => null,
                'end_time'       => null,
                'is_active'      => true,
                'created_by'     => $owner->id,
            ]
        );

        $pricingWeekend = Pricing::firstOrCreate(
            ['name' => 'Harga Weekend'],
            [
                'price_per_hour' => 25000,
                'apply_days'     => ['sabtu', 'minggu'],
                'start_time'     => null,
                'end_time'       => null,
                'is_active'      => true,
                'created_by'     => $owner->id,
            ]
        );

        $pricingMalam = Pricing::firstOrCreate(
            ['name' => 'Harga Malam (Peak Hour)'],
            [
                'price_per_hour' => 30000,
                'apply_days'     => null,        // Berlaku semua hari
                'start_time'     => '20:00:00',
                'end_time'       => '23:59:59',
                'is_active'      => true,
                'created_by'     => $owner->id,
            ]
        );
        
        // PACKAGE SEEDER
         // Paket Normal (durasi fix, harga spesial)
         Package::firstOrCreate(
            ['name' => 'Paket 1 Jam'],
            [
                'type'           => 'normal',
                'duration_hours' => 1,
                'price'          => 18000,  // Lebih murah dari normal (20.000)
                'pricing_id'     => null,
                'description'    => 'Hemat untuk main 1 jam',
                'is_active'      => true,
                'created_by'     => $owner->id,
            ]
        );

        Package::firstOrCreate(
            ['name' => 'Paket 2 Jam'],
            [
                'type'           => 'normal',
                'duration_hours' => 2,
                'price'          => 35000,  // Normal: 40.000
                'pricing_id'     => null,
                'description'    => 'Paket favorit! Hemat Rp 5.000',
                'is_active'      => true,
                'created_by'     => $owner->id,
            ]
        );

        Package::firstOrCreate(
            ['name' => 'Paket 3 Jam'],
            [
                'type'           => 'normal',
                'duration_hours' => 3,
                'price'          => 45000,  // Normal: 60.000
                'pricing_id'     => null,
                'description'    => 'Hemat Rp 15.000 untuk 3 jam bermain',
                'is_active'      => true,
                'created_by'     => $owner->id,
            ]
        );

        // Paket Loss (waktu berjalan, dihitung di akhir)
        Package::firstOrCreate(
            ['name' => 'Paket Loss (Reguler)'],
            [
                'type'           => 'loss',
                'duration_hours' => null,
                'price'          => null,
                'pricing_id'     => $pricingReguler->id,
                'description'    => 'Waktu bebas, dihitung di akhir. Harga Rp 20.000/jam (weekday)',
                'is_active'      => true,
                'created_by'     => $owner->id,
            ]
        );

        Package::firstOrCreate(
            ['name' => 'Paket Loss (Weekend)'],
            [
                'type'           => 'loss',
                'duration_hours' => null,
                'price'          => null,
                'pricing_id'     => $pricingWeekend->id,
                'description'    => 'Waktu bebas, dihitung di akhir. Harga Rp 25.000/jam (weekend)',
                'is_active'      => true,
                'created_by'     => $owner->id,
            ]
        );

        // ADDON SEEDER
        $addons = [
            // Minuman
            ['name' => 'Air Mineral 600ml', 'category' => 'minuman', 'price' => 5000,  'stock' => null],
            ['name' => 'Teh Botol Sosro',   'category' => 'minuman', 'price' => 6000,  'stock' => null],
            ['name' => 'Coca Cola Can',      'category' => 'minuman', 'price' => 8000,  'stock' => null],
            ['name' => 'Pocari Sweat',       'category' => 'minuman', 'price' => 8000,  'stock' => null],
            ['name' => 'Kopi Sachet',        'category' => 'minuman', 'price' => 5000,  'stock' => null],
            // Snack
            ['name' => 'Indomie Goreng',     'category' => 'snack',   'price' => 12000, 'stock' => null],
            ['name' => 'Indomie Rebus',      'category' => 'snack',   'price' => 12000, 'stock' => null],
            ['name' => 'Chitato',            'category' => 'snack',   'price' => 8000,  'stock' => null],
            ['name' => 'Lays',               'category' => 'snack',   'price' => 8000,  'stock' => null],
            ['name' => 'Kacang Garuda',      'category' => 'snack',   'price' => 6000,  'stock' => null],
            // Rokok
            ['name' => 'Sampoerna Mild',     'category' => 'rokok',   'price' => 25000, 'stock' => null],
            ['name' => 'Gudang Garam Surya', 'category' => 'rokok',   'price' => 22000, 'stock' => null],
        ];

        foreach ($addons as $a) {
            Addon::firstOrCreate(
                ['name' => $a['name']],
                array_merge($a, ['is_active' => true, 'created_by' => $owner->id])
            );
        }

        // SEEDER INFORMATION
        $this->command->info('✅ Seeder selesai! Akun demo:');
        $this->command->info('   Owner :     owner@swiss.id / owner123');
        $this->command->info('   Kasir :     kasir@swiss.id / kasir123');
        $this->command->info('   Member:     member@swiss.id / member123');
    }
}
