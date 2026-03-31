<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AssetExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, ShouldAutoSize
{
    protected Collection $assets;

    public function __construct(Collection $assets)
    {
        $this->assets = $assets->values();
    }

    public function collection()
    {
        return $this->assets;
    }

    public function headings(): array
    {
        return [
            'No',
            'Kode Asset',
            'Nama Asset',
            'Tanggal Pembelian',
            'Harga Perolehan',
            'Nilai Residu',
            'Umur Ekonomis (bulan)',
            'Metode Penyusutan',
            'Persentase Susut (%)',
            'Nilai Buku',
            'Akumulasi Penyusutan',
            'Status',
            'Lokasi',
            'PIC',
            'Keterangan',
        ];
    }

    public function map($asset): array
    {
        static $no = 1;

        $nilaiBuku = (float) ($asset->nilai_buku ?? 0);
        $hargaPerolehan = (float) ($asset->harga_perolehan ?? 0);

        return [
            $no++,
            $asset->kode_aset,
            $asset->nama_aset,
            $asset->tanggal_pembelian ? Carbon::parse($asset->tanggal_pembelian)->format('d/m/Y') : '-',
            $hargaPerolehan,
            (float) ($asset->nilai_residu ?? 0),
            (int) ($asset->umur_ekonomis ?? 0),
            $asset->metode_penyusutan,
            $asset->persentase_susut !== null ? (float) $asset->persentase_susut : '-',
            $nilaiBuku,
            $hargaPerolehan - $nilaiBuku,
            ucfirst((string) $asset->status),
            $asset->lokasi ?? '-',
            $asset->pic ?? '-',
            $asset->keterangan ?? '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $this->assets->count() + 1;

        $sheet->getStyle('A1:O1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0D6EFD'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => 'CED4DA'],
                ],
            ],
        ]);

        if ($lastRow >= 2) {
            $sheet->getStyle('A2:O' . $lastRow)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => 'DEE2E6'],
                    ],
                ],
                'alignment' => [
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ]);
        }

        $sheet->setAutoFilter('A1:O1');
        $sheet->freezePane('A2');
        $sheet->getRowDimension(1)->setRowHeight(24);

        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,
            'B' => 20,
            'C' => 35,
            'D' => 18,
            'E' => 18,
            'F' => 18,
            'G' => 18,
            'H' => 22,
            'I' => 18,
            'J' => 18,
            'K' => 20,
            'L' => 15,
            'M' => 20,
            'N' => 20,
            'O' => 35,
        ];
    }
}
