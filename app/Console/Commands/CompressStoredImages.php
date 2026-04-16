<?php

namespace App\Console\Commands;

use App\Services\ImageCompressionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CompressStoredImages extends Command
{
    protected $signature = 'images:compress
                            {--disk=public : Storage disk}
                            {--dir=* : Folder target di disk}
                            {--max-width=1600 : Lebar maksimum}
                            {--max-height=1600 : Tinggi maksimum}
                            {--quality=75 : JPEG quality}
                            {--min-kb=300 : Hanya proses file di atas ukuran ini}';

    protected $description = 'Kompresi gambar lama yang sudah tersimpan di server';

    public function handle(ImageCompressionService $imageCompressionService): int
    {
        $disk = (string) $this->option('disk');
        $directories = $this->option('dir');

        if (empty($directories)) {
            $directories = [
                'bukti_nota',
                'bukti_nota/konstruksi',
                'bukti_nota_pt',
                'bukti_payment',
                'agency_sales',
            ];
        }

        $options = [
            'max_width' => (int) $this->option('max-width'),
            'max_height' => (int) $this->option('max-height'),
            'quality' => (int) $this->option('quality'),
            'min_bytes' => ((int) $this->option('min-kb')) * 1024,
        ];

        $processed = 0;
        $skipped = 0;
        $savedBytes = 0;

        foreach ($directories as $directory) {
            if (!Storage::disk($disk)->exists($directory)) {
                $this->warn("Folder tidak ditemukan: {$directory}");
                continue;
            }

            $this->line("Memproses folder: {$directory}");

            foreach (Storage::disk($disk)->allFiles($directory) as $path) {
                $result = $imageCompressionService->compressStoredImage($path, $disk, $options);

                if (!$result) {
                    continue;
                }

                if (!empty($result['skipped'])) {
                    $skipped++;
                    continue;
                }

                $processed++;
                $savedBytes += $result['saved'] ?? 0;

                $this->line(sprintf(
                    '- %s | %s -> %s',
                    $result['path'],
                    $this->formatBytes($result['before'] ?? 0),
                    $this->formatBytes($result['after'] ?? 0)
                ));
            }
        }

        $this->newLine();
        $this->info("Selesai. File diproses: {$processed}, dilewati: {$skipped}, hemat: {$this->formatBytes($savedBytes)}");

        return self::SUCCESS;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024) {
            return number_format($bytes / 1024 / 1024, 2) . ' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' B';
    }
}
