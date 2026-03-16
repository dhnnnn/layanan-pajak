<?php

namespace Database\Seeders;

use App\Models\Month;
use Illuminate\Database\Seeder;

class MonthSeeder extends Seeder
{
    public function run(): void
    {
        $months = [
            [
                'number' => 1,
                'name' => 'Januari',
                'abbreviation' => 'Jan',
                'column_name' => 'january',
            ],
            [
                'number' => 2,
                'name' => 'Februari',
                'abbreviation' => 'Feb',
                'column_name' => 'february',
            ],
            [
                'number' => 3,
                'name' => 'Maret',
                'abbreviation' => 'Mar',
                'column_name' => 'march',
            ],
            [
                'number' => 4,
                'name' => 'April',
                'abbreviation' => 'Apr',
                'column_name' => 'april',
            ],
            [
                'number' => 5,
                'name' => 'Mei',
                'abbreviation' => 'Mei',
                'column_name' => 'may',
            ],
            [
                'number' => 6,
                'name' => 'Juni',
                'abbreviation' => 'Jun',
                'column_name' => 'june',
            ],
            [
                'number' => 7,
                'name' => 'Juli',
                'abbreviation' => 'Jul',
                'column_name' => 'july',
            ],
            [
                'number' => 8,
                'name' => 'Agustus',
                'abbreviation' => 'Ags',
                'column_name' => 'august',
            ],
            [
                'number' => 9,
                'name' => 'September',
                'abbreviation' => 'Sep',
                'column_name' => 'september',
            ],
            [
                'number' => 10,
                'name' => 'Oktober',
                'abbreviation' => 'Okt',
                'column_name' => 'october',
            ],
            [
                'number' => 11,
                'name' => 'November',
                'abbreviation' => 'Nov',
                'column_name' => 'november',
            ],
            [
                'number' => 12,
                'name' => 'Desember',
                'abbreviation' => 'Des',
                'column_name' => 'december',
            ],
        ];

        foreach ($months as $month) {
            Month::query()->firstOrCreate(
                ['number' => $month['number']],
                [
                    'name' => $month['name'],
                    'abbreviation' => $month['abbreviation'],
                    'column_name' => $month['column_name'],
                ],
            );
        }
    }
}
