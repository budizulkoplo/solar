<?php

namespace App\Exports;

use App\Models\User;
use App\Models\PresensiVisit;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class VisitExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $bulan;
    protected $tahun;
    protected $nik;

    public function __construct($bulan = null, $tahun = null, $nik = null)
    {
        $this->bulan = $bulan ?? date('m');
        $this->tahun = $tahun ?? date('Y');
        $this->nik = $nik;
    }

    public function collection()
    {
        $query = DB::table('presensi_visit as pv')
            ->select(
                'pv.*',
                'u.name',
                'u.nik as user_nik',
                'cu.company_name'
            )
            ->join('users as u', 'pv.nik', '=', 'u.nik')
            ->leftJoin('company_units as cu', 'u.id_unitkerja', '=', 'cu.id')
            ->whereYear('pv.tgl_presensi', $this->tahun)
            ->whereMonth('pv.tgl_presensi', $this->bulan)
            ->orderBy('u.name')
            ->orderBy('pv.tgl_presensi')
            ->orderBy('pv.jam_in');

        if ($this->nik) {
            $query->where('pv.nik', $this->nik);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'NIK',
            'Nama Pegawai',
            'Unit Kerja',
            'Tanggal',
            'Jenis Presensi',
            'Jam',
            'Keterangan',
            'Lokasi',
            'Status'
        ];
    }

    public function map($row): array
    {
        $jenis = [
            1 => 'Visit Masuk',
            2 => 'Visit Pulang',
            3 => 'Lembur Masuk',
            4 => 'Lembur Pulang'
        ][$row->inoutmode] ?? 'Tidak Diketahui';

        return [
            $row->user_nik,
            $row->name,
            $row->company_name ?? '-',
            Carbon::parse($row->tgl_presensi)->format('d/m/Y'),
            $jenis,
            Carbon::parse($row->jam_in)->format('H:i:s'),
            $row->keterangan ?? '-',
            $row->lokasi ?? '-',
            'Tercatat'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style untuk header
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'color' => ['argb' => '2E86C1']
                ]
            ],
        ];
    }
}