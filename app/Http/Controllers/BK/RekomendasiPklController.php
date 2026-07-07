<?php

namespace App\Http\Controllers\BK;

use App\Http\Controllers\Controller;
use App\Models\DataKelas;
use App\Models\DataSiswa;
use App\Models\DataTahunPelajaran;
use App\Models\HubinRekomendasiPklSetting;
use App\Services\RekomendasiPklService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RekomendasiPklController extends Controller
{
    public function __construct(private readonly RekomendasiPklService $recommendationService)
    {
    }

    public function index(Request $request)
    {
        $roleContext = $this->roleContext();
        $this->authorizeAccess($roleContext);
        $allowedKelasIds = $this->allowedKelasIds($roleContext);

        $limit = (int) $request->get('limit', 25);
        if (!in_array($limit, [10, 25, 50, 100], true)) {
            $limit = 25;
        }

        $q = trim((string) $request->get('q', ''));
        $kelasId = $request->get('kelas_id');
        $tingkat = strtoupper(trim((string) $request->get('tingkat', '')));
        $grade = strtoupper(trim((string) $request->get('grade', '')));

        if (!in_array($tingkat, ['X', 'XI', 'XII'], true)) {
            $tingkat = '';
        }

        if (!in_array($grade, ['A', 'B', 'C', 'D', 'E'], true)) {
            $grade = '';
        }

        $tahunAktif = DataTahunPelajaran::where('status_aktif', 1)->first();
        $weights = $this->recommendationService->weights();

        $siswaQuery = DataSiswa::query()
            ->with('kelas')
            ->whereNotNull('data_kelas_id')
            ->whereRaw("UPPER(COALESCE(status_siswa, 'AKTIF')) = 'AKTIF'")
            ->when($allowedKelasIds !== null, fn($builder) => $builder->whereIn('data_kelas_id', $allowedKelasIds))
            ->when($kelasId, fn($builder) => $builder->where('data_kelas_id', (int) $kelasId))
            ->when($tingkat !== '', function ($builder) use ($tingkat) {
                $builder->whereHas('kelas', function ($kelasQuery) use ($tingkat) {
                    $kelasQuery->whereIn('tingkat', $this->tingkatCandidates($tingkat));
                });
            })
            ->when($q !== '', function ($builder) use ($q) {
                $builder->where(function ($w) use ($q) {
                    $w->where('nama_siswa', 'like', "%{$q}%")
                        ->orWhere('nis', 'like', "%{$q}%")
                        ->orWhere('nisn', 'like', "%{$q}%");
                });
            })
            ->orderBy('nama_siswa');

        if ($grade === '') {
            $statsRows = collect();
            (clone $siswaQuery)
                ->reorder('id')
                ->chunkById(200, function (Collection $siswaChunk) use ($tahunAktif, &$statsRows) {
                    $statsRows = $statsRows->concat($this->buildRows($siswaChunk, $tahunAktif));
                });

            $siswaPage = $siswaQuery->paginate($limit)->withQueryString();
            $rows = $this->buildRows($siswaPage->getCollection(), $tahunAktif)
                ->sortBy([
                    ['nilai_akhir', 'desc'],
                    ['nama_siswa', 'asc'],
                ])
                ->values();

            $stats = [
                'A' => $statsRows->where('grade_pkl', 'A')->count(),
                'B' => $statsRows->where('grade_pkl', 'B')->count(),
                'C' => $statsRows->where('grade_pkl', 'C')->count(),
                'D' => $statsRows->where('grade_pkl', 'D')->count(),
                'E' => $statsRows->where('grade_pkl', 'E')->count(),
                'belum' => $statsRows->whereNull('grade_pkl')->count(),
                'total' => $siswaPage->total(),
            ];

            $paged = new LengthAwarePaginator(
                $rows,
                $siswaPage->total(),
                $siswaPage->perPage(),
                $siswaPage->currentPage(),
                [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]
            );
        } else {
            $rows = collect();
            $siswaQuery
                ->reorder('id')
                ->chunkById(200, function (Collection $siswaChunk) use ($tahunAktif, $grade, &$rows) {
                    $filteredRows = $this->buildRows($siswaChunk, $tahunAktif)
                        ->filter(fn(array $row) => $row['grade_pkl'] === $grade)
                        ->values();

                    $rows = $rows->concat($filteredRows);
                });

            $rows = $rows->sortBy([
                ['nilai_akhir', 'desc'],
                ['nama_siswa', 'asc'],
            ])->values();

            $stats = [
                'A' => $rows->where('grade_pkl', 'A')->count(),
                'B' => $rows->where('grade_pkl', 'B')->count(),
                'C' => $rows->where('grade_pkl', 'C')->count(),
                'D' => $rows->where('grade_pkl', 'D')->count(),
                'E' => $rows->where('grade_pkl', 'E')->count(),
                'belum' => 0,
                'total' => $rows->count(),
            ];

            $paged = $this->paginateCollection($rows, $limit, $request);
        }

        $kelasOptions = DataKelas::query()
            ->when($allowedKelasIds !== null, fn($builder) => $builder->whereIn('id', $allowedKelasIds))
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('data_siswa')
                    ->whereColumn('data_siswa.data_kelas_id', 'data_kelas.id')
                    ->whereRaw("UPPER(COALESCE(data_siswa.status_siswa, 'AKTIF')) = 'AKTIF'");
            })
            ->orderBy('tingkat')
            ->orderBy('nama_kelas')
            ->get();

        return view('bk.rekomendasi_pkl.index', [
            'rows' => $paged,
            'stats' => $stats,
            'tahunAktif' => $tahunAktif,
            'routeBase' => $this->routeBase(),
            'weights' => $weights,
            'thresholds' => $this->recommendationService->gradeThresholds(),
            'q' => $q,
            'kelasId' => $kelasId,
            'tingkat' => $tingkat,
            'grade' => $grade,
            'limit' => $limit,
            'kelasOptions' => $kelasOptions,
        ]);
    }

    public function export(Request $request)
    {
        $roleContext = $this->roleContext();
        $this->authorizeAccess($roleContext);
        $allowedKelasIds = $this->allowedKelasIds($roleContext);

        $q = trim((string) $request->get('q', ''));
        $kelasId = $request->get('kelas_id');
        $tingkat = strtoupper(trim((string) $request->get('tingkat', '')));
        $grade = strtoupper(trim((string) $request->get('grade', '')));

        if (!in_array($tingkat, ['X', 'XI', 'XII'], true)) {
            $tingkat = '';
        }

        if (!in_array($grade, ['A', 'B', 'C', 'D', 'E'], true)) {
            $grade = '';
        }

        $tahunAktif = DataTahunPelajaran::where('status_aktif', 1)->first();

        $siswaQuery = DataSiswa::query()
            ->with('kelas')
            ->whereNotNull('data_kelas_id')
            ->whereRaw("UPPER(COALESCE(status_siswa, 'AKTIF')) = 'AKTIF'")
            ->when($allowedKelasIds !== null, fn($builder) => $builder->whereIn('data_kelas_id', $allowedKelasIds))
            ->when($kelasId, fn($builder) => $builder->where('data_kelas_id', (int) $kelasId))
            ->when($tingkat !== '', function ($builder) use ($tingkat) {
                $builder->whereHas('kelas', function ($kelasQuery) use ($tingkat) {
                    $kelasQuery->whereIn('tingkat', $this->tingkatCandidates($tingkat));
                });
            })
            ->when($q !== '', function ($builder) use ($q) {
                $builder->where(function ($w) use ($q) {
                    $w->where('nama_siswa', 'like', "%{$q}%")
                        ->orWhere('nis', 'like', "%{$q}%")
                        ->orWhere('nisn', 'like', "%{$q}%");
                });
            });

        $filename = 'rekomendasi-pkl-' . date('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($siswaQuery, $tahunAktif, $grade) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($out, ['Rekomendasi PKL']);
            fputcsv($out, [
                'Tahun Pelajaran',
                $tahunAktif?->tahun_pelajaran ?? '-',
                'Semester',
                $tahunAktif?->semester ?? '-',
            ]);
            fputcsv($out, []);

            fputcsv($out, [
                'No',
                'Nama Siswa',
                'Kelas',
                'Persentase Kehadiran',
                'Sikap Terakhir',
                'Poin BK Aktif',
                'Penalti BK',
                'Nilai Dasar',
                'Nilai Akhir',
                'Grade PKL',
                'Rekomendasi',
                'Catatan Pembatas',
            ]);

            $no = 1;
            $siswaQuery
                ->orderBy('id')
                ->chunkById(200, function (Collection $siswaChunk) use ($out, $tahunAktif, $grade, &$no) {
                    $rows = $this->buildRows($siswaChunk, $tahunAktif);
                    if ($grade !== '') {
                        $rows = $rows->filter(fn(array $row) => $row['grade_pkl'] === $grade)->values();
                    }

                    foreach ($rows as $row) {
                        fputcsv($out, [
                            $no++,
                            $row['nama_siswa'],
                            $row['kelas'],
                            $row['persentase_kehadiran'] === null ? '-' : number_format($row['persentase_kehadiran'], 2) . '%',
                            $row['sikap_terakhir'],
                            $row['poin_bk'],
                            $row['penalti_bk'],
                            $row['nilai_dasar'] === null ? '-' : number_format($row['nilai_dasar'], 2),
                            $row['nilai_akhir'] === null ? '-' : number_format($row['nilai_akhir'], 2),
                            $row['grade_pkl'] ?? '-',
                            $row['grade_label'],
                            $row['batas_grade'],
                        ]);
                    }
                });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function settings()
    {
        $this->ensureAdminContext();

        $cfg = $this->recommendationService->runtimeConfig();
        $weights = $cfg['weights'] ?? [];
        $thresholds = $cfg['grade_thresholds'] ?? [];

        return view('bk.rekomendasi_pkl.settings', [
            'weights' => [
                'kehadiran' => (float) ($weights['kehadiran'] ?? 50),
                'sikap' => (float) ($weights['sikap'] ?? 30),
            ],
            'thresholds' => [
                'A' => (float) ($thresholds['A'] ?? 85),
                'B' => (float) ($thresholds['B'] ?? 75),
                'C' => (float) ($thresholds['C'] ?? 65),
                'D' => (float) ($thresholds['D'] ?? 50),
            ],
        ]);
    }

    public function updateSettings(Request $request)
    {
        $this->ensureAdminContext();

        $validated = $request->validate([
            'weight_kehadiran' => 'required|numeric|min:0|max:100',
            'weight_sikap' => 'required|numeric|min:0|max:100',
            'grade_a' => 'required|numeric|min:0|max:100',
            'grade_b' => 'required|numeric|min:0|max:100',
            'grade_c' => 'required|numeric|min:0|max:100',
            'grade_d' => 'required|numeric|min:0|max:100',
        ]);

        if (
            (float) $validated['grade_a'] < (float) $validated['grade_b'] ||
            (float) $validated['grade_b'] < (float) $validated['grade_c'] ||
            (float) $validated['grade_c'] < (float) $validated['grade_d']
        ) {
            return back()
                ->withErrors(['grade_a' => 'Urutan grade harus A >= B >= C >= D.'])
                ->withInput();
        }

        if (!Schema::hasTable('hubin_rekomendasi_pkl_settings')) {
            return back()->with('error', 'Tabel pengaturan belum tersedia. Jalankan migration terlebih dahulu.');
        }

        $row = HubinRekomendasiPklSetting::query()->first();
        if (!$row) {
            $row = new HubinRekomendasiPklSetting();
        }

        $row->weights = [
            'kehadiran' => (float) $validated['weight_kehadiran'],
            'sikap' => (float) $validated['weight_sikap'],
            'bk' => 0,
        ];
        $row->grade_thresholds = [
            'A' => (float) $validated['grade_a'],
            'B' => (float) $validated['grade_b'],
            'C' => (float) $validated['grade_c'],
            'D' => (float) $validated['grade_d'],
        ];
        $row->updated_by = Auth::id();
        $row->save();

        $this->recommendationService->clearConfigCache();

        return redirect()
            ->route('admin.bk.rekomendasi-pkl.settings')
            ->with('success', 'Pengaturan rekomendasi PKL berhasil diperbarui.');
    }

    private function buildRows(Collection $siswa, ?DataTahunPelajaran $tahunAktif): Collection
    {
        if ($siswa->isEmpty()) {
            return collect();
        }

        $siswaIds = $siswa->pluck('id')->all();
        $tahunId = $tahunAktif?->id;
        $semester = $tahunAktif?->semester;

        $absensiBySiswa = $this->attendanceSummary($siswaIds, $tahunId, $semester);
        $rekapKetidakhadiran = $this->rekapKetidakhadiran($siswaIds, $tahunId, $semester);
        $latestSikap = $this->latestSikap($siswaIds, $tahunId, $semester);
        $bkPointMap = $this->bkPointMap($siswaIds, $tahunId);

        $estimatedSchoolDays = $this->estimateSchoolDaysInSemester($tahunAktif);

        return $siswa->map(function (DataSiswa $item) use (
            $absensiBySiswa,
            $rekapKetidakhadiran,
            $latestSikap,
            $bkPointMap,
            $estimatedSchoolDays
        ) {
            $attendance = $this->recommendationService->summarizeAttendance(
                $absensiBySiswa->get($item->id, collect()),
                $rekapKetidakhadiran->get($item->id),
                $estimatedSchoolDays
            );

            $sikap = $latestSikap->get($item->id);
            $predikat = $sikap->predikat ?? '-';
            $bk = $bkPointMap->get($item->id, [
                'historical_points' => 0,
                'active_points' => 0,
                'has_heavy' => false,
                'has_terminal' => false,
            ]);
            $result = $this->recommendationService->calculate(
                $attendance,
                $predikat,
                (int) $bk['active_points'],
                (bool) $bk['has_heavy'],
                (bool) $bk['has_terminal']
            );

            return [
                'nama_siswa' => $item->nama_siswa,
                'kelas' => $item->kelas?->nama_kelas ?? '-',
                'persentase_kehadiran' => $result['attendance_percentage'],
                'jumlah_alfa' => $result['attendance_counts']['A'],
                'sikap_terakhir' => $predikat,
                'poin_bk' => $result['active_bk_points'],
                'poin_bk_historis' => (int) $bk['historical_points'],
                'penalti_bk' => $result['bk_penalty'],
                'nilai_dasar' => $result['base_score'],
                'grade_pkl' => $result['grade'],
                'grade_label' => $result['label'],
                'nilai_akhir' => $result['final_score'],
                'batas_grade' => $result['cap_reason'],
                'is_terminal' => $result['is_terminal'],
                'is_complete' => $result['is_complete'],
            ];
        });
    }

    private function attendanceSummary(array $siswaIds, ?int $tahunId, ?string $semester): Collection
    {
        if (empty($siswaIds)) {
            return collect();
        }

        $dailyRows = DB::table('absensi_jam_siswa')
            ->select('data_siswa_id', 'tanggal', 'status')
            ->when($tahunId, fn($builder) => $builder->where('data_tahun_pelajaran_id', $tahunId))
            ->when($semester, fn($builder) => $builder->where('semester', $semester))
            ->whereIn('data_siswa_id', $siswaIds)
            ->get();

        return $dailyRows->groupBy('data_siswa_id');
    }

    private function rekapKetidakhadiran(array $siswaIds, ?int $tahunId, ?string $semester): Collection
    {
        if (empty($siswaIds)) {
            return collect();
        }

        return DB::table('data_ketidakhadiran')
            ->select('data_siswa_id', 'sakit', 'izin', 'tanpa_keterangan')
            ->when($tahunId, fn($builder) => $builder->where('data_tahun_pelajaran_id', $tahunId))
            ->when($semester, fn($builder) => $builder->where('semester', $semester))
            ->whereIn('data_siswa_id', $siswaIds)
            ->get()
            ->keyBy('data_siswa_id');
    }

    private function latestSikap(array $siswaIds, ?int $tahunId, ?string $semester): Collection
    {
        if (empty($siswaIds)) {
            return collect();
        }

        $latestIds = DB::table('bk_sikap_siswa')
            ->selectRaw('MAX(id) as id')
            ->when($tahunId, fn($builder) => $builder->where('data_tahun_pelajaran_id', $tahunId))
            ->when($semester, fn($builder) => $builder->where('semester', $semester))
            ->whereIn('data_siswa_id', $siswaIds)
            ->groupBy('data_siswa_id');

        return DB::table('bk_sikap_siswa')
            ->select('data_siswa_id', 'predikat')
            ->whereIn('id', $latestIds)
            ->get()
            ->keyBy('data_siswa_id');
    }

    private function bkPointMap(array $siswaIds, ?int $tahunId): Collection
    {
        if (empty($siswaIds)) {
            return collect();
        }

        $rows = DB::table('bk_pelanggaran_siswa')
            ->join('bk_jenis_pelanggaran', 'bk_jenis_pelanggaran.id', '=', 'bk_pelanggaran_siswa.bk_jenis_pelanggaran_id')
            ->select([
                'bk_pelanggaran_siswa.data_siswa_id',
                'bk_pelanggaran_siswa.data_tahun_pelajaran_id',
                'bk_pelanggaran_siswa.tanggal',
                'bk_pelanggaran_siswa.poin',
                'bk_jenis_pelanggaran.kategori',
                'bk_jenis_pelanggaran.is_terminal',
                'bk_jenis_pelanggaran.affects_pkl_score',
            ])
            ->whereIn('bk_pelanggaran_siswa.data_siswa_id', $siswaIds)
            ->get()
            ->groupBy('data_siswa_id');

        return collect($siswaIds)->mapWithKeys(function ($siswaId) use ($rows, $tahunId) {
            return [
                $siswaId => $this->recommendationService->summarizeBkPoints(
                    $rows->get($siswaId, collect()),
                    $tahunId
                ),
            ];
        });
    }

    private function estimateSchoolDaysInSemester(?DataTahunPelajaran $tahunAktif): int
    {
        [$start, $end] = $this->semesterRange($tahunAktif);
        if (!$start || !$end) {
            return 110;
        }

        $cursor = $start;
        $count = 0;
        while ($cursor->lte($end)) {
            if ($cursor->isWeekday()) {
                $count++;
            }
            $cursor = $cursor->addDay();
        }

        return max($count, 1);
    }

    private function semesterRange(?DataTahunPelajaran $tahunAktif): array
    {
        if (!$tahunAktif) {
            return [null, null];
        }

        [$thAwal, $thAkhir] = $this->parseTahunPelajaran((string) $tahunAktif->tahun_pelajaran);
        if ($tahunAktif->semester === 'Ganjil') {
            return [
                CarbonImmutable::create($thAwal, 7, 1),
                CarbonImmutable::create($thAwal, 12, 31),
            ];
        }

        return [
            CarbonImmutable::create($thAkhir, 1, 1),
            CarbonImmutable::create($thAkhir, 6, 30),
        ];
    }

    private function parseTahunPelajaran(string $tahunPelajaran): array
    {
        if (preg_match('/^(\d{4})\s*\/\s*(\d{4})$/', $tahunPelajaran, $match)) {
            return [(int) $match[1], (int) $match[2]];
        }

        $now = (int) date('Y');
        return [$now, $now + 1];
    }

    private function paginateCollection(Collection $items, int $perPage, Request $request): LengthAwarePaginator
    {
        $page = max((int) $request->get('page', 1), 1);
        $total = $items->count();
        $slice = $items->forPage($page, $perPage)->values();

        return new LengthAwarePaginator(
            $slice,
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
    }

    private function routeBase(): string
    {
        $name = request()->route()?->getName() ?? '';
        if (str_starts_with($name, 'admin.')) {
            return 'admin.bk';
        }
        if (str_starts_with($name, 'guru.wali-kelas.')) {
            return 'guru.wali-kelas';
        }
        if (str_starts_with($name, 'guru.')) {
            return 'guru';
        }
        return 'bk';
    }

    private function roleContext(): string
    {
        $name = request()->route()?->getName() ?? '';
        if (str_starts_with($name, 'admin.')) {
            return 'admin';
        }
        if (str_starts_with($name, 'guru.wali-kelas.')) {
            return 'wali';
        }
        if (str_starts_with($name, 'guru.')) {
            return 'guru';
        }
        return 'bk';
    }

    private function allowedKelasIds(string $roleContext): ?array
    {
        if ($roleContext === 'wali') {
            return $this->waliKelasIds(Auth::id());
        }

        if ($roleContext !== 'guru') {
            return null;
        }

        $user = Auth::user();
        if (!$user) {
            return [];
        }

        $globalRoles = config('rekomendasi_pkl.roles.guru_global_access', ['pembimbing_pkl']);
        $cfgRoles = $this->runtimeConfig()['roles'] ?? [];
        $globalRoles = $cfgRoles['guru_global_access'] ?? $globalRoles;
        if ($this->userHasAnyRole($user, $globalRoles)) {
            return null;
        }

        $limitedRoles = $cfgRoles['guru_limited_access'] ?? config('rekomendasi_pkl.roles.guru_limited_access', ['wali_kelas']);
        if ($this->userHasAnyRole($user, $limitedRoles) || $this->isWaliKelasUser($user->id)) {
            return $this->waliKelasIds($user->id);
        }

        return [];
    }

    private function tingkatCandidates(string $tingkat): array
    {
        return match ($tingkat) {
            'X' => ['X', '10'],
            'XI' => ['XI', '11'],
            'XII' => ['XII', '12'],
            default => [$tingkat],
        };
    }

    private function authorizeAccess(string $roleContext): void
    {
        if (in_array($roleContext, ['admin', 'bk', 'wali'], true)) {
            return;
        }

        if ($roleContext !== 'guru') {
            return;
        }

        $user = Auth::user();
        if (!$user) {
            abort(403);
        }

        $cfgRoles = $this->runtimeConfig()['roles'] ?? [];
        $globalRoles = $cfgRoles['guru_global_access'] ?? config('rekomendasi_pkl.roles.guru_global_access', ['pembimbing_pkl']);
        $limitedRoles = $cfgRoles['guru_limited_access'] ?? config('rekomendasi_pkl.roles.guru_limited_access', ['wali_kelas']);

        if (
            $this->userHasAnyRole($user, $globalRoles) ||
            $this->userHasAnyRole($user, $limitedRoles) ||
            $this->isWaliKelasUser($user->id)
        ) {
            return;
        }

        abort(403, 'Anda tidak memiliki akses ke rekomendasi PKL.');
    }

    private function userHasAnyRole($user, array $roles): bool
    {
        foreach ($roles as $role) {
            if (method_exists($user, 'hasRole') && $user->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    private function isWaliKelasUser(int $userId): bool
    {
        return DataKelas::where('wali_kelas_id', $userId)->exists();
    }

    private function waliKelasIds(int $userId): array
    {
        return DataKelas::query()
            ->where('wali_kelas_id', $userId)
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->all();
    }

    private function runtimeConfig(): array
    {
        return $this->recommendationService->runtimeConfig();
    }

    private function ensureAdminContext(): void
    {
        $name = request()->route()?->getName() ?? '';
        if (!str_starts_with($name, 'admin.')) {
            abort(403);
        }
    }
}
