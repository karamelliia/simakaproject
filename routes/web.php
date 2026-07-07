<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ClassPromotionController;

/*
|--------------------------------------------------------------------------
| ADMIN CONTROLLERS
|--------------------------------------------------------------------------
*/
use App\Http\Controllers\Admin\DataSiswaController;
use App\Http\Controllers\Admin\DataGuruController;
use App\Http\Controllers\Admin\DataAdminController;
use App\Http\Controllers\Admin\DataSekolahController;
use App\Http\Controllers\Admin\DataTahunPelajaranController;
use App\Http\Controllers\Admin\DataJurusanController;
use App\Http\Controllers\Admin\DataKelasController;
use App\Http\Controllers\Admin\DataMapelController;
use App\Http\Controllers\Admin\DataPembelajaranController;
use App\Http\Controllers\Admin\DataEkstrakurikulerController;
use App\Http\Controllers\Admin\HariLiburController;
use App\Http\Controllers\Admin\AbsensiController as AdminAbsensiController;
use App\Http\Controllers\Admin\BkHubinAdminSettingController;
use App\Http\Controllers\Admin\AlumniController;
use App\Http\Controllers\Admin\TracerStudyController;
use App\Http\Controllers\Admin\StaffTuController;
use App\Http\Controllers\Admin\StudentExitController;
use App\Http\Controllers\Admin\TuPaymentItemController;
use App\Http\Controllers\TU\DashboardController as TuDashboardController;
use App\Http\Controllers\TU\PaymentChecklistController;

// KOKURIKULER
use App\Http\Controllers\Admin\KkDimensiController;
use App\Http\Controllers\Admin\KkKegiatanController;
use App\Http\Controllers\Admin\KkKelompokController;
use App\Http\Controllers\Admin\Kokurikuler\KelompokAnggotaController as AdminKelompokAnggotaController;
use App\Http\Controllers\Admin\Kokurikuler\KelompokKegiatanController as AdminKelompokKegiatanController;

// RAPOR (TANPA subfolder Rapor)
use App\Http\Controllers\Admin\LegerNilaiController;
use App\Http\Controllers\Admin\CetakRaporController;
use App\Http\Controllers\Admin\CetakRaporPerkembanganController;
use App\Http\Controllers\Admin\KelengkapanRaporPdfController;
use App\Http\Controllers\Admin\RaporPerkembanganPdfController;
use App\Http\Controllers\Admin\RaporSemesterPdfController;
use App\Http\Controllers\Admin\CapaianPembelajaranRaporController;
use App\Http\Controllers\Admin\CapaianPembelajaranPdfController;

/*
|--------------------------------------------------------------------------
| GURU CONTROLLERS
|--------------------------------------------------------------------------
*/
use App\Http\Controllers\Guru\PembelajaranController;
use App\Http\Controllers\Guru\NilaiController;
use App\Http\Controllers\Guru\TujuanPembelajaranController;
use App\Http\Controllers\Guru\NilaiAkhirController;
use App\Http\Controllers\Guru\DeskripsiCapaianController;
use App\Http\Controllers\Guru\CapaianPembelajaranController;
use App\Http\Controllers\Guru\DashboardController;
use App\Http\Controllers\Guru\AbsensiController as GuruAbsensiController;
use App\Http\Controllers\Guru\Kokurikuler\KelompokController;
use App\Http\Controllers\Guru\Kokurikuler\AnggotaKelompokController;
use App\Http\Controllers\Guru\Kokurikuler\CapaianAkhirController;
use App\Http\Controllers\Guru\Kokurikuler\DeskripsiKokurikulerController;
use App\Http\Controllers\Guru\WaliKelas\DataKelasController as GuruWaliDataKelasController;
use App\Http\Controllers\Guru\WaliKelas\ArsipSiswaController as GuruWaliArsipSiswaController;
use App\Http\Controllers\Guru\WaliKelas\StatusPembayaranController as GuruWaliStatusPembayaranController;
use App\Http\Controllers\Guru\WaliKelas\KetidakhadiranController;
use App\Http\Controllers\Guru\WaliKelas\AbsensiController as GuruWaliAbsensiController;
use App\Http\Controllers\Guru\WaliKelas\CatatanWaliKelasController;
use App\Http\Controllers\Guru\WaliKelas\HomeVisitController as GuruWaliHomeVisitController;
use App\Http\Controllers\Guru\WaliKelas\PengunduranDiriController as GuruWaliPengunduranDiriController;
use App\Http\Controllers\Guru\WaliKelas\PemanggilanOrangTuaController as GuruWaliPemanggilanOrangTuaController;
use App\Http\Controllers\Guru\Kokurikuler\KegiatanKelompokController;
use App\Http\Controllers\Guru\Kokurikuler\NilaiKokurikulerController;
use App\Http\Controllers\BK\DataBkController;
use App\Http\Controllers\BK\ModulBkController;
use App\Http\Controllers\BK\PelanggaranController;
use App\Http\Controllers\BK\RekapPelanggaranController;
use App\Http\Controllers\BK\PembinaanController;
use App\Http\Controllers\BK\HomeVisitController;
use App\Http\Controllers\BK\PemanggilanOrangTuaController;
use App\Http\Controllers\BK\PerjanjianSiswaController;
use App\Http\Controllers\BK\PengunduranDiriController;
use App\Http\Controllers\BK\PeminatanSiswaController;
use App\Http\Controllers\BK\SikapController;
use App\Http\Controllers\BK\AbsensiBulananController;
use App\Http\Controllers\BK\RekomendasiPklController;
use App\Http\Controllers\BK\HubinSkeletonController;

