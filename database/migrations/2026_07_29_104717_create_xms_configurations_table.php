<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('xms_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label')->nullable();
            $table->json('value')->nullable();
            $table->timestamps();
        });

        DB::table('xms_configurations')->insert([
            'key' => 'campaign-simulator-oursblanc-rates',
            'label' => 'Campaign Simulator — Rate table (OursBlanc)',
            'value' => json_encode($this->defaultCampaignSimulatorRates()),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('xms_configurations');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function defaultCampaignSimulatorRates(): array
    {
        return [
            ['format' => 'SmartPulse', 'type' => 'Vidéo', 'ratio' => '16/9', 'cpm_min' => 2.8, 'cpm_max' => 9.3, 'att_min' => 6.7, 'att_max' => 16.2, 'ctr_min' => 2.0, 'ctr_max' => 7.0],
            ['format' => 'SmartPulse', 'type' => 'Vidéo', 'ratio' => '9/16', 'cpm_min' => 2.8, 'cpm_max' => 9.3, 'att_min' => 9.0, 'att_max' => 21.3, 'ctr_min' => 3.2, 'ctr_max' => 14.0],
            ['format' => 'SmartPulse', 'type' => 'Image', 'ratio' => '16/9', 'cpm_min' => 2.8, 'cpm_max' => 9.3, 'att_min' => 4.5, 'att_max' => 12.8, 'ctr_min' => 2.3, 'ctr_max' => 6.2],
            ['format' => 'SmartPulse', 'type' => 'Image', 'ratio' => '9/16', 'cpm_min' => 2.8, 'cpm_max' => 9.3, 'att_min' => 6.7, 'att_max' => 14.5, 'ctr_min' => 2.7, 'ctr_max' => 7.4],
            ['format' => 'SmartPulse', 'type' => 'Slider', 'ratio' => '16/9', 'cpm_min' => 2.8, 'cpm_max' => 9.3, 'att_min' => 5.7, 'att_max' => 15.1, 'ctr_min' => 2.6, 'ctr_max' => 8.9],
            ['format' => 'SmartPulse', 'type' => 'Slider', 'ratio' => '9/16', 'cpm_min' => 2.8, 'cpm_max' => 9.3, 'att_min' => 6.8, 'att_max' => 20.3, 'ctr_min' => 3.1, 'ctr_max' => 13.2],
            ['format' => 'SmartView', 'type' => 'Vidéo', 'ratio' => '16/9', 'cpm_min' => 2.8, 'cpm_max' => 9.3, 'att_min' => 12, 'att_max' => 35, 'ctr_min' => 1.3, 'ctr_max' => 4.1],
            ['format' => 'SmartView', 'type' => 'Image', 'ratio' => '16/9', 'cpm_min' => 2.8, 'cpm_max' => 9.3, 'att_min' => 14, 'att_max' => 28, 'ctr_min' => 0.9, 'ctr_max' => 2.3],
            ['format' => 'SmartView', 'type' => 'Slider', 'ratio' => '16/9', 'cpm_min' => 2.8, 'cpm_max' => 9.3, 'att_min' => 21, 'att_max' => 43, 'ctr_min' => 0.9, 'ctr_max' => 3.8],
            ['format' => 'SmartSkin', 'type' => 'Vidéo', 'ratio' => '-', 'cpm_min' => 6, 'cpm_max' => 23, 'att_min' => 18.3, 'att_max' => 62, 'ctr_min' => 0.6, 'ctr_max' => 2.1],
            ['format' => 'SmartSkin', 'type' => 'Image', 'ratio' => '-', 'cpm_min' => 6, 'cpm_max' => 23, 'att_min' => 11, 'att_max' => 37, 'ctr_min' => 0.6, 'ctr_max' => 1.1],
            ['format' => 'SmartSkin', 'type' => 'Avant/Après', 'ratio' => '-', 'cpm_min' => 6, 'cpm_max' => 23, 'att_min' => 13.4, 'att_max' => 57.5, 'ctr_min' => 0.6, 'ctr_max' => 1.8],
        ];
    }
};
