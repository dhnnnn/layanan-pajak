<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail WP - {{ $wpInfo?->nm_wp ?? $npwpd }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11px; color: #1a1a1a; background: #fff; margin: 35px 60px 35px 70px; }

        .page-header { text-align: center; margin-bottom: 14px; padding-bottom: 10px; border-bottom: 2px solid #1a1a1a; }
        .page-header h2 { font-size: 14px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
        .page-header p { font-size: 10px; color: #444; margin-top: 3px; }

        .wp-info { border: 1px solid padding: 10px 14px; margin-bottom: 14px; }
        .wp-info table { width: 100%; border-collapse: collapse; }
        .wp-info td { padding: 2px 6px; font-size: 10px; }
        .wp-info td:first-child { font-weight: bold; width: 130px; color: #555; }

        .summary { display: table; width: 100%; margin-bottom: 14px; border-collapse: collapse; }
        .summary-row { display: table-row; }
        .summary-card { display: table-cell; width: 25%; border: 1px solid #ccc; padding: 8px 10px; vertical-align: top; }
        .summary-card .label { font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; color: #666; }
        .summary-card .value { font-size: 12px; font-weight: bold; margin-top: 2px; }
        .summary-card .value.blue { color: #1d4ed8; }
        .summary-card .value.green { color: #15803d; }
        .summary-card .value.red { color: #dc2626; }

        .year-section { margin-bottom: 16px; page-break-inside: avoid; }
        .year-title { background: #1e293b; color: #fff; font-weight: bold; font-size: 11px;
                      padding: 6px 10px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0; }

        table.monthly { width: 100%; border-collapse: collapse; }
        table.monthly th { background: #f1f5f9; font-size: 9px; font-weight: bold; text-transform: uppercase;
                           padding: 5px 8px; border: 1px solid #ccc; text-align: center; }
        table.monthly td { font-size: 10px; padding: 5px 8px; border: 1px solid #ddd; }
        table.monthly td.num { text-align: right; }
        table.monthly td.center { text-align: center; }
        table.monthly tr.total-row td { font-weight: bold; background: #e2e8f0; border-color: #ccc; }
        .badge-lunas { color: #15803d; font-weight: bold; }
        .badge-tunggakan { color: #dc2626; font-weight: bold; }
        .badge-belum { color: #94a3b8; }
    </style>
</head>
<body>

<div class="page-header">
    <h2>Laporan Detail Wajib Pajak</h2>
    <p>
        {{ $wpInfo?->nm_wp ?? $npwpd }} &mdash; NPWPD: {{ $npwpd }}
        &mdash; Periode: {{ $bulanIndo[$selectedMonthFrom] }} – {{ $bulanIndo[$selectedMonthTo] }}
        &mdash; Tahun: {{ $yearLabel }}
    </p>
</div>

{{-- WP Info --}}
<div class="wp-info">
    <table>
        <tr><td>Nama WP</td><td>{{ $wpInfo?->nm_wp ?? '-' }}</td></tr>
        <tr><td>NPWPD</td><td>{{ $npwpd }}</td></tr>
        <tr><td>Jenis Pajak</td><td>{{ $wpInfo?->tax_type_name ?? '-' }}</td></tr>
        <tr><td>Kecamatan</td><td>{{ $districtName }}</td></tr>
        <tr><td>Alamat</td><td>{{ $wpInfo?->almt_op ?? '-' }}</td></tr>
        <tr><td>Status</td><td>{{ ($wpInfo?->status ?? '') === '1' ? 'AKTIF' : 'NON-AKTIF' }}</td></tr>
    </table>
</div>

{{-- Summary --}}
<div class="summary">
    <div class="summary-row">
        <div class="summary-card">
            <div class="label">Total SPTPD {{ $yearLabel }}</div>
            <div class="value blue">Rp {{ number_format($totalSptpdAll, 0, ',', '.') }}</div>
        </div>
        <div class="summary-card">
            <div class="label">Total Bayar {{ $yearLabel }}</div>
            <div class="value green">Rp {{ number_format($totalBayarAll, 0, ',', '.') }}</div>
        </div>
        <div class="summary-card">
            <div class="label">Tunggakan {{ $yearLabel }}</div>
            <div class="value red">Rp {{ number_format($totalTunggakanAll, 0, ',', '.') }}</div>
        </div>
        <div class="summary-card">
            <div class="label">Kepatuhan {{ $yearLabel }}</div>
            <div class="value {{ $pctAll >= 90 ? 'green' : ($pctAll >= 50 ? '' : 'red') }}">
                {{ number_format($pctAll, 1) }}%
            </div>
        </div>
    </div>
</div>

{{-- Per Year Tables --}}
@foreach($tableData as $yr => $rows)
    @php
        $sumS = collect($rows)->sum('total_ketetapan');
        $sumB = collect($rows)->sum('total_bayar');
        $sumT = collect($rows)->sum('total_tunggakan');
    @endphp
    <div class="year-section">
        <div class="year-title">Tahun {{ $yr }}</div>
        <table class="monthly">
            <thead>
                <tr>
                    <th style="width:14%">Bulan</th>
                    <th style="width:13%">Tgl Lapor</th>
                    <th style="width:24%">Ketetapan (SPTPD)</th>
                    <th style="width:24%">Realisasi Bayar</th>
                    <th style="width:16%">Tunggakan</th>
                    <th style="width:9%">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                    @php
                        $hasSptpd = $row['total_ketetapan'] > 0;
                        $lunas    = $hasSptpd && $row['total_tunggakan'] <= 0;
                    @endphp
                    <tr>
                        <td>{{ $row['month_name'] }}</td>
                        <td class="center">{{ $row['tgl_lapor'] }}</td>
                        <td class="num">{{ $hasSptpd ? number_format($row['total_ketetapan'], 0, ',', '.') : '-' }}</td>
                        <td class="num">{{ $row['total_bayar'] > 0 ? number_format($row['total_bayar'], 0, ',', '.') : '-' }}</td>
                        <td class="num">{{ $row['total_tunggakan'] > 0 ? number_format($row['total_tunggakan'], 0, ',', '.') : '-' }}</td>
                        <td class="center">
                            @if(!$hasSptpd)
                                <span class="badge-belum">Belum Lapor</span>
                            @elseif($lunas)
                                <span class="badge-lunas">Lunas</span>
                            @else
                                <span class="badge-tunggakan">Tunggakan</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="2">TOTAL</td>
                    <td class="num">{{ number_format($sumS, 0, ',', '.') }}</td>
                    <td class="num">{{ number_format($sumB, 0, ',', '.') }}</td>
                    <td class="num">{{ number_format($sumT, 0, ',', '.') }}</td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    </div>
@endforeach

</body>
</html>