/*
|--------------------------------------------------------------------------
| ROOT
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
});

Route::middleware('auth')->get('/dashboard', function () {
    $user = auth()->user();

    if (!$user) {
        return redirect('/');
    }

    // Login melewati route pengarah ini sebelum sampai ke dashboard peran.
    // Pertahankan flash message agar masih tersedia pada request berikutnya.
    session()->keep('login_success');

    $role = $user->peran->nama_peran ?? null;

    if ($role === 'admin' || $user->hasRole('admin')) {
        return redirect()->route('admin.dashboard');
    }

    if ($role === 'guru_mapel' || $user->hasRole('guru_mapel')) {
        return redirect()->route('guru.dashboard');
    }

    if ($role === 'bk' || $user->hasRole('bk')) {
        return redirect()->route('bk.dashboard');
    }

    if ($role === 'staff_tu' || $user->hasRole('staff_tu')) {
        return redirect()->route('tu.dashboard');
    }

    return redirect('/')
        ->with('error', 'Akun Anda belum memiliki akses modul. Hubungi admin.');
})->name('dashboard');

/*
|--------------------------------------------------------------------------
| ADMIN AREA
|--------------------------------------------------------------------------
| Wajib: hanya admin yang boleh masuk /admin/*
*/
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::get('dashboard', function () {
            return view('admin.dashboard');
        })->name('dashboard');

        // MASTER DATA

        // ===== SISWA =====
        Route::get('siswa', [DataSiswaController::class, 'index'])->name('siswa.index');
            // ===== IMPORT SISWA (XLSX) =====
            Route::get('siswa/import/format', [DataSiswaController::class, 'downloadFormatImport'])
                ->name('siswa.import.format');

            Route::post('siswa/import', [DataSiswaController::class, 'import'])
                ->name('siswa.import');

            Route::get('siswa/import', [DataSiswaController::class, 'importCreate'])
                ->name('siswa.import.create');
            Route::get('siswa/export', [DataSiswaController::class, 'export'])
                ->name('siswa.export');

        Route::delete('siswa/destroy-multiple', [DataSiswaController::class, 'destroyMultiple'])
            ->name('siswa.destroyMultiple');
        // CRUD SISWA (taruh setelah import)
        Route::get('siswa/create', [DataSiswaController::class, 'create'])->name('siswa.create');
        Route::post('siswa', [DataSiswaController::class, 'store'])->name('siswa.store');
        Route::get('siswa/{id}', [DataSiswaController::class, 'show'])->name('siswa.show');
        Route::get('siswa/{id}/edit', [DataSiswaController::class, 'edit'])->name('siswa.edit');
        Route::put('siswa/{id}', [DataSiswaController::class, 'update'])->name('siswa.update');
        Route::delete('siswa/{id}', [DataSiswaController::class, 'destroy'])->name('siswa.destroy');

        Route::get('siswa-keluar', [StudentExitController::class, 'index'])->name('siswa-keluar.index');
        Route::post('siswa-keluar', [StudentExitController::class, 'store'])->name('siswa-keluar.store');

        Route::prefix('alumni')->name('alumni.')->group(function () {
            Route::get('/', [AlumniController::class, 'index'])->name('index');
            Route::get('kelulusan', [AlumniController::class, 'graduationForm'])->name('graduation');
            Route::post('kelulusan', [AlumniController::class, 'processGraduation'])->name('graduation.process');
            Route::get('pemetaan-lama', [AlumniController::class, 'legacyMappingForm'])->name('legacy-mapping');
            Route::post('pemetaan-lama', [AlumniController::class, 'storeLegacyMapping'])->name('legacy-mapping.store');
            Route::get('{alumni}/transkrip', [AlumniController::class, 'transcript'])->name('transcript');
            Route::get('{alumni}', [AlumniController::class, 'show'])->name('show');
        });

        


        // ===== GURU =====
        Route::get('guru', [DataGuruController::class, 'index'])->name('guru.index');
       
        // ===== IMPORT GURU (XLSX) =====
        Route::get('guru/import', [DataGuruController::class, 'importCreate'])->name('guru.import.create');
        Route::get('guru/import/format', [DataGuruController::class, 'downloadFormatImport'])->name('guru.import.format');
        Route::post('guru/import', [DataGuruController::class, 'import'])->name('guru.import');
        Route::get('guru/export', [DataGuruController::class, 'export'])->name('guru.export');

        // destroy multiple
        Route::delete('guru/destroy-multiple', [DataGuruController::class, 'destroyMultiple'])
            ->name('guru.destroyMultiple');

        // modal detail (AJAX)
        Route::get('guru/{id}/detail', [DataGuruController::class, 'detailModal'])->name('guru.detail.modal');

        // CRUD
        Route::get('guru/create', [DataGuruController::class, 'create'])->name('guru.create');
        Route::post('guru', [DataGuruController::class, 'store'])->name('guru.store');
        Route::get('guru/{id}', [DataGuruController::class, 'show'])->name('guru.show');
        Route::get('guru/{id}/edit', [DataGuruController::class, 'edit'])->name('guru.edit');
        Route::put('guru/{id}', [DataGuruController::class, 'update'])->name('guru.update');
        Route::delete('guru/{id}', [DataGuruController::class, 'destroy'])->name('guru.destroy');

        

       

       
       
       
       
        Route::resource('admin', DataAdminController::class);
        Route::resource('staff-tu', StaffTuController::class)->except(['show', 'destroy']);
        Route::resource('tu-payment-items', TuPaymentItemController::class)
            ->only(['index', 'store', 'update'])
            ->parameters(['tu-payment-items' => 'item']);

        // ADMINISTRASI
        Route::resource('sekolah', DataSekolahController::class)
            ->except(['show', 'destroy']);

        Route::put(
            'sekolah/{id}/logo',
            [DataSekolahController::class, 'updateLogo']
        )->name('sekolah.updateLogo');

        Route::get('kenaikan-kelas', [ClassPromotionController::class, 'index'])
            ->name('kenaikan-kelas.index');
        Route::post('kenaikan-kelas', [ClassPromotionController::class, 'store'])
            ->name('kenaikan-kelas.store');
        Route::put('kenaikan-kelas/{decision}/selesaikan-syarat', [ClassPromotionController::class, 'completeCondition'])
            ->name('kenaikan-kelas.syarat.complete');

        // TAHUN PELAJARAN
        Route::get('tahun-pelajaran', [DataTahunPelajaranController::class, 'index'])
            ->name('tahun.index');
        Route::post('tahun-pelajaran', [DataTahunPelajaranController::class, 'store'])
            ->name('tahun.store');
        Route::put('tahun-pelajaran/{id}', [DataTahunPelajaranController::class, 'update'])
            ->name('tahun.update');
        Route::put('tahun-pelajaran/{id}/aktif', [DataTahunPelajaranController::class, 'setAktif'])
            ->name('tahun.aktif');
        Route::get('pembelajaran/mapel-by-kelas/{kelas}', [DataPembelajaranController::class, 'mapelByKelas'])
            ->name('pembelajaran.mapelByKelas');

        // JURUSAN
        Route::get('jurusan', [DataJurusanController::class, 'index'])->name('jurusan.index');
        Route::post('jurusan', [DataJurusanController::class, 'store'])->name('jurusan.store');
        Route::put('jurusan/{id}', [DataJurusanController::class, 'update'])->name('jurusan.update');
        Route::delete('jurusan/{id}', [DataJurusanController::class, 'destroy'])->name('jurusan.destroy');

        // ADMINISTRASI LANJUTAN
        Route::get('kelas/export', [DataKelasController::class, 'export'])->name('kelas.export');
        Route::resource('kelas', DataKelasController::class);

        // MAPEL
        Route::get('mapel/import/format', [DataMapelController::class, 'downloadFormatImport'])
            ->name('mapel.import.format');
        Route::post('mapel/import', [DataMapelController::class, 'import'])
            ->name('mapel.import');

        Route::get('mapel/export', [DataMapelController::class, 'export'])
            ->name('mapel.export');
        Route::resource('mapel', DataMapelController::class);
        

        // PEMBELAJRAN
        Route::get('pembelajaran/import/format', [DataPembelajaranController::class, 'downloadFormatImport'])
            ->name('pembelajaran.import.format');
        Route::post('pembelajaran/import', [DataPembelajaranController::class, 'import'])
            ->name('pembelajaran.import');
        Route::get('pembelajaran/export', [DataPembelajaranController::class, 'export'])
            ->name('pembelajaran.export');
        Route::delete('pembelajaran/destroy-multiple', [DataPembelajaranController::class, 'destroyMultiple'])
            ->name('pembelajaran.destroyMultiple');
        Route::resource('pembelajaran', DataPembelajaranController::class);
        Route::get('pembelajaran/{id}/json', [DataPembelajaranController::class, 'json'])
            ->name('pembelajaran.json');

        // HARI LIBUR
        Route::get('hari-libur', [HariLiburController::class, 'index'])->name('hari-libur.index');
        Route::post('hari-libur', [HariLiburController::class, 'store'])->name('hari-libur.store');
        Route::post('hari-libur/generate-pemerintah', [HariLiburController::class, 'generatePemerintah'])->name('hari-libur.generate-pemerintah');
        Route::delete('hari-libur/{hariLibur}', [HariLiburController::class, 'destroy'])->name('hari-libur.destroy');

        Route::resource('ekstrakurikuler', DataEkstrakurikulerController::class);
        Route::get('ekstrakurikuler/{id}/json', [DataEkstrakurikulerController::class, 'json'])
            ->name('ekstrakurikuler.json');

        // ABSENSI (REKAP KETIDAKHADIRAN PER SEMESTER)
        Route::get('absensi', [AdminAbsensiController::class, 'index'])->name('absensi.index');
        Route::get('absensi/{kelas}/rekap', [AdminAbsensiController::class, 'rekap'])->name('absensi.rekap');
        Route::get('absensi-jadwal', [AdminAbsensiController::class, 'jadwal'])->name('absensi.jadwal');
        Route::get('absensi-jadwal/pengaturan-jam', [AdminAbsensiController::class, 'jamPelajaran'])->name('absensi.jadwal.jam');
        Route::put('absensi-jadwal/pengaturan-jam', [AdminAbsensiController::class, 'jamPelajaranStore'])->name('absensi.jadwal.jam.store');
        Route::get('absensi-jadwal/export/assignment', [AdminAbsensiController::class, 'jadwalExportAssignment'])->name('absensi.jadwal.export.assignment');
        Route::get('absensi-jadwal/import/format', [AdminAbsensiController::class, 'jadwalDownloadFormatImport'])->name('absensi.jadwal.import.format');
        Route::post('absensi-jadwal/import', [AdminAbsensiController::class, 'jadwalImport'])->name('absensi.jadwal.import');
        Route::post('absensi-jadwal', [AdminAbsensiController::class, 'jadwalStore'])->name('absensi.jadwal.store');
        Route::delete('absensi-jadwal/{id}', [AdminAbsensiController::class, 'jadwalDestroy'])->name('absensi.jadwal.destroy');

        // BK
        Route::get('bk-hubin-settings', [BkHubinAdminSettingController::class, 'index'])
            ->name('bk-hubin-settings.index');
        Route::put('bk-hubin-settings', [BkHubinAdminSettingController::class, 'update'])
            ->name('bk-hubin-settings.update');

        Route::prefix('hubin')->name('hubin.')->group(function () {
            Route::get('tracer-study', [TracerStudyController::class, 'index'])->name('tracer-study.index');
            Route::post('tracer-study', [TracerStudyController::class, 'store'])->name('tracer-study.store');
        });

        Route::prefix('bk')->name('bk.')->group(function () {
            Route::get('/', [DataBkController::class, 'index'])->name('index');
            Route::get('pdf', [DataBkController::class, 'pdf'])->name('pdf');
            Route::post('/', [DataBkController::class, 'store'])->name('store');
            Route::put('{bk}', [DataBkController::class, 'update'])->name('update');
            Route::delete('{bk}', [DataBkController::class, 'destroy'])->name('destroy');
            Route::get('riwayat/{siswa}', [DataBkController::class, 'riwayat'])->name('riwayat');

            Route::get('pelanggaran', [PelanggaranController::class, 'index'])->name('pelanggaran.index');
            Route::get('rekap-pelanggaran', [RekapPelanggaranController::class, 'index'])->name('rekap-pelanggaran.index');
            Route::get('rekap-pelanggaran/pdf', [RekapPelanggaranController::class, 'pdf'])->name('rekap-pelanggaran.pdf');
            Route::get('pelanggaran/jenis', [PelanggaranController::class, 'jenis'])->name('pelanggaran.jenis.index');
            Route::post('pelanggaran', [PelanggaranController::class, 'store'])->name('pelanggaran.store');
            Route::put('pelanggaran/{pelanggaran}', [PelanggaranController::class, 'update'])->name('pelanggaran.update');
            Route::delete('pelanggaran/{pelanggaran}', [PelanggaranController::class, 'destroy'])->name('pelanggaran.destroy');
            Route::post('pelanggaran/jenis', [PelanggaranController::class, 'storeJenis'])->name('pelanggaran.jenis.store');
            Route::put('pelanggaran/jenis/{jenis}', [PelanggaranController::class, 'updateJenis'])->name('pelanggaran.jenis.update');
            Route::delete('pelanggaran/jenis/{jenis}', [PelanggaranController::class, 'destroyJenis'])->name('pelanggaran.jenis.destroy');

            Route::get('pembinaan', [PembinaanController::class, 'index'])->name('pembinaan.index');
            Route::post('pembinaan', [PembinaanController::class, 'store'])->name('pembinaan.store');
            Route::put('pembinaan/{pembinaan}', [PembinaanController::class, 'update'])->name('pembinaan.update');
            Route::delete('pembinaan/{pembinaan}', [PembinaanController::class, 'destroy'])->name('pembinaan.destroy');

            Route::get('home-visit', [HomeVisitController::class, 'index'])->name('home-visit.index');
            Route::get('home-visit/pdf', [HomeVisitController::class, 'pdf'])->name('home-visit.pdf');
            Route::get('home-visit/{homeVisit}/bukti', [HomeVisitController::class, 'downloadBukti'])->name('home-visit.bukti');
            Route::post('home-visit', [HomeVisitController::class, 'store'])->name('home-visit.store');
            Route::put('home-visit/{homeVisit}', [HomeVisitController::class, 'update'])->name('home-visit.update');
            Route::delete('home-visit/{homeVisit}', [HomeVisitController::class, 'destroy'])->name('home-visit.destroy');

            Route::get('pengunduran-diri', [PengunduranDiriController::class, 'index'])->name('pengunduran-diri.index');
            Route::get('pengunduran-diri/pdf', [PengunduranDiriController::class, 'pdf'])->name('pengunduran-diri.pdf');
            Route::get('pengunduran-diri/{pengunduranDiri}/surat', [PengunduranDiriController::class, 'downloadSurat'])->name('pengunduran-diri.surat');
            Route::post('pengunduran-diri', [PengunduranDiriController::class, 'store'])->name('pengunduran-diri.store');
            Route::put('pengunduran-diri/{pengunduranDiri}', [PengunduranDiriController::class, 'update'])->name('pengunduran-diri.update');
            Route::delete('pengunduran-diri/{pengunduranDiri}', [PengunduranDiriController::class, 'destroy'])->name('pengunduran-diri.destroy');

            Route::get('perjanjian-siswa', [PerjanjianSiswaController::class, 'index'])->name('perjanjian-siswa.index');
            Route::post('perjanjian-siswa', [PerjanjianSiswaController::class, 'store'])->name('perjanjian-siswa.store');
            Route::put('perjanjian-siswa/{perjanjianSiswa}', [PerjanjianSiswaController::class, 'update'])->name('perjanjian-siswa.update');
            Route::delete('perjanjian-siswa/{perjanjianSiswa}', [PerjanjianSiswaController::class, 'destroy'])->name('perjanjian-siswa.destroy');

            Route::get('peminatan-siswa', [PeminatanSiswaController::class, 'index'])->name('peminatan.index');
            Route::post('peminatan-siswa', [PeminatanSiswaController::class, 'store'])->name('peminatan.store');
            Route::put('peminatan-siswa/{peminatanSiswa}', [PeminatanSiswaController::class, 'update'])->name('peminatan.update');
            Route::delete('peminatan-siswa/{peminatanSiswa}', [PeminatanSiswaController::class, 'destroy'])->name('peminatan.destroy');

            Route::get('absensi-bulanan', [AbsensiBulananController::class, 'index'])->name('absensi-bulanan.index');
            Route::get('rekomendasi-pkl', [RekomendasiPklController::class, 'index'])->name('rekomendasi-pkl.index');
            Route::get('rekomendasi-pkl/export', [RekomendasiPklController::class, 'export'])->name('rekomendasi-pkl.export');
            Route::get('rekomendasi-pkl/settings', [RekomendasiPklController::class, 'settings'])->name('rekomendasi-pkl.settings');
            Route::put('rekomendasi-pkl/settings', [RekomendasiPklController::class, 'updateSettings'])->name('rekomendasi-pkl.settings.update');
            Route::prefix('hubin')->name('hubin.')->group(function () {
                Route::get('data-dudi', [HubinSkeletonController::class, 'dataDudi'])->name('data-dudi.index');
                Route::post('data-dudi', [HubinSkeletonController::class, 'storeDudi'])->name('data-dudi.store');
                Route::put('data-dudi/{dudi}', [HubinSkeletonController::class, 'updateDudi'])->name('data-dudi.update');
                Route::delete('data-dudi/{dudi}', [HubinSkeletonController::class, 'destroyDudi'])->name('data-dudi.destroy');
                Route::get('penempatan-pkl', [HubinSkeletonController::class, 'penempatanPkl'])->name('penempatan-pkl.index');
                Route::post('penempatan-pkl', [HubinSkeletonController::class, 'storePenempatan'])->name('penempatan-pkl.store');
                Route::put('penempatan-pkl/{penempatan}', [HubinSkeletonController::class, 'updatePenempatan'])->name('penempatan-pkl.update');
                Route::delete('penempatan-pkl/{penempatan}', [HubinSkeletonController::class, 'destroyPenempatan'])->name('penempatan-pkl.destroy');
                Route::get('monitoring-pkl', [HubinSkeletonController::class, 'monitoringPkl'])->name('monitoring-pkl.index');
                Route::post('monitoring-pkl', [HubinSkeletonController::class, 'storeMonitoring'])->name('monitoring-pkl.store');
                Route::put('monitoring-pkl/{monitoring}', [HubinSkeletonController::class, 'updateMonitoring'])->name('monitoring-pkl.update');
                Route::delete('monitoring-pkl/{monitoring}', [HubinSkeletonController::class, 'destroyMonitoring'])->name('monitoring-pkl.destroy');
            });

            Route::get('pemanggilan-orang-tua', [PemanggilanOrangTuaController::class, 'index'])->name('pemanggilan-ortu.index');
            Route::get('pemanggilan-orang-tua/pdf', [PemanggilanOrangTuaController::class, 'pdf'])->name('pemanggilan-ortu.pdf');
            Route::post('pemanggilan-orang-tua', [PemanggilanOrangTuaController::class, 'store'])->name('pemanggilan-ortu.store');
            Route::put('pemanggilan-orang-tua/{pemanggilanOrtu}', [PemanggilanOrangTuaController::class, 'update'])->name('pemanggilan-ortu.update');
            Route::delete('pemanggilan-orang-tua/{pemanggilanOrtu}', [PemanggilanOrangTuaController::class, 'destroy'])->name('pemanggilan-ortu.destroy');
        });
        /*
        |----------------------------------------------------------------------
        | KOKURIKULER
        |----------------------------------------------------------------------
        */
        Route::prefix('kokurikuler')
            ->name('kokurikuler.')
            ->group(function () {
                Route::resource('dimensi', KkDimensiController::class);
                Route::resource('kegiatan', KkKegiatanController::class);
                Route::resource('kelompok', KkKelompokController::class);


                Route::get('kelompok/{kelompok}/anggota', [AdminKelompokAnggotaController::class, 'index'])
                    ->name('kelompok.anggota.index');
                Route::post('kelompok/{kelompok}/anggota', [AdminKelompokAnggotaController::class, 'store'])
                    ->name('kelompok.anggota.store');
                Route::delete('kelompok/{kelompok}/anggota/{anggota}', [AdminKelompokAnggotaController::class, 'destroy'])
                    ->name('kelompok.anggota.destroy');

                // OPTIONAL kalau mau tombol "Tambahkan Semua" beneran jalan
                Route::post('kelompok/{kelompok}/anggota/add-all', [AdminKelompokAnggotaController::class, 'addAll'])
                    ->name('kelompok.anggota.addAll');

                Route::get('kelompok/{kelompok}/kegiatan', [AdminKelompokKegiatanController::class, 'index'])
                    ->name('kelompok.kegiatan.index');
                Route::post('kelompok/{kelompok}/kegiatan', [AdminKelompokKegiatanController::class, 'store'])
                    ->name('kelompok.kegiatan.store');
                Route::delete('kelompok/{kelompok}/kegiatan/{pivot}', [AdminKelompokKegiatanController::class, 'destroy'])
                    ->name('kelompok.kegiatan.destroy');
            
            });

        /*
        |----------------------------------------------------------------------
        | RAPOR
        |----------------------------------------------------------------------
        */
        Route::prefix('rapor')
            ->name('rapor.')
            ->group(function () {

                // LEGER NILAI
                Route::get('leger', [LegerNilaiController::class, 'index'])
                    ->name('leger');
                Route::get('leger/{kelas}', [LegerNilaiController::class, 'detail'])
                    ->name('leger.detail');
                Route::get('leger', [LegerNilaiController::class, 'index'])->name('leger');
                Route::get('leger/{kelas}', [LegerNilaiController::class, 'detail'])->name('leger.detail');
                Route::get('leger/{kelas}/pdf', [LegerNilaiController::class, 'exportPdf'])->name('leger.pdf');
                Route::get('leger/{kelas}/excel', [LegerNilaiController::class, 'exportExcel'])->name('leger.excel');

                // CETAK RAPOR
                Route::get('cetak', [CetakRaporController::class, 'index'])
                    ->name('cetak');
                Route::get('cetak/{kelas}', [CetakRaporController::class, 'detail'])
                    ->name('cetak.detail');

                Route::get('perkembangan', [CetakRaporPerkembanganController::class, 'index'])
                    ->name('perkembangan');
                Route::get('perkembangan/{kelas}', [CetakRaporPerkembanganController::class, 'detail'])
                    ->name('perkembangan.detail');

                Route::get('capaian-pembelajaran', [CapaianPembelajaranRaporController::class, 'index'])
                    ->name('capaian-pembelajaran.index');
                Route::get('capaian-pembelajaran/{kelas}', [CapaianPembelajaranRaporController::class, 'detail'])
                    ->name('capaian-pembelajaran.detail');

                // PDF
                Route::get('pdf/kelengkapan/{siswa}', [KelengkapanRaporPdfController::class, 'show'])
                    ->name('pdf.kelengkapan');

                Route::get('pdf/semester/{siswa}', [RaporSemesterPdfController::class, 'show'])
                    ->name('pdf.semester');

                Route::get('pdf/perkembangan/{siswa}', [RaporPerkembanganPdfController::class, 'show'])
                    ->name('pdf.perkembangan');

                Route::get('pdf/capaian-pembelajaran/{siswa}', [CapaianPembelajaranPdfController::class, 'show'])
                    ->name('pdf.capaian-pembelajaran');
            });
    });

