<?php

namespace App\Console\Commands;

use App\Models\PmProjectNote;
use App\Models\PmTask;
use App\Models\PmTaskComment;
use App\Support\PmHtml;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class MigratePmAttachments extends Command
{
    protected $signature = 'pm:migrate-attachments {--dry-run : Hitung URL tanpa mengunduh atau mengubah data} {--all : Sertakan PDF dan dokumen selain gambar}';

    protected $description = 'Migrasikan attachment PM lama ke storage lokal Solar dan rewrite URL HTML';

    private const ALLOWED_HOSTS = [
        'pm.mysolarsystem.web.id',
        'pm.mentarimultitrada.com',
    ];

    private array $localUrlMap = [];
    private int $downloaded = 0;
    private int $reused = 0;
    private int $failed = 0;
    private bool $includeDocuments = false;

    public function handle(): int
    {
        $this->includeDocuments = (bool) $this->option('all');
        $source = DB::connection('pm');
        $texts = collect();

        foreach ($source->table('tickets')->get(['id', 'description']) as $ticket) {
            $texts->push(['type' => 'task', 'id' => $ticket->id, 'html' => $ticket->description]);
        }
        foreach ($source->table('ticket_comments')->get(['id', 'comment']) as $comment) {
            $texts->push(['type' => 'comment', 'id' => $comment->id, 'html' => $comment->comment]);
        }
        if ($source->getSchemaBuilder()->hasTable('project_notes')) {
            foreach ($source->table('project_notes')->get(['id', 'content']) as $note) {
                $texts->push(['type' => 'note', 'id' => $note->id, 'html' => $note->content]);
            }
        }

        $urls = $texts->flatMap(fn (array $item) => $this->extractUrls($item['html']))->unique()->values();
        $this->info('Menemukan '.$urls->count().' URL attachment unik dari '.$texts->count().' konten PM.');

        if ($this->option('dry-run')) {
            $this->table(['No', 'URL'], $urls->take(30)->map(fn (string $url, int $index) => [$index + 1, $url])->all());
            if ($urls->count() > 30) {
                $this->line('... dan '.($urls->count() - 30).' URL lainnya.');
            }
            return self::SUCCESS;
        }

        foreach ($urls as $url) {
            $this->localUrlMap[$url] = $this->migrateUrl($url);
        }

        DB::transaction(function () use ($source, $texts): void {
            foreach ($texts as $item) {
                $html = $this->rewriteHtml($item['html']);
                if ($item['type'] === 'task') {
                    PmTask::where('legacy_ticket_id', $item['id'])->update(['description' => PmHtml::sanitize($html)]);
                } elseif ($item['type'] === 'comment') {
                    PmTaskComment::where('legacy_comment_id', $item['id'])->update(['message' => PmHtml::sanitize($html)]);
                } else {
                    PmProjectNote::where('legacy_note_id', $item['id'])->update(['content' => PmHtml::sanitize($html)]);
                }
            }
        });

        $this->info("Selesai: {$this->downloaded} diunduh, {$this->reused} sudah ada, {$this->failed} gagal.");
        return $this->failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function extractUrls(?string $html): array
    {
        $html = html_entity_decode((string) $html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        preg_match_all('~https?://[^"\'<>\s]+/storage/[^"\'<>\s]+~i', $html, $matches);

        return collect($matches[0] ?? [])
            ->map(fn (string $url) => rtrim($url, '.,);]'))
            ->filter(fn (string $url) => $this->isAllowedUrl($url))
            ->unique()
            ->values()
            ->all();
    }

    private function migrateUrl(string $url): string
    {
        if (isset($this->localUrlMap[$url])) {
            return $this->localUrlMap[$url];
        }

        $path = parse_url($url, PHP_URL_PATH) ?: '/storage/attachments/attachment';
        $filename = basename($path);
        $filename = preg_replace('/[^A-Za-z0-9._-]+/', '_', $filename) ?: 'attachment';
        $storagePath = 'pm-attachments/'.sha1($url).'-'.$filename;

        if (Storage::disk('public')->exists($storagePath)) {
            $this->reused++;
            return '/storage/'.$storagePath;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $candidates = [$url];
        if ($host === 'pm.mentarimultitrada.com') {
            $candidates = ['https://pm.mysolarsystem.web.id'.(parse_url($url, PHP_URL_PATH) ?: ''), $url];
        } elseif ($host === 'pm.mysolarsystem.web.id') {
            $candidates = [$url, 'https://pm.mentarimultitrada.com'.(parse_url($url, PHP_URL_PATH) ?: '')];
        }

        $lastError = null;
        foreach (array_unique($candidates) as $candidate) {
            try {
                $response = Http::timeout(10)->get($candidate);
                $contentType = strtolower((string) $response->header('Content-Type'));
                if (!$response->successful() || str_starts_with($contentType, 'text/html')) {
                    $lastError = 'HTTP '.$response->status();
                    continue;
                }

                Storage::disk('public')->put($storagePath, $response->body());
                $this->downloaded++;
                return '/storage/'.$storagePath;
            } catch (\Throwable $exception) {
                $lastError = $exception->getMessage();
            }
        }

        $this->failed++;
        $this->warn('Gagal mengambil attachment: '.$url.' ('.$lastError.')');
        return '/pm/dashboard';
    }

    private function rewriteHtml(?string $html): string
    {
        $html = html_entity_decode((string) $html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        foreach ($this->extractUrls($html) as $url) {
            $html = str_replace($url, $this->localUrlMap[$url] ?? $url, $html);
        }

        return $html;
    }

    private function isAllowedUrl(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $path = (string) parse_url($url, PHP_URL_PATH);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        $extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'avif'];
        if ($this->includeDocuments) {
            $extensions = array_merge($extensions, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'ppt', 'pptx', 'zip', 'txt', 'mp4', 'webm']);
        }

        return in_array($host, self::ALLOWED_HOSTS, true)
            && str_starts_with($path, '/storage/')
            && in_array($extension, $extensions, true);
    }
}
