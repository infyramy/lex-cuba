<?php

namespace Database\Seeders;

use App\Models\MemberSubscription;
use App\Models\Package;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MemberSeeder extends Seeder
{
    public function run(): void
    {
        // ── Packages ─────────────────────────────────────────────────────
        $basic = Package::updateOrCreate(
            ['name' => 'Basic'],
            [
                'description'              => 'Access to notes and case summaries for self-study.',
                'price'                    => 29.00,
                'duration_months'          => 1,
                'chatbot_access'           => false,
                'accessible_category_ids'  => null,
                'is_active'                => true,
            ]
        );

        $pro = Package::updateOrCreate(
            ['name' => 'Pro'],
            [
                'description'              => 'Full access to all modules including question bank and AI chatbot.',
                'price'                    => 59.00,
                'duration_months'          => 3,
                'chatbot_access'           => true,
                'accessible_category_ids'  => null,
                'is_active'                => true,
            ]
        );

        Package::updateOrCreate(
            ['name' => 'Annual'],
            [
                'description'              => 'Best value — 12 months of full Pro access.',
                'price'                    => 179.00,
                'duration_months'          => 12,
                'chatbot_access'           => true,
                'accessible_category_ids'  => null,
                'is_active'                => true,
            ]
        );

        // ── Members ──────────────────────────────────────────────────────
        $alice = User::updateOrCreate(
            ['email' => 'alice@student.unisza.edu.my'],
            [
                'name'        => 'Alice Rahman',
                'password'    => Hash::make('password'),
                'user_type'   => 'member',
                'status'      => 'active',
                'institution' => 'Universiti Sultan Zainal Abidin',
                'is_bypassed' => false,
                'role_id'     => null,
            ]
        );

        $bob = User::updateOrCreate(
            ['email' => 'bob@lawfaculty.um.edu.my'],
            [
                'name'        => 'Bob Hakim',
                'password'    => Hash::make('password'),
                'user_type'   => 'member',
                'status'      => 'active',
                'institution' => 'Universiti Malaya',
                'is_bypassed' => false,
                'role_id'     => null,
            ]
        );

        $carol = User::updateOrCreate(
            ['email' => 'carol@student.upm.edu.my'],
            [
                'name'        => 'Carol Lim',
                'password'    => Hash::make('password'),
                'user_type'   => 'member',
                'status'      => 'suspended',
                'institution' => 'Universiti Putra Malaysia',
                'is_bypassed' => false,
                'role_id'     => null,
            ]
        );

        // ── Subscriptions ────────────────────────────────────────────────
        $subscribedAt = Carbon::now()->subDays(10);

        MemberSubscription::updateOrCreate(
            ['user_id' => $alice->id],
            [
                'package_id'    => $pro->id,
                'package_name'  => $pro->name,
                'package_price' => $pro->price,
                'subscribed_at' => $subscribedAt,
                'expires_at'    => $subscribedAt->copy()->addMonths($pro->duration_months),
                'notes'         => 'Initial subscription.',
            ]
        );

        MemberSubscription::updateOrCreate(
            ['user_id' => $bob->id],
            [
                'package_id'    => $basic->id,
                'package_name'  => $basic->name,
                'package_price' => $basic->price,
                'subscribed_at' => Carbon::now()->subDays(20),
                'expires_at'    => Carbon::now()->subDays(20)->addMonths($basic->duration_months),
                'notes'         => null,
            ]
        );
    }
}