/*
|--------------------------------------------------------------------------
| GURU AREA
|--------------------------------------------------------------------------
| Minimal: hanya guru_mapel yang boleh masuk /guru/*
| (nanti bisa diperluas untuk multi-role wali_kelas/koordinator_p5/pembina_ekskul)
*/
Route::middleware(['auth', 'role:guru_mapel'])
    ->prefix('guru')
    ->name('guru.')
    ->group(function () {

        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('pembelajaran', [PembelajaranController::class, 'index'])
            ->name('pembelajaran.index');

        Route::get('nilai/{pembelajaran}', [NilaiController::class, 'index'])
            ->name('nilai.index');

        Route::post('nilai', [NilaiController::class, 'store'])
            ->name('nilai.store');

        // ABSENSI PER-JAM (GURU MAPEL)
        Route::get('absensi', [GuruAbsensiController::class, 'index'])->name('absensi.index');
        Route::get('absensi-rekap', [GuruAbsensiController::class, 'rekap'])->name('absensi.rekap');
        Route::get('absensi/{jadwal}/input', [GuruAbsensiController::class, 'input'])->name('absensi.input');
        Route::post('absensi/{jadwal}/input', [GuruAbsensiController::class, 'store'])->name('absensi.store');
        Route::get('rekomendasi-pkl', [RekomendasiPklController::class, 'index'])->name('rekomendasi-pkl.index');
        Route::get('rekomendasi-pkl/export', [RekomendasiPklController::class, 'export'])->name('rekomendasi-pkl.export');
        Route::prefix('hubin')->name('hubin.')->group(function () {
            Route::get('data-dudi', [HubinSkeletonController::class, 'dataDudi'])->name('data-dudi.index');
            Route::post('data-dudi', [HubinSkeletonController::class, 'storeDudi'])->name('data-dudi.store');
            Route::put('data-dudi/{dudi}', [HubinSkeletonController::class, 'updateDudi'])->name('data-dudi.update');
            Route::get('penempatan-pkl', [HubinSkeletonController::class, 'penempatanPkl'])->name('penempatan-pkl.index');
            Route::post('penempatan-pkl', [HubinSkeletonController::class, 'storePenempatan'])->name('penempatan-pkl.store');
            Route::put('penempatan-pkl/{penempatan}', [HubinSkeletonController::class, 'updatePenempatan'])->name('penempatan-pkl.update');
            Route::get('monitoring-pkl', [HubinSkeletonController::class, 'monitoringPkl'])->name('monitoring-pkl.index');
            Route::post('monitoring-pkl', [HubinSkeletonController::class, 'storeMonitoring'])->name('monitoring-pkl.store');
            Route::put('monitoring-pkl/{monitoring}', [HubinSkeletonController::class, 'updateMonitoring'])->name('monitoring-pkl.update');
        });

        Route::get('tujuan-pembelajaran/{pembelajaran}', [TujuanPembelajaranController::class, 'index'])
            ->name('tp.index');

        Route::post('tujuan-pembelajaran/{pembelajaran}', [TujuanPembelajaranController::class, 'store'])
            ->name('tp.store');

        Route::delete('tujuan-pembelajaran/hapus/{id}', [TujuanPembelajaranController::class, 'destroy'])
            ->name('tp.destroy');

        Route::get('nilai-akhir/{pembelajaran}', [NilaiAkhirController::class, 'index'])
            ->name('nilai_akhir.index');

        Route::post('nilai-akhir/{pembelajaran}', [NilaiAkhirController::class, 'update'])
            ->name('nilai_akhir.update');

        Route::post('nilai-akhir/{pembelajaran}/apply-average', [NilaiAkhirController::class, 'applyAverage'])
            ->name('nilai_akhir.applyAverage');

        Route::get('deskripsi-capaian/{pembelajaran}', [DeskripsiCapaianController::class, 'index'])
            ->name('deskripsi.index');

        Route::post('deskripsi-capaian/{pembelajaran}', [DeskripsiCapaianController::class, 'update'])
            ->name('deskripsi.update');

        Route::get('capaian-pembelajaran', [CapaianPembelajaranController::class, 'index'])
            ->name('capaian-pembelajaran.index');
        Route::get('capaian-pembelajaran/{pembelajaran}', [CapaianPembelajaranController::class, 'edit'])
            ->name('capaian-pembelajaran.edit');
        Route::post('capaian-pembelajaran/{pembelajaran}', [CapaianPembelajaranController::class, 'update'])
            ->name('capaian-pembelajaran.update');

        /*
        |----------------------------------------------------------
        | KOKURIKULER (SATU GROUP SAJA)
        |----------------------------------------------------------
        */
        Route::prefix('kokurikuler')->name('kokurikuler.')->group(function () {

            // (kalau punya route index kelompok, taruh di sini)
            Route::get('/', [KelompokController::class, 'index'])->name('index');

            // ANGGOTA
            Route::get('{kelompok}/anggota', [AnggotaKelompokController::class, 'index'])->name('anggota.index');
            Route::post('{kelompok}/anggota', [AnggotaKelompokController::class, 'store'])->name('anggota.store');
            Route::delete('{kelompok}/anggota/{anggota}', [AnggotaKelompokController::class, 'destroy'])->name('anggota.destroy');
            
            // KEGIATAN
            Route::get('{kelompok}/kegiatan', [KegiatanKelompokController::class, 'index'])->name('kegiatan.index');
            Route::post('{kelompok}/kegiatan', [KegiatanKelompokController::class, 'store'])->name('kegiatan.store');
            Route::delete('{kelompok}/kegiatan/{pivot}', [KegiatanKelompokController::class, 'destroy'])->name('kegiatan.destroy');
          
            // CAPAIAN AKHIR (PINDAH MASUK SINI)
            Route::get('{kelompok}/kegiatan/{pivot}/capaian-akhir', [CapaianAkhirController::class, 'index'])
                ->name('capaian_akhir.index');

            Route::post('{kelompok}/kegiatan/{pivot}/capaian-akhir', [CapaianAkhirController::class, 'store'])
                ->name('capaian_akhir.store');

            Route::put('{kelompok}/kegiatan/{pivot}/capaian-akhir/{id}', [CapaianAkhirController::class, 'update'])
                ->name('capaian_akhir.update');

            Route::delete('{kelompok}/kegiatan/{pivot}/capaian-akhir/{id}', [CapaianAkhirController::class, 'destroy'])
                ->name('capaian_akhir.destroy');

            // NILAI
            Route::get('kelompok/{kelompok}/kegiatan/{kegiatan}/nilai', [NilaiKokurikulerController::class, 'index'])
                ->name('nilai.index');

            Route::post('kelompok/{kelompok}/kegiatan/{kegiatan}/nilai', [NilaiKokurikulerController::class, 'update'])
                ->name('nilai.update');

            // DESKRIPSI KOKURIKULER (JANGAN GROUP KEDUA, TARUH DI SINI)
            Route::get('kelompok/{kelompok}/kegiatan/{kegiatan}/deskripsi', [DeskripsiKokurikulerController::class, 'index'])
                ->name('deskripsi.index');

            Route::post('kelompok/{kelompok}/kegiatan/{kegiatan}/deskripsi', [DeskripsiKokurikulerController::class, 'update'])
                ->name('deskripsi.update');
        });

        /*
        |----------------------------------------------------------
        | EKSKUL
        |----------------------------------------------------------
        */
        Route::prefix('ekskul')->name('ekskul.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Guru\EkskulController::class, 'index'])
                ->name('index');

            Route::get('{ekskul}/anggota', [\App\Http\Controllers\Guru\EkskulAnggotaController::class, 'index'])
                ->name('anggota.index');

            Route::post('{ekskul}/anggota', [\App\Http\Controllers\Guru\EkskulAnggotaController::class, 'store'])
                ->name('anggota.store');

            Route::post('{ekskul}/anggota/update', [\App\Http\Controllers\Guru\EkskulAnggotaController::class, 'update'])
                ->name('anggota.update');

            Route::delete('{ekskul}/anggota/{anggota}', [\App\Http\Controllers\Guru\EkskulAnggotaController::class, 'destroy'])
                ->name('anggota.destroy');
        });

        /*
        |----------------------------------------------------------
        | WALI KELAS
        |----------------------------------------------------------
        */
        Route::prefix('wali-kelas')->name('wali-kelas.')->group(function () {

        // DATA KELAS
        Route::get('data-kelas', [GuruWaliDataKelasController::class, 'index'])->name('data-kelas.index');
        Route::get('data-kelas/{kelas}', [GuruWaliDataKelasController::class, 'detail'])->name('data-kelas.detail');
        Route::get('arsip-siswa', [GuruWaliArsipSiswaController::class, 'index'])->name('arsip-siswa.index');
        Route::get('arsip-siswa/{siswa}', [GuruWaliArsipSiswaController::class, 'show'])->name('arsip-siswa.show');
        Route::get('status-pembayaran', [GuruWaliStatusPembayaranController::class, 'index'])
            ->name('status-pembayaran.index');

        Route::get('kenaikan-kelas', [ClassPromotionController::class, 'index'])->name('kenaikan-kelas.index');
        Route::post('kenaikan-kelas', [ClassPromotionController::class, 'store'])->name('kenaikan-kelas.store');
        Route::put('kenaikan-kelas/{decision}/selesaikan-syarat', [ClassPromotionController::class, 'completeCondition'])
            ->name('kenaikan-kelas.syarat.complete');

        // EDIT SISWA (dalam konteks kelas)
        Route::get('data-kelas/siswa/{id}/edit', [GuruWaliDataKelasController::class, 'editSiswa'])->name('data-kelas.siswa.edit');
        Route::put('data-kelas/siswa/{id}', [GuruWaliDataKelasController::class, 'updateSiswa'])->name('data-kelas.siswa.update');

            // KETIDAKHADIRAN
            Route::get('absensi', [GuruWaliAbsensiController::class, 'index'])->name('absensi.index');
            Route::get('absensi/{kelas}', [GuruWaliAbsensiController::class, 'kelola'])->name('absensi.kelola');
            Route::post('absensi/{kelas}', [GuruWaliAbsensiController::class, 'update'])->name('absensi.update');

            // kompatibilitas route lama
            Route::get('ketidakhadiran', [KetidakhadiranController::class, 'index'])->name('ketidakhadiran.index');
            Route::get('ketidakhadiran/{kelas}', [KetidakhadiranController::class, 'kelola'])->name('ketidakhadiran.kelola');
            Route::post('ketidakhadiran/{kelas}', [KetidakhadiranController::class, 'update'])->name('ketidakhadiran.update');

            // CATATAN
            Route::get('catatan', [CatatanWaliKelasController::class, 'index'])->name('catatan.index');
            Route::get('catatan/{kelas}', [CatatanWaliKelasController::class, 'kelola'])->name('catatan.kelola');
            Route::post('catatan/{kelas}', [CatatanWaliKelasController::class, 'update'])->name('catatan.update');

            // BK
            Route::get('bk', [DataBkController::class, 'index'])->name('bk.index');
            Route::get('bk/pdf', [DataBkController::class, 'pdf'])->name('bk.pdf');
            Route::post('bk', [DataBkController::class, 'store'])->name('bk.store');
            Route::get('bk/riwayat/{siswa}', [DataBkController::class, 'riwayat'])->name('bk.riwayat');
            Route::get('home-visit', [GuruWaliHomeVisitController::class, 'index'])->name('home-visit.index');
            Route::get('home-visit/form-download', [GuruWaliHomeVisitController::class, 'downloadForm'])->name('home-visit.form-download');
            Route::get('home-visit/{homeVisit}/bukti', [GuruWaliHomeVisitController::class, 'downloadBukti'])->name('home-visit.bukti');
            Route::post('home-visit', [GuruWaliHomeVisitController::class, 'store'])->name('home-visit.store');
            Route::put('home-visit/{homeVisit}', [GuruWaliHomeVisitController::class, 'update'])->name('home-visit.update');
            Route::delete('home-visit/{homeVisit}', [GuruWaliHomeVisitController::class, 'destroy'])->name('home-visit.destroy');
            Route::get('pengunduran-diri', [GuruWaliPengunduranDiriController::class, 'index'])->name('pengunduran-diri.index');
            Route::get('pengunduran-diri/{pengunduranDiri}/surat', [GuruWaliPengunduranDiriController::class, 'downloadSurat'])->name('pengunduran-diri.surat');
            Route::post('pengunduran-diri', [GuruWaliPengunduranDiriController::class, 'store'])->name('pengunduran-diri.store');
            Route::put('pengunduran-diri/{pengunduranDiri}', [GuruWaliPengunduranDiriController::class, 'update'])->name('pengunduran-diri.update');
            Route::delete('pengunduran-diri/{pengunduranDiri}', [GuruWaliPengunduranDiriController::class, 'destroy'])->name('pengunduran-diri.destroy');
            Route::get('pemanggilan-orang-tua', [GuruWaliPemanggilanOrangTuaController::class, 'index'])->name('pemanggilan-ortu.index');
            Route::post('pemanggilan-orang-tua', [GuruWaliPemanggilanOrangTuaController::class, 'store'])->name('pemanggilan-ortu.store');
            Route::put('pemanggilan-orang-tua/{pemanggilanOrtu}', [GuruWaliPemanggilanOrangTuaController::class, 'update'])->name('pemanggilan-ortu.update');
            Route::delete('pemanggilan-orang-tua/{pemanggilanOrtu}', [GuruWaliPemanggilanOrangTuaController::class, 'destroy'])->name('pemanggilan-ortu.destroy');
            Route::get('sikap', [SikapController::class, 'index'])->name('sikap.index');
            Route::post('sikap', [SikapController::class, 'store'])->name('sikap.store');
            Route::put('sikap/{sikap}', [SikapController::class, 'update'])->name('sikap.update');
            Route::delete('sikap/{sikap}', [SikapController::class, 'destroy'])->name('sikap.destroy');
            Route::get('rekomendasi-pkl', [RekomendasiPklController::class, 'index'])->name('rekomendasi-pkl.index');
            Route::get('rekomendasi-pkl/export', [RekomendasiPklController::class, 'export'])->name('rekomendasi-pkl.export');


            Route::prefix('rapor')->name('rapor.')->group(function () {

                // LEGER NILAI
                Route::get('leger', [\App\Http\Controllers\Guru\WaliKelas\Rapor\LegerNilaiController::class, 'index'])
                    ->name('leger.index');
                Route::get('leger/{kelas}', [\App\Http\Controllers\Guru\WaliKelas\Rapor\LegerNilaiController::class, 'detail'])
                    ->name('leger.detail');


                // CETAK RAPOR
                Route::get('cetak', [\App\Http\Controllers\Guru\WaliKelas\Rapor\CetakRaporController::class, 'index'])
                    ->name('cetak.index');

                Route::get('cetak/{kelas}', [\App\Http\Controllers\Guru\WaliKelas\Rapor\CetakRaporController::class, 'detail'])
                    ->name('cetak.detail');

                Route::get('pdf/kelengkapan/{siswa}', [\App\Http\Controllers\Guru\WaliKelas\Rapor\RaporKelengkapanPdfController::class, 'show'])
                    ->name('pdf.kelengkapan');

                Route::get('pdf/semester/{siswa}', [\App\Http\Controllers\Guru\WaliKelas\Rapor\RaporSemesterPdfController::class, 'show'])
                    ->name('pdf.semester');

                Route::get('perkembangan', [\App\Http\Controllers\Guru\WaliKelas\Rapor\CetakRaporPerkembanganController::class, 'index'])
                    ->name('perkembangan.index');

                Route::get('perkembangan/{kelas}', [\App\Http\Controllers\Guru\WaliKelas\Rapor\CetakRaporPerkembanganController::class, 'detail'])
                    ->name('perkembangan.detail');

                Route::get('pdf/perkembangan/{siswa}', [\App\Http\Controllers\Guru\WaliKelas\Rapor\RaporPerkembanganPdfController::class, 'show'])
                    ->name('pdf.perkembangan');
            });
        });
    });

