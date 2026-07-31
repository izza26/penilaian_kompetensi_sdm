<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body>
<table border="1" cellpadding="5" cellspacing="0">
    <thead>
        <tr><th colspan="9" style="font-size: 16px; font-weight: bold; text-align: center; background-color: #d1d5db;">REKAPITULASI HASIL PENILAIAN KOMPETENSI SDM</th></tr>
        <tr><th colspan="9" style="text-align: center; background-color: #f3f4f6;">Museum Geologi | Filter Jabatan/Periode: {{ $periode }}</th></tr>
        <tr><th colspan="9" style="text-align: left; font-style: italic;">Diunduh pada: {{ date('d M Y, H:i') }} WIB</th></tr>
        <tr></tr>
        <tr style="background-color: #cbd5e1; font-weight: bold; text-align: center;">
            <th width="50">No</th><th width="200">Nama Pegawai</th><th width="150">Jabatan</th><th width="120">Kode Unit</th><th width="350">Judul Unit Kompetensi</th><th width="100">Nilai Akhir (Skala 100)</th><th width="150">Kategori / Predikat</th><th width="200">Waktu Penilaian</th><th width="350">Catatan / Rekomendasi Pimpinan</th>
        </tr>
    </thead>
    <tbody>
        @forelse($list_data as $index => $row)
        <tr>
            <td style="text-align: center;">{{ $index + 1 }}</td>
            <td>{{ $row->pegawai_nama }}</td>
            <td>{{ $row->jabatan }}</td>
            <td style="text-align: center;">{{ $row->kode_unit }}</td>
            <td>{{ $row->judul_unit }}</td>
            <td style="text-align: center;">{{ number_format($row->nilai_akhir, 2) }}</td>
            <td style="text-align: center;">{{ $row->kategori }}</td>
            <td style="text-align: center;">{{ date('d/m/Y H:i', strtotime($row->waktu_submit)) }}</td>
            <td>{{ $row->rekomendasi }}</td>
        </tr>
        @empty
        <tr><td colspan="9" style="text-align: center; color: red;">Tidak ada data penilaian.</td></tr>
        @endforelse
    </tbody>
</table>
</body>
</html>