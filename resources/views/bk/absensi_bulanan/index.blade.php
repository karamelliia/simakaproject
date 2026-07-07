@extends('layouts.adminlte')
@section('title', 'Rekapitulasi Absensi')
@section('page_title', 'Rekapitulasi Absensi')

@section('content')
<div class="card mb-3">
  <div class="card-header">
    <h3 class="card-title">Filter Rekapitulasi Absensi</h3>
  </div>
  <form method="GET" class="card-body">
    <div class="row">
      <div class="col-md-2">
        <label>Periode</label>
        <select name="periode" id="filterPeriode" class="form-control">
          <option value="bulan" @selected($periode === 'bulan')>Bulanan</option>
          <option value="triwulan" @selected($periode === 'triwulan')>Triwulan</option>
          <option value="semester" @selected($periode === 'semester')>Semester Aktif</option>
          <option value="tahun" @selected($periode === 'tahun')>Tahun Pelajaran</option>
        </select>
      </div>
      <div class="col-md-2 period-field period-bulan">
        <label>Bulan</label>
        <select name="bulan" class="form-control" required>
          @for($m = 1; $m <= 12; $m++)
            <option value="{{ $m }}" {{ (int)$bulan === $m ? 'selected' : '' }}>
              {{ \Carbon\Carbon::create(null, $m, 1)->translatedFormat('F') }}
            </option>
          @endfor
        </select>
      </div>
      <div class="col-md-2 period-field period-triwulan">
        <label>Triwulan</label>
        <select name="triwulan" class="form-control">
          @for($quarter = 1; $quarter <= 4; $quarter++)
            <option value="{{ $quarter }}" @selected((int) $triwulan === $quarter)>
              Triwulan {{ $quarter }}
            </option>
          @endfor
        </select>
      </div>
      <div class="col-md-2 period-field period-calendar">
        <label>Tahun</label>
        <input type="number" name="tahun" class="form-control" value="{{ $tahun }}" min="2020" max="2100" required>
      </div>
      <div class="col-md-2">
        <label>Kelas</label>
        <select name="kelas_id" class="form-control">
          <option value="">Semua Kelas</option>
          @foreach($kelasOptions as $k)
            <option value="{{ $k->id }}" {{ (string)$kelasId === (string)$k->id ? 'selected' : '' }}>
              {{ $k->nama_kelas }}
            </option>
          @endforeach
        </select>
      </div>
      <div class="col-md-2">
        <label>Cari Siswa</label>
        <input type="text" name="q" class="form-control" value="{{ $q }}" placeholder="Nama / NIS / NISN">
      </div>
      <div class="col-md-2 d-flex align-items-end">
        <button class="btn btn-primary btn-block">Terapkan</button>
      </div>
    </div>
    <div class="mt-3 text-muted small">
      Periode: {{ $periodeLabel }}
      ({{ \Carbon\Carbon::parse($startDate)->translatedFormat('d F Y') }}
      s/d {{ \Carbon\Carbon::parse($endDate)->translatedFormat('d F Y') }}) |
      Tahun Aktif: {{ $tahunAktif?->tahun_pelajaran ?? '-' }} {{ $tahunAktif?->semester ? ('- '.$tahunAktif->semester) : '' }}
    </div>
  </form>
</div>

<div class="row mb-3">
  <div class="col-md-2">
    <div class="small-box bg-secondary">
      <div class="inner"><h3>{{ $ringkasan['total_siswa'] }}</h3><p>Total Siswa</p></div>
    </div>
  </div>
  <div class="col-md-2">
    <div class="small-box bg-success">
      <div class="inner"><h3>{{ $ringkasan['total_hadir'] }}</h3><p>Hadir (JP)</p></div>
    </div>
  </div>
  <div class="col-md-2">
    <div class="small-box bg-warning">
      <div class="inner"><h3>{{ $ringkasan['total_sakit'] }}</h3><p>Sakit (JP)</p></div>
    </div>
  </div>
  <div class="col-md-2">
    <div class="small-box bg-info">
      <div class="inner"><h3>{{ $ringkasan['total_izin'] }}</h3><p>Izin (JP)</p></div>
    </div>
  </div>
  <div class="col-md-2">
    <div class="small-box bg-danger">
      <div class="inner"><h3>{{ $ringkasan['total_alpa'] }}</h3><p>Alpa (JP)</p></div>
    </div>
  </div>
  <div class="col-md-2">
    <div class="small-box bg-primary">
      <div class="inner"><h3>{{ $ringkasan['total_jam'] }}</h3><p>Total JP</p></div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h3 class="card-title">Rekap Per Siswa (Sumber: Absensi Guru Mapel)</h3>
  </div>
  <div class="card-body table-responsive">
    <div class="alert alert-light border py-2">
      Semua status dihitung per jam pelajaran (JP). Skor: Hadir × 1, Sakit × 0,5, Izin × 0,5, dan Alpa × 0.
    </div>
    <table class="table table-bordered table-hover mb-0">
      <thead>
        <tr>
          <th style="width:60px">No</th>
          <th>Nama Siswa</th>
          <th style="width:120px">Kelas</th>
          <th style="width:90px">Hadir</th>
          <th style="width:90px">Sakit</th>
          <th style="width:90px">Izin</th>
          <th style="width:90px">Alpa</th>
          <th style="width:90px">Total JP</th>
          <th style="width:100px">Skor</th>
          <th style="width:110px">Kehadiran</th>
        </tr>
      </thead>
      <tbody>
        @forelse($rows as $i => $row)
          <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $row['siswa']->nama_siswa ?? '-' }}</td>
            <td>{{ $row['siswa']->kelas->nama_kelas ?? '-' }}</td>
            <td><span class="badge badge-success">{{ $row['hadir'] }}</span></td>
            <td><span class="badge badge-warning">{{ $row['sakit'] }}</span></td>
            <td><span class="badge badge-info">{{ $row['izin'] }}</span></td>
            <td><span class="badge badge-danger">{{ $row['alpa'] }}</span></td>
            <td>{{ $row['total_jam'] }}</td>
            <td>{{ number_format($row['skor'], 1, ',', '.') }}</td>
            <td>{{ $row['persentase'] === null ? '-' : number_format($row['persentase'], 2, ',', '.') . '%' }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="10" class="text-center text-muted">Belum ada data absensi pada periode ini.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const period = document.getElementById('filterPeriode');

  function refreshPeriodFields() {
    document.querySelectorAll('.period-field').forEach(function (field) {
      field.style.display = 'none';
    });

    if (period.value === 'bulan') {
      document.querySelector('.period-bulan').style.display = 'block';
      document.querySelector('.period-calendar').style.display = 'block';
    } else if (period.value === 'triwulan') {
      document.querySelector('.period-triwulan').style.display = 'block';
      document.querySelector('.period-calendar').style.display = 'block';
    }
  }

  period.addEventListener('change', refreshPeriodFields);
  refreshPeriodFields();
});
</script>
@endpush
