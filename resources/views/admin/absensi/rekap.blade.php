@extends('layouts.adminlte')
@section('title','Rekapitulasi Absensi')

@push('styles')
<style>
  .rekap-absensi-table thead th {
    background: linear-gradient(90deg, var(--simaka-primary-dark) 0%, var(--simaka-primary) 100%);
    color: #fff;
    border-color: var(--simaka-primary-dark);
  }
  .rekap-absensi-table tbody tr:nth-child(even) {
    background-color: var(--simaka-primary-soft);
  }
  .rekap-absensi-table td {
    vertical-align: middle;
  }
  .rekap-head-badge {
    min-width: 26px;
    display: inline-block;
    font-weight: 700;
  }
</style>
@endpush

@section('content')
<div class="container-fluid">
  <div class="d-flex align-items-center mb-3">
    <a href="{{ route('admin.absensi.index') }}" class="btn btn-link p-0 mr-2">
      <i class="fas fa-arrow-left"></i>
    </a>
    <h4 class="mb-0">Rekapitulasi Absensi</h4>
  </div>

  <div class="card mb-3">
    <div class="card-body">
      <div class="row">
        <div class="col-md-2 font-weight-bold">Kelas</div>
        <div class="col-md-10">: {{ $kelas->nama_kelas }}</div>
        <div class="col-md-2 font-weight-bold mt-2">Wali Kelas</div>
        <div class="col-md-10 mt-2">: {{ $kelas->wali->pengguna->nama ?? '-' }}</div>
        <div class="col-md-2 font-weight-bold mt-2">Tahun Pelajaran</div>
        <div class="col-md-10 mt-2">: {{ $tahunAktif->tahun_pelajaran }} - {{ $tahunAktif->semester }}</div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-body border-bottom">
      <form method="GET" class="row">
        <div class="col-md-3">
          <label>Periode</label>
          <select name="periode" class="form-control">
            <option value="month" {{ $periode==='month' ? 'selected' : '' }}>Per 1 Bulan</option>
            <option value="quarter" {{ $periode==='quarter' ? 'selected' : '' }}>Per 3 Bulan</option>
            <option value="semester" {{ $periode==='semester' ? 'selected' : '' }}>Per Semester</option>
            <option value="year" {{ $periode==='year' ? 'selected' : '' }}>Per Tahun</option>
          </select>
        </div>

        <div class="col-md-3">
          <label>Bulan (untuk Per 1 Bulan)</label>
          <select name="bulan" class="form-control">
            @for($m=1; $m<=12; $m++)
              <option value="{{ $m }}" {{ (int)$bulan===$m ? 'selected' : '' }}>
                {{ \Carbon\Carbon::create(null, $m, 1)->translatedFormat('F') }}
              </option>
            @endfor
          </select>
        </div>

        <div class="col-md-3">
          <label>Triwulan (untuk Per 3 Bulan)</label>
          <select name="quarter" class="form-control">
            <option value="1" {{ (int)$quarter===1 ? 'selected' : '' }}>Triwulan 1</option>
            <option value="2" {{ (int)$quarter===2 ? 'selected' : '' }}>Triwulan 2</option>
          </select>
        </div>

        <div class="col-md-3 d-flex align-items-end">
          <button class="btn btn-primary btn-block">Tampilkan Rekap</button>
        </div>
      </form>
    </div>

    <div class="card-body pt-3">
      <div class="mb-2 text-muted">
        <b>{{ $periodeLabel }}</b> |
        {{ \Carbon\Carbon::parse($startDate)->translatedFormat('d F Y') }}
        s/d {{ \Carbon\Carbon::parse($endDate)->translatedFormat('d F Y') }}
      </div>
      <div class="alert alert-light border py-2">
        Semua status dihitung per jam pelajaran (JP). Skor: Hadir × 1, Sakit × 0,5, Izin × 0,5, dan Alpa × 0.
      </div>
      <div class="table-responsive">
        <table class="table table-bordered table-sm table-hover mb-0 rekap-absensi-table">
          <thead>
            <tr>
              <th style="width:50px;">#</th>
              <th>NIS</th>
              <th>Nama Siswa</th>
              <th style="width:70px;">L/P</th>
              <th style="width:70px;"><span class="badge badge-success rekap-head-badge">H</span></th>
              <th style="width:70px;"><span class="badge badge-warning rekap-head-badge text-dark">S</span></th>
              <th style="width:70px;"><span class="badge badge-info rekap-head-badge">I</span></th>
              <th style="width:70px;"><span class="badge badge-danger rekap-head-badge">A</span></th>
              <th style="width:90px;">Total JP</th>
              <th style="width:100px;">Skor</th>
              <th style="width:110px;">Kehadiran</th>
            </tr>
          </thead>
          <tbody>
            @forelse($rows as $i => $r)
              <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $r['siswa']->nis ?? '-' }}</td>
                <td>{{ $r['siswa']->nama_siswa ?? '-' }}</td>
                <td>{{ $r['siswa']->jenis_kelamin ?? '-' }}</td>
                <td>{{ $r['hadir'] }}</td>
                <td>{{ $r['sakit'] }}</td>
                <td>{{ $r['izin'] }}</td>
                <td>{{ $r['alpa'] }}</td>
                <td>{{ $r['total_jam'] }}</td>
                <td>{{ number_format($r['skor'], 1, ',', '.') }}</td>
                <td>{{ $r['persentase'] === null ? '-' : number_format($r['persentase'], 2, ',', '.') . '%' }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="11" class="text-center text-muted py-4">Belum ada data absensi pada periode ini.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection
