<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GenerateMonthlyTagihan extends Command
{
    protected $signature = 'tagihan:generate';
    protected $description = 'Generate tagihan bulanan otomatis setiap tanggal 1';

    public function handle()
    {
        $settings = DB::table('setting')->get();

        foreach ($settings as $setting) {
            $bulanDepan = Carbon::now()->addMonthNoOverflow()->startOfMonth();
            
            // cek apakah sudah ada tagihan untuk bulan depan
            $exists = DB::table('tagihan')
                ->where('setting_id', $setting->id)
                ->where('periode', $bulanDepan->toDateString())
                ->exists();

            if (!$exists) {
                DB::table('tagihan')->insert([
                    'setting_id' => $setting->id,
                    'periode' => $bulanDepan->toDateString(),
                    'tanggal_tagihan' => $bulanDepan->toDateString(),
                    'jatuh_tempo' => $bulanDepan->copy()->day(10)->toDateString(),
                    'total' => $setting->biaya ?? 1000000,
                    'status' => 'unpaid',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->info("Tagihan untuk {$bulanDepan->format('M Y')} berhasil dibuat.");
            }
        }
    }
}
