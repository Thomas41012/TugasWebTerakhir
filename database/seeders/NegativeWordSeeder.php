<?php

namespace Database\Seeders;

use App\Models\NegativeWord;
use Illuminate\Database\Seeder;

class NegativeWordSeeder extends Seeder
{
    public function run(): void
    {
        $words = [
            'war' => 3.00,
            'warfare' => 3.00,

            'crisis' => 3.00,
            'crises' => 3.00,

            'inflation' => 2.00,
            'inflationary' => 2.00,

            'delay' => 1.50,
            'delays' => 1.50,
            'delayed' => 1.50,

            'disaster' => 3.00,
            'disasters' => 3.00,

            'decrease' => 1.50,
            'decreased' => 1.50,
            'decreasing' => 1.50,

            'decline' => 1.50,
            'declined' => 1.50,
            'declining' => 1.50,

            'conflict' => 2.50,
            'conflicts' => 2.50,

            'risk' => 1.50,
            'risks' => 1.50,
            'risky' => 1.50,

            'recession' => 3.00,

            'loss' => 2.00,
            'losses' => 2.00,
            'lost' => 2.00,

            'collapse' => 3.00,
            'collapsed' => 3.00,
            'collapsing' => 3.00,

            'storm' => 2.00,
            'storms' => 2.00,

            'flood' => 2.50,
            'floods' => 2.50,
            'flooding' => 2.50,

            'earthquake' => 3.00,
            'earthquakes' => 3.00,

            'shortage' => 2.50,
            'shortages' => 2.50,

            'congestion' => 2.00,
            'congested' => 2.00,

            'sanction' => 2.50,
            'sanctions' => 2.50,
            'sanctioned' => 2.50,

            'attack' => 3.00,
            'attacks' => 3.00,
            'attacked' => 3.00,

            'disruption' => 2.50,
            'disruptions' => 2.50,
            'disrupted' => 2.50,

            'volatile' => 2.00,
            'volatility' => 2.00,

            'weak' => 1.50,
            'weaker' => 1.50,
            'weakness' => 1.50,

            'danger' => 2.00,
            'dangerous' => 2.00,

            'negative' => 1.50,

            'failure' => 2.00,
            'failed' => 2.00,

            'bankruptcy' => 3.00,
            'bankrupt' => 3.00,

            'default' => 2.50,

            'deficit' => 2.00,

            'debt' => 1.50,

            'uncertainty' => 2.00,
            'uncertain' => 2.00,

            'unstable' => 2.00,
            'instability' => 2.00,

            'slowdown' => 2.00,
            'slowing' => 1.50,

            'drop' => 1.50,
            'dropped' => 1.50,
            'fall' => 1.50,
            'falling' => 1.50,
            'fell' => 1.50,

            'surge' => 1.50,
            'spike' => 1.50,

            'tariff' => 1.50,
            'tariffs' => 1.50,

            'restriction' => 1.50,
            'restrictions' => 1.50,
            'restricted' => 1.50,

            'ban' => 2.00,
            'banned' => 2.00,

            'blockade' => 3.00,

            'strike' => 2.50,
            'strikes' => 2.50,

            'protest' => 2.00,
            'protests' => 2.00,

            'shutdown' => 2.50,

            'closure' => 2.00,
            'closed' => 1.50,

            'damage' => 2.00,
            'damaged' => 2.00,

            'destruction' => 3.00,
            'destroyed' => 3.00,

            'threat' => 2.00,
            'threats' => 2.00,

            'tension' => 2.00,
            'tensions' => 2.00,

            'scarcity' => 2.50,

            'expensive' => 1.50,

            'costly' => 1.50,

            'unemployment' => 2.00,

            'poverty' => 2.00,

            'emergency' => 2.50,

            'outage' => 2.00,
            'outages' => 2.00,

            'accident' => 2.00,
            'accidents' => 2.00,

            'breach' => 2.00,

            'fraud' => 2.50,

            'corruption' => 2.50,

            'illegal' => 2.00,

            'smuggling' => 2.50,

            'piracy' => 2.50,

            'cyberattack' => 3.00,
            'cyberattacks' => 3.00,

            'vulnerability' => 2.00,
            'vulnerabilities' => 2.00,

            'contamination' => 2.50,

            'recall' => 2.00,

            'penalty' => 1.50,
            'penalties' => 1.50,
        ];

        foreach ($words as $word => $weight) {
            NegativeWord::updateOrCreate(
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