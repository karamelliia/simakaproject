<?php

namespace App\Http\Controllers\BK;

use App\Http\Controllers\Controller;
use App\Models\BkPelanggaranSiswa;
use App\Models\DataKelas;
use App\Models\DataSekolah;
use App\Models\DataSiswa;
use App\Models\DataTahunPelajaran;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class RekapPelanggaranController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $baseQuery = $this->baseQuery($filters);

        $rekap = (clone $baseQuery)
            ->selectRaw('data_siswa_id, COUNT(*) as jumlah_pelanggaran, SUM(poin) as total_poin, MAX(tanggal) as pelanggaran_terakhir')
            ->with('siswa.kelas')
            ->groupBy('data_siswa_id')
            ->orderByDesc('total_poin')
            ->orderByDesc('jumlah_pelanggaran')
            ->paginate(25)
            ->withQueryString();

        return view('bk.rekap', [
            'rekap' => $rekap,
            'statistik' => [
                'jumlah_siswa' => (clone $baseQuery)->distinct()->count('data_siswa_id'),
                'jumlah_pelanggaran' => (clone $baseQuery)->count(),
                'total_poin' => (int) (clone $baseQuery)->sum('poin'),
            ],
            'filters' => $filters,
            'tahunOptions' => DataTahunPelajaran::query()
                ->orderByDesc('tahun_pelajaran')
                ->orderBy('semester')
                ->get(),
            'kelasOptions' => DataKelas::orderBy('nama_kelas')->get(),
            'siswaOptions' => DataSiswa::orderBy('nama_siswa')->get(),
            'routeBase' => $this->routeBase(),
        ]);
    }

    public function pdf(Request $request)
    {
        $filters = $this->filters($request);
        $rows = $this->baseQuery($filters)
            ->selectRaw('data_siswa_id, COUNT(*) as jumlah_pelanggaran, SUM(poin) as total_poin, MAX(tanggal) as pelanggaran_terakhir')
            ->with('siswa.kelas')
            ->groupBy('data_siswa_id')
            ->orderByDesc('total_poin')
            ->orderByDesc('jumlah_pelanggaran')
            ->get();

        return Pdf::loadView('bk.rekap_pelanggaran_pdf', [
            'rows' => $rows,
            'sekolah' => DataSekolah::first(),
            'tahun' => $filters['tahun_id'] ? DataTahunPelajaran::find($filters['tahun_id']) : null,
            'kelas' => $filters['kelas_id'] ? DataKelas::find($filters['kelas_id']) : null,
            'siswa' => $filters['siswa_id'] ? DataSiswa::find($filters['siswa_id']) : null,
            'filters' => $filters,
            'dibuatPada' => now(),
        ])->setPaper('a4', 'landscape')->stream('rekap-pelanggaran-siswa.pdf');
    }

    private function filters(Request $request): array
    {
        return [
            'tahun_id' => $request->filled('tahun_id') ? (int) $request->input('tahun_id') : null,
            'semester' => trim((string) $request->input('semester', '')),
            'kelas_id' => $request->filled('kelas_id') ? (int) $request->input('kelas_id') : null,
            'siswa_id' => $request->filled('siswa_id') ? (int) $request->input('siswa_id') : null,
            'tanggal_dari' => $request->input('tanggal_dari'),
            'tanggal_sampai' => $request->input('tanggal_sampai'),
        ];
    }

    private function baseQuery(array $filters)
    {
        return BkPelanggaranSiswa::query()
            ->when($filters['tahun_id'], fn ($query, $id) => $query->where('data_tahun_pelajaran_id', $id))
            ->when($filters['semester'] !== '', fn ($query) => $query->where('semester', $filters['semester']))
            ->when($filters['kelas_id'], fn ($query, $id) => $query->where('data_kelas_id', $id))
            ->when($filters['siswa_id'], fn ($query, $id) => $query->where('data_siswa_id', $id))
            ->when($filters['tanggal_dari'], fn ($query, $date) => $query->whereDate('tanggal', '>=', $date))
            ->when($filters['tanggal_sampai'], fn ($query, $date) => $query->whereDate('tanggal', '<=', $date));
    }

    private function routeBase(): string
    {
        return str_starts_with(request()->route()?->getName() ?? '', 'admin.')
            ? 'admin.bk.rekap-pelanggaran'
            : 'bk.rekap-pelanggaran';
    }
}
