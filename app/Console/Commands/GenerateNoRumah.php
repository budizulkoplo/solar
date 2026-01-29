<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\UnitDetail;
use App\Models\Unit;

class GenerateNoRumah extends Command
{
    protected $signature = 'generate:no-rumah';
    protected $description = 'Generate no_rumah untuk unit details yang sudah ada';

    public function handle()
    {
        $this->info('Memulai generate no_rumah...');
        
        $units = Unit::whereNotNull('blok')->get();
        
        $totalUpdated = 0;
        
        foreach ($units as $unit) {
            $details = $unit->details()->orderBy('created_at', 'asc')->get();
            
            $this->info("Processing unit: {$unit->namaunit} (Blok: {$unit->blok}) - {$details->count()} details");
            
            foreach ($details as $index => $detail) {
                $noRumah = $unit->blok . '-' . ($index + 1);
                
                if ($detail->no_rumah != $noRumah) {
                    $detail->update(['no_rumah' => $noRumah]);
                    $totalUpdated++;
                    $this->info("  Updated: Detail ID {$detail->id} => {$noRumah}");
                } else {
                    $this->line("  Skipped: Detail ID {$detail->id} already has no_rumah: {$detail->no_rumah}");
                }
            }
        }
        
        $this->info("Generate no_rumah selesai! Total yang diupdate: {$totalUpdated}");
        
        // Juga update unit yang tidak punya blok
        $this->info("\nMemproses unit tanpa blok...");
        $unitsWithoutBlok = Unit::whereNull('blok')->get();
        
        foreach ($unitsWithoutBlok as $unit) {
            $details = $unit->details()->orderBy('created_at', 'asc')->get();
            
            foreach ($details as $index => $detail) {
                if (!$detail->no_rumah) {
                    $noRumah = 'UR-' . $detail->id;
                    $detail->update(['no_rumah' => $noRumah]);
                    $totalUpdated++;
                    $this->info("  Updated unit tanpa blok: Detail ID {$detail->id} => {$noRumah}");
                }
            }
        }
        
        $this->info("\nTotal semua yang diupdate: {$totalUpdated}");
    }
}