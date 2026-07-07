<!doctype html><html lang="id"><head><meta charset="utf-8"><style>
body{font-family:DejaVu Sans,sans-serif;font-size:10px;color:#222}h2,h3,p{text-align:center;margin:3px}table{width:100%;border-collapse:collapse;margin-top:14px}th,td{border:1px solid #555;padding:6px}th{background:#e8edf3}.num{text-align:center}.meta{margin:12px 0 0}.footer{margin-top:14px;text-align:right;font-size:9px}
</style></head><body>
<h2>{{ $sekolah->nama_sekolah ?? 'SIMAKA' }}</h2><h3>REKAP PELANGGARAN SISWA</h3>
<p>Tahun Pelajaran: {{ $tahun->tahun_pelajaran ?? 'Semua' }} | Semester: {{ $filters['semester'] ?: ($tahun->semester ?? 'Semua') }}</p>
<div class="meta">Kelas: {{ $kelas->nama_kelas ?? 'Semua kelas' }} | Siswa: {{ $siswa->nama_siswa ?? 'Semua siswa' }} | Periode: {{ $filters['tanggal_dari'] ?: '-' }} s.d. {{ $filters['tanggal_sampai'] ?: '-' }}</div>
<table><thead><tr><th>No</th><th>NIS</th><th>Nama Siswa</th><th>Kelas</th><th>Jumlah Pelanggaran</th><th>Total Poin</th><th>Terakhir</th></tr></thead><tbody>
@forelse($rows as $i=>$row)<tr><td class="num">{{ $i+1 }}</td><td>{{ $row->siswa->nis ?? '-' }}</td><td>{{ $row->siswa->nama_siswa ?? '-' }}</td><td>{{ $row->siswa?->kelas?->nama_kelas ?? '-' }}</td><td class="num">{{ $row->jumlah_pelanggaran }}</td><td class="num">{{ $row->total_poin }}</td><td>{{ $row->pelanggaran_terakhir ? \Carbon\Carbon::parse($row->pelanggaran_terakhir)->format('d-m-Y') : '-' }}</td></tr>
@empty<tr><td colspan="7" class="num">Tidak ada data.</td></tr>@endforelse
</tbody></table><div class="footer">Dicetak: {{ $dibuatPada->format('d-m-Y H:i') }}</div></body></html>
