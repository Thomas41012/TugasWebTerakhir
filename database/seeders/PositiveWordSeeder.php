<?php

namespace Database\Seeders;

use App\Models\PositiveWord;
use Illuminate\Database\Seeder;

class PositiveWordSeeder extends Seeder
{
    public function run(): void
    {
        $words = [
            'growth' => 2.00,
            'increase' => 1.50,
            'increased' => 1.50,
            'increasing' => 1.50,
            'profit' => 2.00,
            'profitable' => 2.00,
            'stable' => 1.50,
            'stability' => 1.50,
            'improve' => 1.50,
            'improved' => 1.50,
            'improvement' => 1.50,
            'recovery' => 2.00,
            'recover' => 1.50,
            'strong' => 2.00,
            'stronger' => 2.00,
            'success' => 2.00,
            'successful' => 2.00,
            'positive' => 1.50,
            'gain' => 1.50,
            'gains' => 1.50,
            'gained' => 1.50,
            'expand' => 1.50,
            'expanded' => 1.50,
            'expanding' => 1.50,
            'expansion' => 2.00,
            'surplus' => 2.00,
            'efficient' => 1.50,
            'efficiency' => 1.50,
            'opportunity' => 1.50,
            'opportunities' => 1.50,
            'investment' => 1.50,
            'investments' => 1.50,
            'development' => 1.50,
            'progress' => 1.50,
            'record' => 1.00,
            'rise' => 1.50,
            'rising' => 1.50,
            'rose' => 1.50,
            'boost' => 1.50,
            'boosted' => 1.50,
            'secure' => 1.50,
            'secured' => 1.50,
            'cooperation' => 1.50,
            'partnership' => 1.50,
            'agreement' => 1.50,
            'innovation' => 1.50,
            'resilient' => 2.00,
            'resilience' => 2.00,
            'accelerate' => 1.50,
            'accelerated' => 1.50,
            'advance' => 1.50,
            'advanced' => 1.50,
            'benefit' => 1.50,
            'benefits' => 1.50,
            'competitive' => 1.50,
            'demand' => 1.00,
            'optimistic' => 2.00,
            'breakthrough' => 2.00,
            'upgrade' => 1.50,
            'upgraded' => 1.50,
            'modernization' => 1.50,
            'sustainable' => 1.50,
            'sustainability' => 1.50,
            'accessible' => 1.00,
            'availability' => 1.00,
            'productive' => 1.50,
            'productivity' => 1.50,
            'competitive' => 1.50,
            'reliable' => 1.50,
            'reliability' => 1.50,
            'support' => 1.00,
            'supported' => 1.00,
            'strengthen' => 1.50,
            'strengthened' => 1.50,
            'strengthening' => 1.50,
            'prosperity' => 2.00,
            'prosperous' => 2.00,
            'advantage' => 1.50,
            'advantages' => 1.50,
            'rebound' => 2.00,
            'rebounded' => 2.00,
        ];

        foreach ($words as $word => $weight) {
            PositiveWord::updateOrCreate(
                [
                    'word' => mb_strtolower(
                        trim($word)
                    ),
                ],
                [
                    'weight' => $weight,
                ]
            );
        }
    }
}