Route::middleware(['auth', 'role:staff_tu'])
    ->prefix('tu')
    ->name('tu.')
    ->group(function () {
        Route::get('dashboard', [TuDashboardController::class, 'index'])->name('dashboard');
        Route::get('pembayaran', [PaymentChecklistController::class, 'index'])->name('payments.index');
        Route::post('pembayaran', [PaymentChecklistController::class, 'update'])->name('payments.update');
    });

Route::middleware(['auth', 'role:bk'])
    ->prefix('bk')
    ->name('bk.')
    ->group(function () {
        Route::get('dashboard', [ModulBkController::class, 'dashboard'])->name('dashboard');

        Route::get('data-bk', [DataBkController::class, 'index'])->name('data-bk.index');
        Route::get('data-bk/pdf', [DataBkController::class, 'pdf'])->name('data-bk.pdf');
        Route::post('data-bk', [DataBkController::class, 'store'])->name('data-bk.store');
        Route::put('data-bk/{bk}', [DataBkController::class, 'update'])->name('data-bk.update');
        Route::delete('data-bk/{bk}', [DataBkController::class, 'destroy'])->name('data-bk.destroy');
        Route::get('data-bk/riwayat/{siswa}', [DataBkController::class, 'riwayat'])->name('data-bk.riwayat');

        Route::get('pelanggaran', [PelanggaranController::class, 'index'])->name('pelanggaran.index');
        Route::get('rekap-pelanggaran', [RekapPelanggaranController::class, 'index'])->name('rekap-pelanggaran.index');
        Route::get('rekap-pelanggaran/pdf', [RekapPelanggaranController::class, 'pdf'])->name('rekap-pelanggaran.pdf');
        Route::get('pelanggaran/jenis', [PelanggaranController::class, 'jenis'])->name('pelanggaran.jenis.index');
        Route::post('pelanggaran', [PelanggaranController::class, 'store'])->name('pelanggaran.store');
        Route::put('pelanggaran/{pelanggaran}', [PelanggaranController::class, 'update'])->name('pelanggaran.update');
        Route::delete('pelanggaran/{pelanggaran}', [PelanggaranController::class, 'destroy'])->name('pelanggaran.destroy');
        Route::post('pelanggaran/jenis', [PelanggaranController::class, 'storeJenis'])->name('pelanggaran.jenis.store');
        Route::put('pelanggaran/jenis/{jenis}', [PelanggaranController::class, 'updateJenis'])->name('pelanggaran.jenis.update');
        Route::delete('pelanggaran/jenis/{jenis}', [PelanggaranController::class, 'destroyJenis'])->name('pelanggaran.jenis.destroy');
        Route::get('pembinaan', [PembinaanController::class, 'index'])->name('pembinaan.index');
        Route::post('pembinaan', [PembinaanController::class, 'store'])->name('pembinaan.store');
        Route::put('pembinaan/{pembinaan}', [PembinaanController::class, 'update'])->name('pembinaan.update');
        Route::delete('pembinaan/{pembinaan}', [PembinaanController::class, 'destroy'])->name('pembinaan.destroy');
        Route::get('home-visit', [HomeVisitController::class, 'index'])->name('home-visit.index');
        Route::get('home-visit/pdf', [HomeVisitController::class, 'pdf'])->name('home-visit.pdf');
        Route::get('home-visit/{homeVisit}/bukti', [HomeVisitController::class, 'downloadBukti'])->name('home-visit.bukti');
        Route::post('home-visit', [HomeVisitController::class, 'store'])->name('home-visit.store');
        Route::put('home-visit/{homeVisit}', [HomeVisitController::class, 'update'])->name('home-visit.update');
        Route::delete('home-visit/{homeVisit}', [HomeVisitController::class, 'destroy'])->name('home-visit.destroy');
        Route::get('pengunduran-diri', [PengunduranDiriController::class, 'index'])->name('pengunduran-diri.index');
        Route::get('pengunduran-diri/pdf', [PengunduranDiriController::class, 'pdf'])->name('pengunduran-diri.pdf');
        Route::get('pengunduran-diri/{pengunduranDiri}/surat', [PengunduranDiriController::class, 'downloadSurat'])->name('pengunduran-diri.surat');
        Route::post('pengunduran-diri', [PengunduranDiriController::class, 'store'])->name('pengunduran-diri.store');
        Route::put('pengunduran-diri/{pengunduranDiri}', [PengunduranDiriController::class, 'update'])->name('pengunduran-diri.update');
        Route::delete('pengunduran-diri/{pengunduranDiri}', [PengunduranDiriController::class, 'destroy'])->name('pengunduran-diri.destroy');
        Route::get('perjanjian-siswa', [PerjanjianSiswaController::class, 'index'])->name('perjanjian-siswa.index');
        Route::post('perjanjian-siswa', [PerjanjianSiswaController::class, 'store'])->name('perjanjian-siswa.store');
        Route::put('perjanjian-siswa/{perjanjianSiswa}', [PerjanjianSiswaController::class, 'update'])->name('perjanjian-siswa.update');
        Route::delete('perjanjian-siswa/{perjanjianSiswa}', [PerjanjianSiswaController::class, 'destroy'])->name('perjanjian-siswa.destroy');
        Route::get('peminatan-siswa', [PeminatanSiswaController::class, 'index'])->name('peminatan.index');
        Route::post('peminatan-siswa', [PeminatanSiswaController::class, 'store'])->name('peminatan.store');
        Route::put('peminatan-siswa/{peminatanSiswa}', [PeminatanSiswaController::class, 'update'])->name('peminatan.update');
        Route::delete('peminatan-siswa/{peminatanSiswa}', [PeminatanSiswaController::class, 'destroy'])->name('peminatan.destroy');
        Route::get('absensi-bulanan', [AbsensiBulananController::class, 'index'])->name('absensi-bulanan.index');
        Route::get('rekomendasi-pkl', [RekomendasiPklController::class, 'index'])->name('rekomendasi-pkl.index');
        Route::get('rekomendasi-pkl/export', [RekomendasiPklController::class, 'export'])->name('rekomendasi-pkl.export');
        Route::prefix('hubin')->name('hubin.')->group(function () {
            Route::get('data-dudi', [HubinSkeletonController::class, 'dataDudi'])->name('data-dudi.index');
            Route::post('data-dudi', [HubinSkeletonController::class, 'storeDudi'])->name('data-dudi.store');
            Route::put('data-dudi/{dudi}', [HubinSkeletonController::class, 'updateDudi'])->name('data-dudi.update');
            Route::delete('data-dudi/{dudi}', [HubinSkeletonController::class, 'destroyDudi'])->name('data-dudi.destroy');
            Route::get('penempatan-pkl', [HubinSkeletonController::class, 'penempatanPkl'])->name('penempatan-pkl.index');
            Route::post('penempatan-pkl', [HubinSkeletonController::class, 'storePenempatan'])->name('penempatan-pkl.store');
            Route::put('penempatan-pkl/{penempatan}', [HubinSkeletonController::class, 'updatePenempatan'])->name('penempatan-pkl.update');
            Route::delete('penempatan-pkl/{penempatan}', [HubinSkeletonController::class, 'destroyPenempatan'])->name('penempatan-pkl.destroy');
            Route::get('monitoring-pkl', [HubinSkeletonController::class, 'monitoringPkl'])->name('monitoring-pkl.index');
            Route::post('monitoring-pkl', [HubinSkeletonController::class, 'storeMonitoring'])->name('monitoring-pkl.store');
            Route::put('monitoring-pkl/{monitoring}', [HubinSkeletonController::class, 'updateMonitoring'])->name('monitoring-pkl.update');
            Route::delete('monitoring-pkl/{monitoring}', [HubinSkeletonController::class, 'destroyMonitoring'])->name('monitoring-pkl.destroy');
        });
        Route::get('pemanggilan-orang-tua', [PemanggilanOrangTuaController::class, 'index'])->name('pemanggilan-ortu.index');
        Route::get('pemanggilan-orang-tua/pdf', [PemanggilanOrangTuaController::class, 'pdf'])->name('pemanggilan-ortu.pdf');
        Route::post('pemanggilan-orang-tua', [PemanggilanOrangTuaController::class, 'store'])->name('pemanggilan-ortu.store');
        Route::put('pemanggilan-orang-tua/{pemanggilanOrtu}', [PemanggilanOrangTuaController::class, 'update'])->name('pemanggilan-ortu.update');
        Route::delete('pemanggilan-orang-tua/{pemanggilanOrtu}', [PemanggilanOrangTuaController::class, 'destroy'])->name('pemanggilan-ortu.destroy');
    });


/*
|--------------------------------------------------------------------------
| PROFILE
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');
    Route::patch('profile', [ProfileController::class, 'update'])
        ->name('profile.update');
    Route::delete('profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');
});

require __DIR__ . '/auth.php';
