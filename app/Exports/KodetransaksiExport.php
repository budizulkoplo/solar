<?php

namespace App\Exports;

use App\Models\Kodetransaksi;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class KodetransaksiExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, ShouldAutoSize
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Kodetransaksi::with(['coa', 'header', 'neraca', 'labarugi'])->get();
    }

    public function headings(): array
    {
        return [
            'No',
            'Kode Transaksi',
            'Nama Transaksi',
            'Header Transaksi',
            'COA',
            'Neraca',
            'Laba Rugi',
            'Tanggal Dibuat',
            'Tanggal Diupdate'
        ];
    }

    public function map($row): array
    {
        static $no = 1;
        return [
            $no++,
            $row->kodetransaksi,
            $row->transaksi,
            $row->header ? $row->header->keterangan : '-',
            $row->coa ? $row->coa->name : '-',
            $row->neraca ? $row->neraca->rincian : '-',
            $row->labarugi ? $row->labarugi->rincian : '-',
            $row->created_at ? Carbon::parse($row->created_at)->format('d/m/Y H:i') : '-',
            $row->updated_at ? Carbon::parse($row->updated_at)->format('d/m/Y H:i') : '-'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Header styling
        $sheet->getStyle('A1:I1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '3498DB']
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ]);

        // Data rows styling
        $lastRow = $this->collection()->count() + 1;
        $sheet->getStyle('A2:I' . $lastRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ],
            'alignment' => [
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ]
        ]);

        // Auto filter
        $sheet->setAutoFilter('A1:I1');

        // Freeze first row
        $sheet->freezePane('A2');

        // Set row height for header
        $sheet->getRowDimension(1)->setRowHeight(25);

        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,   // No
            'B' => 20,  // Kode Transaksi
            'C' => 40,  // Nama Transaksi
            'D' => 30,  // Header Transaksi
            'E' => 30,  // COA
            'F' => 30,  // Neraca
            'G' => 30,  // Laba Rugi
            'H' => 20,  // Tanggal Dibuat
            'I' => 20,  // Tanggal Diupdate
        ];
    }
}