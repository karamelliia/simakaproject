@extends('layouts.adminlte')

@section('title', 'Rekap Absensi Mapel')
@section('page_title', 'Rekap Absensi Mapel')

@push('styles')
<style>
  .teacher-attendance-report .summary-box {
    height: 100%;
    padding: 1rem;
    border: 1px solid #dce5eb;
    border-radius: 0.75rem;
    background: #fff;
  }

  .teacher-attendance-report .summary-value {
    color: var(--simaka-primary);
    font-size: 1.6rem;
    font-weight: 800;
    line-height: 1;
  }

  .teacher-attendance-report .status-counts {
    display: grid;
    grid-template-columns: repeat(4, minmax(48px, 1fr));
    gap: 0.35rem;
  }

  .teacher-attendance-report .status-count {
    padding: 0.4rem;
    border-radius: 0.4rem;
    text-align: center;
    font-weight: 700;
  }

  .teacher-attendance-report .status-h { color: #216e4e; background: #e4f4ec; }
  .teacher-attendance-report .status-s { color: #8a6116; background: #fff4d6; }
  .teacher-attendance-report .status-i { color: #236482; background: #e5f3fa; }
  .teacher-attendance-report .status-a { color: #963f3f; background: #fae8e8; }

  @media (max-width: 767.98px) {
    .teacher-attendance-report {
      padding-right: 0;
      padding-left: 0;
    }

    .teacher-attendance-report .filter-actions {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 0.5rem;
    }

    .teacher-attendance-report .report-table,
    .teacher-attendance-report .report-table tbody,
    .teacher-attendance-report .report-table tr,
    .teacher-attendance-report .report-table td {
      display: block;
      width: 100%;
    }

    .teacher-attendance-report .report-table thead {
      display: none;
    }

    .teacher-attendance-report .report-table tbody {
      padding: 0.75rem;
    }

    .teacher-attendance-report .report-table tr {
      margin-bottom: 0.75rem;
      padding: 0.9rem;
      border: 1px solid #dce5eb;
      border-radius: 0.75rem;
      background: #fff;
      box-shadow: 0 0.25rem 0.75rem rgba(35, 67, 89, 0.07);
    }

    .teacher-attendance-report .report-table td {
      padding: 0.3rem 0 !important;
      border: 0;
    }

    .teacher-attendance-report .report-table .student-name {
      font-size: 1rem;
      font-weight: 700;
    }
  }
</style>
@endpush

@section('content')
<div class="container-fluid teacher-attendance-report">
  <div class="card">
    <div class="card-body">
      <form method="GET" action="{{ route('guru.absensi.rekap') }}">
        <div class="form-row align-items-end">
          <div class="col-lg-2 col-md-4">
            <div class="form-group">
              <label for="periode">Periode</label>
              <select name="periode" id="periode" class="form-control">
                <option value="bulan_ini" @selected($periode === 'bulan_ini')>Bulan ini</option>
                <option value="3_bulan" @selected($periode === '3_bulan')>3 bulan terakhir</option>
                <option value="semester" @selected($periode === 'semester')>Semester berjalan</option>
                <option value="custom" @selected($periode === 'custom')>Rentang khusus</option>
              </select>
            </div>
          </div>
          <div class="col-lg-2 col-md-4">
            <div class="form-group">
              <label for="tanggal_mulai">Dari Tanggal</label>
              <input type="date" name="tanggal_mulai" id="tanggal_mulai"
                     class="form-control" value="{{ $tanggalMulai }}">
            </div>
          </div>
          <div class="col-lg-2 col-md-4">
            <div class="form-group">
              <label for="tanggal_selesai">Sampai Tanggal</label>
              <input type="date" name="tanggal_selesai" id="tanggal_selesai"
                     class="form-control" value="{{ $tanggalSelesai }}">
            </div>
          </div>
          <div class="col-lg-2 col-md-4">
            <div class="form-group">
              <label for="kelas_id">Kelas</label>
              <select name="kelas_id" id="kelas_id" class="form-control">
                <option value="">Semua kelas</option>
                @foreach($kelasOptions as $kelas)
                  <option value="{{ $kelas->id }}" @selected((int) $kelasId === (int) $kelas->id)>
                    {{ $kelas->nama_kelas }}
                  </option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="col-lg-2 col-md-4">
            <div class="form-group">
              <label for="mapel_id">Mata Pelajaran</label>
              <select name="mapel_id" id="mapel_id" class="form-control">
                <option value="">Semua mapel</option>
                @foreach($mapelOptions as $mapel)
                  <option value="{{ $mapel->id }}" @selected((int) $mapelId === (int) $mapel->id)>
                    {{ $mapel->nama_mapel }}
                  </option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="col-lg-2 col-md-4">
            <div class="form-group filter-actions">
              <button class="btn btn-primary">
                <i class="fas fa-filter mr-1"></i> Tampilkan
              </button>
              <a href="{{ route('guru.absensi.rekap') }}" class="btn btn-light">Reset</a>
            </div>
          </div>
        </div>
      </form>
      <small class="text-muted">
        Periode aktif: {{ \Carbon\Carbon::parse($tanggalMulai)->translatedFormat('d F Y') }}
        sampai {{ \Carbon\Carbon::parse($tanggalSelesai)->translatedFormat('d F Y') }}.
      </small>
    </div>
  </div>

  <div class="row mb-3">
    <div class="col-6 col-lg-2 mb-3 mb-lg-0">
      <div class="summary-box">
        <div class="summary-value">{{ (int) ($totals->pertemuan ?? 0) }}</div>
        <small class="text-muted">Pertemuan</small>
      </div>
    </div>
    <div class="col-6 col-lg-2 mb-3 mb-lg-0">
      <div class="summary-box">
        <div class="summary-value">{{ (int) ($totals->siswa ?? 0) }}</div>
        <small class="text-muted">Siswa tercatat</small>
      </div>
    </div>
    @foreach([
      ['H', 'Hadir', 'hadir', 'status-h'],
      ['S', 'Sakit', 'sakit', 'status-s'],
      ['I', 'Izin', 'izin', 'status-i'],
      ['A', 'Alpa', 'alpa', 'status-a'],
    ] as [$code, $label, $field, $class])
      <div class="col-6 col-lg-2 mb-3 mb-lg-0">
        <div class="summary-box">
          <div class="summary-value">{{ (int) ($totals->{$field} ?? 0) }}</div>
          <small class="text-muted">{{ $label }} ({{ $code }})</small>
        </div>
      </div>
    @endforeach
  </div>

  <div class="card">
    <div class="card-header">
      <h3 class="card-title">Rekap per Siswa dan Mata Pelajaran</h3>
    </div>
    <div class="card-body table-responsive p-0">
      <table class="table table-bordered table-hover mb-0 report-table">
        <thead>
          <tr>
            <th style="width: 55px;">No.</th>
            <th>Nama Siswa</th>
            <th>Kelas</th>
            <th>Mapel</th>
            <th style="width: 110px;">Pertemuan</th>
            <th style="width: 260px;">H / S / I / A</th>
            <th style="width: 110px;">Kehadiran</th>
          </tr>
        </thead>
        <tbody>
          @forelse($rekap as $index => $row)
            @php
              $persentase = $row->total_absensi > 0
                  ? round(($row->hadir / $row->total_absensi) * 100, 1)
                  : 0;
            @endphp
            <tr>
              <td>{{ $rekap->firstItem() + $index }}</td>
              <td class="student-name">
                {{ $row->nama_siswa }}
                <small class="d-block text-muted">NIS: {{ $row->nis ?: '-' }}</small>
              </td>
              <td>{{ $row->nama_kelas }}</td>
              <td>{{ $row->nama_mapel }}</td>
              <td>{{ $row->total_pertemuan }}</td>
              <td>
                <div class="status-counts">
                  <span class="status-count status-h">H {{ $row->hadir }}</span>
                  <span class="status-count status-s">S {{ $row->sakit }}</span>
                  <span class="status-count status-i">I {{ $row->izin }}</span>
                  <span class="status-count status-a">A {{ $row->alpa }}</span>
                </div>
              </td>
              <td>
                <strong>{{ number_format($persentase, 1, ',', '.') }}%</strong>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center text-muted py-4">
                Belum ada data absensi yang Anda input pada periode ini.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($rekap->hasPages())
      <div class="card-footer d-flex flex-wrap justify-content-between align-items-center">
        <span class="text-muted small">
          Menampilkan {{ $rekap->firstItem() }} - {{ $rekap->lastItem() }} dari {{ $rekap->total() }} rekap
        </span>
        {{ $rekap->links('pagination::bootstrap-4') }}
      </div>
    @endif
  </div>
</div>
@endsection

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    var periode = document.getElementById('periode');
    var mulai = document.getElementById('tanggal_mulai');
    var selesai = document.getElementById('tanggal_selesai');

    function toggleCustomDates() {
      var custom = periode.value === 'custom';
      mulai.readOnly = !custom;
      selesai.readOnly = !custom;
    }

    periode.addEventListener('change', toggleCustomDates);
    toggleCustomDates();
  });
</script>
@endpush
