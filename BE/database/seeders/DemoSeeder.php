<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Post;
use App\Models\Album;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $u = User::firstOrCreate(
            ['email' => 'demo@example.com'],
            ['name' => 'Demo', 'password' => Hash::make('demopass123')]
        );

        // Создадим пост вручную (без factory)
        Post::create([
            'user_id' => $u->id,
            'title' => 'Hello World',
            'body' => 'Public demo post',
            'visibility' => 'public',
        ]);

        Album::create([
            'user_id' => $u->id,
            'name' => 'My Album',
            'description' => 'Demo album',
            'visibility' => 'public',
        ]);
    }
}
