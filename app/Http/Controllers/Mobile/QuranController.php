<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class QuranController extends Controller
{
    public function index()
    {
        $surat = Cache::rememberForever('mobile_daftar_surat_quran', function () {
            $response = Http::timeout(15)->get('https://equran.id/api/v2/surat');

            if ($response->failed()) {
                return [];
            }

            return $response->json('data', []);
        });

        return view('mobile.quran.index', compact('surat'));
    }

    public function show(int $nomor)
    {
        $user = Auth::user();

        $surat = Cache::remember("mobile_surat_quran_{$nomor}", now()->addDay(), function () use ($nomor) {
            $response = Http::timeout(15)->get("https://equran.id/api/v2/surat/{$nomor}");

            if ($response->failed()) {
                return null;
            }

            return data_get($response->json(), 'data');
        });

        if (!$surat) {
            return redirect()
                ->route('mobile.quran.index')
                ->with('warning', 'Gagal mengambil data surat dari API.');
        }

        $riwayat = DB::table('ngaji')
            ->where('nik', $user->nik)
            ->where('surat', $surat['namaLatin'])
            ->get()
            ->map(function ($row) {
                return [
                    'ayat' => (int) $row->ayat,
                    'senin' => ($row->type ?? null) === 'senin',
                    'rutin' => ($row->type ?? 'rutin') === 'rutin',
                ];
            });

        return view('mobile.quran.show', compact('surat', 'riwayat'));
    }

    public function markRutin(Request $request)
    {
        $validated = $request->validate([
            'surat' => ['required', 'string', 'max:255'],
            'ayat' => ['required', 'integer', 'min:1'],
        ]);

        $user = Auth::user();

        DB::table('ngaji')->insert([
            'nik' => $user->nik,
            'pegawai_nama' => $user->name,
            'surat' => $validated['surat'],
            'ayat' => $validated['ayat'],
            'type' => 'rutin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }
}
