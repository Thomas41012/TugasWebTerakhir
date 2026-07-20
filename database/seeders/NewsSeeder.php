<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\News;
use Illuminate\Database\Seeder;

class NewsSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'Global trade growth improves amid stable market conditions',
                'Global trade shows positive growth and improvement in international supply chain activity.',
                'economy',
                'positive',
                4,
                0,
                1.0000,
            ],
            [
                'Port congestion causes shipping delay',
                'Increasing congestion at major ports creates delays and disruption for international shipping.',
                'shipping',
                'negative',
                0,
                4,
                -1.0000,
            ],
            [
                'Export market remains stable',
                'Export activity remains stable while investment creates new opportunities.',
                'trade',
                'positive',
                3,
                0,
                1.0000,
            ],
            [
                'Inflation risk affects production costs',
                'Inflation and volatile currency conditions increase risk for production and logistics.',
                'economy',
                'negative',
                1,
                4,
                -0.7500,
            ],
        ];

        foreach (Country::all() as $country) {
            foreach ($templates as $index => $template) {
                News::create([
                    'country_id' => $country->id,
                    'title' => $country->name . ': ' . $template[0],
                    'description' => $template[1],
                    'content' => $template[1],
                    'source' => 'Global Supply Intelligence',
                    'url' => 'https://example.com/news/' . uniqid(),
                    'image_url' => null,
                    'category' => $template[2],
                    'sentiment' => $template[3],
                    'positive_score' => $template[4],
                    'negative_score' => $template[5],
                    'sentiment_score' => $template[6],
                    'published_at' => now()->subHours($index * 8),
                ]);
            }
        }
    }
}