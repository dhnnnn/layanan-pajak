<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Tunggakan - {{ $employee->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11px; color: #1a1a1a; background: #fff; margin: 40px 70px 35px 90px; }

        .page-header { text-align: center; margin-bottom: 16px; padding-bottom: 10px; border-bottom: 2px solid #1a1a1a; }
        .page-header h2 { font-size: 14px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
        .page-header p { font-size: 11px; color: #333; margin-top: 2px; }

        /* Summary: 2 kolom x 2 baris */
        .summary { display: table; width: 100%; margin-bottom: 18px; border-collapse: collapse; }
        .summary-row { display: table-row; }
        .summary-card { display: table-cell; width: 50%; border: 1px solid #999; padding: 8px 12px; vertical-align: top; }
        .summary-card .label { font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; color: #555; }
        .summary-card .value { font-size: 13px; font-weight: bold; margin-top: 2px; color: #1a1a1a; }

        .wp-card { border: 1px solid #999; margin-bottom: 10px; overflow: hidden; page-break-inside: avoid; }
        .wp-card-header { background: #f0f0f0; padding: 7px 10px; border-bottom: 1px solid #999; display: flex; justify-content: space-between; align-items: flex-start; }
        .wp-name { font-weight: bold; font-size: 11px; color: #1a1a1a; }
        .wp-meta { font-size: 9px; color: #444; margin-top: 2px; }
        .wp-badge { font-size: 10px; font-weight: bold; color: #1a1a1a; text-align: right; }
        .wp-badge .label { font-size: 9px; color: #555; font-weight: normal; }

        .wp-stats { display: flex; padding: 7px 10px; gap: 20px; border-bottom: 1px solid #ddd; }
        .wp-stat .s-label { font-size: 9px; color: #555; text-transform: uppercase; }
        .wp-stat .s-value { font-size: 11px; font-weight: bold; margin-top: 1px; color: #1a1a1a; }

        .monthly-title { padding: 5px 10px 3px; font-size: 9px; font-weight: bold; text-transform: uppercase; color: #444; letter-spacing: 0.5px; }
        .monthly-grid { display: flex; flex-wrap: wrap; gap: 4px; padding: 0 10px 8px; }
        .month-item { border: 1px solid #bbb; padding: 4px 8px; min-width: 80px; }
        .month-item.has-tunggakan { background: #f5f5f5; border-color: #888; }
        .month-item.lunas { background: #fff; border-color: #bbb; }
        .month-item .m-name { font-size: 9px; font-weight: bold; color: #333; }
        .month-item .m-ket { font-size: 9px; color: #555; }
        .month-item .m-byr { font-size: 9px; font-weight: bold; color: #1a1a1a; }

        .no-data { text-align: center; padding: 20px; color: #555; font-style: italic; }

        @media print {
            body { font-size: 10px; }
            .wp-card { page-break-inside: avoid; }
        }
    </style>
</head>
<body>

<div class="page-header">
    <h2>Daftar Tunggakan Wajib Pajak</h2>
    <p>Petugas: <strong>{{ $employee->name }}</strong> &mdash; {{ $upt->name }} &mdash; Tahun {{ $year }}</p>
    <p>Wilayah: {{ $employee->districts->pluck('name')->implode(', ') }}</p>
</div>

<div class="summary">
    <div class="summary-row">
        <div class="summary-card">
            <div class="label">Total Ketetapan</div>
            <div class="value">Rp {{ number_format($summary['total_sptpd'], 0, ',', '.') }}</div>
        </div>
        <div class="summary-card">
            <div class="label">Total Pembayaran</div>
            <div class="value">Rp {{ number_format($summary['total_bayar'], 0, ',', '.') }}</div>
        </div>
    </div>
    <div class="summary-row">
        <div class="summary-card">
            <div class="label">Total Tunggakan</div>
            <div class="value">Rp {{ number_format($summary['total_tunggakan'], 0, ',', '.') }}</div>
        </div>
        <div class="summary-card">
            <div class="label">WP Bermasalah</div>
            <div class="value">{{ $wpList->count() }} WP</div>
        </div>
    </div>
</div>
</div>

@forelse($wpList as $i => $wp)
    @php
        $key = $wp->npwpd . '|' . $wp->nop;
        $monthly = $monthlyData->get($key, collect())->keyBy('month');
        $months = [1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'Mei',6=>'Jun',7=>'Jul',8=>'Agu',9=>'Sep',10=>'Okt',11=>'Nov',12=>'Des'];
    @endphp
    <div class="wp-card">
        <div class="wp-card-header">
            <div>
                <div class="wp-name">{{ $i + 1 }}. {{ $wp->nm_wp }}</div>
                <div class="wp-meta">NPWPD: {{ $wp->npwpd }} &bull; {{ $wp->jenis_pajak ?? $wp->ayat }} &bull; {{ $wp->kd_kecamatan }}</div>
            </div>
            <div class="wp-badge">
                <div class="label">Tunggakan</div>
                Rp {{ number_format($wp->total_tunggakan, 0, ',', '.') }}
            </div>
        </div>

        <div class="wp-stats">
            <div class="wp-stat">
                <div class="s-label">Ketetapan</div>
                <div class="s-value">Rp {{ number_format($wp->total_ketetapan, 0, ',', '.') }}</div>
            </div>
            <div class="wp-stat">
                <div class="s-label">Terbayar</div>
                <div class="s-value">Rp {{ number_format($wp->total_bayar, 0, ',', '.') }}</div>
            </div>
            <div class="wp-stat">
                <div class="s-label">Progress</div>
                <div class="s-value">{{ $wp->total_ketetapan > 0 ? number_format(($wp->total_bayar / $wp->total_ketetapan) * 100, 1) : 0 }}%</div>
            </div>
        </div>

        @if($monthly->isNotEmpty())
        <div class="monthly-title">Rincian per Bulan</div>
        <div class="monthly-grid">
            @foreach($months as $m => $label)
                @if($monthly->has($m))
                    @php $md = $monthly->get($m); $hasTunggakan = $md->total_tunggakan > 0; @endphp
                    <div class="month-item {{ $hasTunggakan ? 'has-tunggakan' : 'lunas' }}">
                        <div class="m-name">{{ $label }}</div>
                        <div class="m-ket">Rp {{ number_format($md->total_ketetapan, 0, ',', '.') }}</div>
                        <div class="m-byr">{{ $hasTunggakan ? 'Rp ' . number_format($md->total_bayar, 0, ',', '.') : 'Lunas' }}</div>
                    </div>
                @endif
            @endforeach
        </div>
        @endif
    </div>
@empty
    <div class="no-data">Tidak ada WP dengan tunggakan untuk petugas ini.</div>
@endforelse

</body>
</html>
