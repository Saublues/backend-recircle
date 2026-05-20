<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Admin User ─────────────────────────────
        User::create([
            'name'     => 'Admin ReCircle',
            'email'    => 'admin@recircle.id',
            'password' => bcrypt('password'),
            'role'     => 'admin',
            'is_seller' => false,
            'kampus'   => 'IPB University',
        ]);

        // ── Demo User ──────────────────────────────
        User::create([
            'name'     => 'Sausan Asma',
            'email'    => 'sausan@recircle.id',
            'password' => bcrypt('password'),
            'role'     => 'user',
            'is_seller' => true,
            'kampus'   => 'IPB University',
            'nomor_wa' => '628123456789',
        ]);

        // ── Categories ─────────────────────────────
        $categories = [
            ['nama_kategori' => 'Elektronik',          'ikon' => 'Laptop'],
            ['nama_kategori' => 'Furniture',           'ikon' => 'Armchair'],
            ['nama_kategori' => 'Fashion',             'ikon' => 'Shirt'],
            ['nama_kategori' => 'Buku & Modul',        'ikon' => 'BookOpen'],
            ['nama_kategori' => 'Gadget',              'ikon' => 'Smartphone'],
            ['nama_kategori' => 'Hobi',                'ikon' => 'Gamepad2'],
            ['nama_kategori' => 'Perlengkapan Kost',   'ikon' => 'Home'],
            ['nama_kategori' => 'Otomotif',            'ikon' => 'Bike'],
        ];

        foreach ($categories as $cat) {
            Category::create([
                'nama_kategori' => $cat['nama_kategori'],
                'slug'          => Str::slug($cat['nama_kategori']),
                'ikon'          => $cat['ikon'],
            ]);
        }
    }
}
