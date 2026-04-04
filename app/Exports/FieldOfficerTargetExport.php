<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FieldOfficerTargetExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function __construct(
        private readonly Collection $data,
        private readonly int $year,
        private readonly string $officerName,
        private readonly string $districtName,
    ) {}

    public function collection(): Collection
    {
        return $this->data;
    }

    public function title(): string
    {
        return "Pencapaian Target {$this->year}";
    }

    public function headings(): array
    {
        return [
            ['Pencapaian Target Wilayah Tugas'],
            [$this->districtName.' — Tahun '.$this->year],
            ['Petugas: '.$this->officerName],
            [],
            ['No', 'NPWPD', 'Nama Wajib Pajak', 'Jenis Pajak', 'Status', 'Total Ketetapan (Rp)', 'Total Bayar (Rp)', 'Tunggakan (Rp)'],
        ];
    }

    public function map(mixed $row): array
    {
        static $no = 0;
        $no++;

        return [
            $no,
            $row['npwpd'],
            $row['nm_wp'],
            $row['tax_type_name'] ?? '-',
            $row['status_code'] == '1' ? 'Aktif' : 'Non Aktif',
            $row['total_sptpd'],
            $row['total_bayar'],
            $row['tunggakan'],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 13]],
            5 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '1E293B']], 'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']]],
        ];
    }
}
