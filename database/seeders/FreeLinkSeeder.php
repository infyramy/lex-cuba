<?php

namespace Database\Seeders;

use App\Models\FreeLink;
use Illuminate\Database\Seeder;

class FreeLinkSeeder extends Seeder
{
    public function run(): void
    {
        $links = [
            ['title' => 'Statutes', 'url' => 'https://www.lawnet.com.my', 'sort_order' => 1],
            ['title' => 'Case Law', 'url' => 'https://www.kehakiman.gov.my', 'sort_order' => 2],
            ['title' => 'UNISZA Learning Platform', 'url' => 'https://elearning.unisza.edu.my', 'sort_order' => 3],
            ['title' => 'Judiciary Virtual Tour', 'url' => 'https://www.kehakiman.gov.my/virtual-tour', 'sort_order' => 4],
            ['title' => 'Google Scholar', 'url' => 'https://scholar.google.com', 'sort_order' => 5],
            ['title' => 'Quiz / Game (Wayground)', 'url' => 'https://wayground.com', 'sort_order' => 6],
            ['title' => 'Micro Credential EduflexS', 'url' => 'https://eduflexs.com', 'sort_order' => 7],
        ];

        foreach ($links as $link) {
            FreeLink::updateOrCreate(
                ['title' => $link['title']],
                array_merge($link, ['is_active' => true])
            );
        }
    }
}
