<?php

namespace App\Http\Controllers\BK;

use App\Http\Controllers\Controller;
use App\Models\BkSikapSiswa;
use App\Models\DataKelas;
use App\Models\DataSiswa;
use App\Models\DataTahunPelajaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class SikapController extends Controller
{
    public function index(Request $request)
    {
        $limit = (int) $request->get('limit', 10);
        if (!in_array($limit, [10, 25, 50, 100], true)) {
            $limit = 10;
        }

        $q = trim((string) $request->get('q', ''));
        $kelasId = $request->get('kelas_id');
        $predikat = trim((string) $request->get('predikat', ''));
        $status = trim((string) $request->get('status', ''));
        $tanggalDari = $request->get('tanggal_dari');
        $tanggalSampai = $request->get('tanggal_sampai');
        $kelasIds = $this->accessibleKelasIds();
        $kelasId = $this->sanitizeKelasFilter($kelasId, $kelasIds);

        $baseQuery = BkSikapSiswa::query()
            ->with(['siswa', 'kelas'])
            ->when($this->isWaliContext(), fn($builder) => $builder->whereIn('data_kelas_id', $kelasIds))
            ->when($q !== '', function ($builder) use ($q) {
                $builder->where(function ($w) use ($q) {
                    $w->whereHas('siswa', function ($sQuery) use ($q) {
                        $sQuery->where('nama_siswa', 'like', "%{$q}%")
                            ->orWhere('nis', 'like', "%{$q}%")
                            ->orWhere('nisn', 'like', "%{$q}%");
                    })
                        ->orWhere('aspek_sikap', 'like', "%{$q}%")
                        ->orWhere('catatan', 'like', "%{$q}%");
                });
            })
            ->when($kelasId, fn($builder) => $builder->where('data_kelas_id', $kelasId))
            ->when($predikat !== '', fn($builder) => $builder->where('predikat', $predikat))
            ->when($status !== '', fn($builder) => $builder->where('status', $status))
            ->when($tanggalDari, fn($builder) => $builder->whereDate('tanggal_penilaian', '>=', $tanggalDari))
            ->when($tanggalSampai, fn($builder) => $builder->whereDate('tanggal_penilaian', '<=', $tanggalSampai));

        $sikap = (clone $baseQuery)
            ->latest('tanggal_penilaian')
            ->latest('id')
            ->paginate($limit)
            ->withQueryString();

        $kelasOptions = DataKelas::query()
            ->when($this->isWaliContext(), fn($builder) => $builder->whereIn('id', $kelasIds))
            ->orderBy('nama_kelas')
            ->get();

        $siswaOptions = DataSiswa::with('kelas')
            ->when($this->isWaliContext(), fn($builder) => $builder->whereIn('data_kelas_id', $kelasIds))
            ->orderBy('nama_siswa')
            ->get();

        $predikatOptions = BkSikapSiswa::predikatOptions();
        $statusOptions = BkSikapSiswa::statusOptions();

        $predikatCounts = [];
        foreach ($predikatOptions as $p) {
            $predikatCounts[$p] = (clone $baseQuery)->where('predikat', $p)->count();
        }

        return view('bk.sikap.index', [
            'sikap' => $sikap,
            'kelasOptions' => $kelasOptions,
            'siswaOptions' => $siswaOptions,
            'predikatOptions' => $predikatOptions,
            'statusOptions' => $statusOptions,
            'predikatCounts' => $predikatCounts,
            'limit' => $limit,
            'q' => $q,
            'kelasId' => $kelasId,
            'predikat' => $predikat,
            'status' => $status,
            'tanggalDari' => $tanggalDari,
            'tanggalSampai' => $tanggalSampai,
            'routeBase' => $this->routeBase(),
            'isWaliContext' => $this->isWaliContext(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'data_siswa_id' => 'required|exists:data_siswa,id',
            'tanggal_penilaian' => 'required|date',
            'aspek_sikap' => 'required|string|max:120',
            'predikat' => 'required|in:' . implode(',', BkSikapSiswa::predikatOptions()),
            'status' => 'required|in:' . implode(',', BkSikapSiswa::statusOptions()),
            'catatan' => 'nullable|string',
            'tindak_lanjut' => 'nullable|string',
        ]);

        $siswa = DataSiswa::findOrFail($validated['data_siswa_id']);
        $this->assertSiswaAccessible($siswa);
        $tahunAktif = DataTahunPelajaran::where('status_aktif', 1)->firstOrFail();

        BkSikapSiswa::create([
            'data_siswa_id' => $siswa->id,
            'data_kelas_id' => $siswa->data_kelas_id,
            'data_tahun_pelajaran_id' => $tahunAktif->id,
            'semester' => $tahunAktif->semester,
            'tanggal_penilaian' => $validated['tanggal_penilaian'],
            'aspek_sikap' => $validated['aspek_sikap'],
            'predikat' => $validated['predikat'],
            'status' => $validated['status'],
            'catatan' => $validated['catatan'] ?? null,
            'tindak_lanjut' => $validated['tindak_lanjut'] ?? null,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        return redirect()->route($this->routeBase() . '.sikap.index')
            ->with('success', 'Data sikap siswa berhasil ditambahkan.');
    }

    public function update(Request $request, BkSikapSiswa $sikap)
    {
        $this->assertSikapAccessible($sikap);

        $validated = $request->validate([
            'data_siswa_id' => 'required|exists:data_siswa,id',
            'tanggal_penilaian' => 'required|date',
            'aspek_sikap' => 'required|string|max:120',
            'predikat' => 'required|in:' . implode(',', BkSikapSiswa::predikatOptions()),
            'status' => 'required|in:' . implode(',', BkSikapSiswa::statusOptions()),
            'catatan' => 'nullable|string',
            'tindak_lanjut' => 'nullable|string',
        ]);

        $siswa = DataSiswa::findOrFail($validated['data_siswa_id']);
        $this->assertSiswaAccessible($siswa);
        $tahunAktif = DataTahunPelajaran::where('status_aktif', 1)->first();

        $sikap->update([
            'data_siswa_id' => $siswa->id,
            'data_kelas_id' => $siswa->data_kelas_id,
            'data_tahun_pelajaran_id' => $tahunAktif?->id ?? $sikap->data_tahun_pelajaran_id,
            'semester' => $tahunAktif?->semester ?? $sikap->semester,
            'tanggal_penilaian' => $validated['tanggal_penilaian'],
            'aspek_sikap' => $validated['aspek_sikap'],
            'predikat' => $validated['predikat'],
            'status' => $validated['status'],
            'catatan' => $validated['catatan'] ?? null,
            'tindak_lanjut' => $validated['tindak_lanjut'] ?? null,
            'updated_by' => Auth::id(),
        ]);

        return redirect()->route($this->routeBase() . '.sikap.index')
            ->with('success', 'Data sikap siswa berhasil diperbarui.');
    }

    public function destroy(BkSikapSiswa $sikap)
    {
        $this->assertSikapAccessible($sikap);
        $sikap->delete();

        return redirect()->route($this->routeBase() . '.sikap.index')
            ->with('success', 'Data sikap siswa berhasil dihapus.');
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

        return 'bk';
    }

    private function isWaliContext(): bool
    {
        $name = request()->route()?->getName() ?? '';

        return str_starts_with($name, 'guru.wali-kelas.');
    }

    private function accessibleKelasIds(): array
    {
        if (!$this->isWaliContext()) {
            return [];
        }

        return DataKelas::where('wali_kelas_id', Auth::id())
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->all();
    }

    private function sanitizeKelasFilter($kelasId, array $kelasIds): ?string
    {
        if (!$this->isWaliContext() || !$kelasId) {
            return $kelasId ?: null;
        }

        return in_array((int) $kelasId, $kelasIds, true) ? (string) $kelasId : null;
    }

    private function assertSiswaAccessible(DataSiswa $siswa): void
    {
        if (!$this->isWaliContext()) {
            return;
        }

        if (!in_array((int) $siswa->data_kelas_id, $this->accessibleKelasIds(), true)) {
            throw ValidationException::withMessages([
                'data_siswa_id' => 'Siswa tidak termasuk kelas yang Anda wali.',
            ]);
        }
    }

    private function assertSikapAccessible(BkSikapSiswa $sikap): void
    {
        if (!$this->isWaliContext()) {
            return;
        }

        if (!in_array((int) $sikap->data_kelas_id, $this->accessibleKelasIds(), true)) {
            abort(403);
        }
    }
}
