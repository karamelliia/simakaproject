@extends('layouts.adminlte')
@section('title', 'Rekomendasi PKL')
@section('page_title', 'Rekomendasi PKL')

@section('content')
<div class="row mb-3">
  <div class="col-md-2 col-6">
    <div class="small-box bg-success">
      <div class="inner">
        <h3>{{ $stats['A'] }}</h3>
        <p>Grade A</p>
      </div>
      <div class="icon"><i class="fas fa-star"></i></div>
    </div>
  </div>
  <div class="col-md-2 col-6">
    <div class="small-box bg-primary">
      <div class="inner">
        <h3>{{ $stats['B'] }}</h3>
        <p>Grade B</p>
      </div>
      <div class="icon"><i class="fas fa-thumbs-up"></i></div>
    </div>
  </div>
  <div class="col-md-2 col-6">
    <div class="small-box bg-info">
      <div class="inner">
        <h3>{{ $stats['C'] }}</h3>
        <p>Grade C</p>
      </div>
      <div class="icon"><i class="fas fa-balance-scale"></i></div>
    </div>
  </div>
  <div class="col-md-2 col-6">
    <div class="small-box bg-warning">
      <div class="inner">
        <h3>{{ $stats['D'] }}</h3>
        <p>Grade D</p>
      </div>
      <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
    </div>
  </div>
  <div class="col-md-2 col-6">
    <div class="small-box bg-danger">
      <div class="inner">
        <h3>{{ $stats['E'] }}</h3>
        <p>Grade E</p>
      </div>
      <div class="icon"><i class="fas fa-times-circle"></i></div>
    </div>
  </div>
  <div class="col-md-2 col-6">
    <div class="small-box bg-secondary">
      <div class="inner">
        <h3>{{ $stats['total'] }}</h3>
        <p>Total Siswa</p>
        @if(($stats['belum'] ?? 0) > 0)
          <small>{{ $stats['belum'] }} belum dihitung</small>
        @endif
      </div>
      <div class="icon"><i class="fas fa-users"></i></div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h3 class="card-title">Daftar Rekomendasi PKL</h3>
  </div>
  <div class="card-body">
    <div class="mb-3 text-muted">
      @if($tahunAktif)
        Tahun Pelajaran Aktif: <b>{{ $tahunAktif->tahun_pelajaran }}</b> | Semester: <b>{{ $tahunAktif->semester }}</b>
      @else
        Tahun pelajaran aktif belum ditentukan.
      @endif
    </div>
    <div class="alert alert-light border mb-3">
      <div><b>Rumus Saat Ini</b></div>
      <div class="small">
        Nilai dasar: Kehadiran {{ number_format($weights['raw'][0], 1) }}% + Sikap {{ number_format($weights['raw'][1], 1) }}%
      </div>
      <div class="small">
        Nilai akhir = nilai dasar - penalti BK (0 sampai 40). Pelanggaran sangat berat menjadi terminal: nilai 0 dan Grade E.
      </div>
      <div class="small">
        Batas grade: A ≥ {{ $thresholds['A'] }}, B ≥ {{ $thresholds['B'] }}, C ≥ {{ $thresholds['C'] }}, D ≥ {{ $thresholds['D'] }}, selain itu E
      </div>
    </div>

    <form method="GET" class="row">
      <div class="col-md-3 mb-2">
        <label>Cari Siswa</label>
        <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="Nama / NIS / NISN">
      </div>
      <div class="col-md-2 mb-2">
        <label>Tingkat</label>
        <select name="tingkat" class="form-control">
          <option value="">Semua</option>
          <option value="X" {{ $tingkat === 'X' ? 'selected' : '' }}>X</option>
          <option value="XI" {{ $tingkat === 'XI' ? 'selected' : '' }}>XI</option>
          <option value="XII" {{ $tingkat === 'XII' ? 'selected' : '' }}>XII</option>
        </select>
      </div>
      <div class="col-md-3 mb-2">
        <label>Kelas</label>
        <select name="kelas_id" class="form-control">
          <option value="">Semua Kelas</option>
          @foreach($kelasOptions as $kelas)
            <option value="{{ $kelas->id }}" {{ (string) $kelasId === (string) $kelas->id ? 'selected' : '' }}>
              {{ $kelas->nama_kelas }}
            </option>
          @endforeach
        </select>
      </div>
      <div class="col-md-2 mb-2">
        <label>Grade PKL</label>
        <select name="grade" class="form-control">
          <option value="">Semua</option>
          <option value="A" {{ $grade === 'A' ? 'selected' : '' }}>A</option>
          <option value="B" {{ $grade === 'B' ? 'selected' : '' }}>B</option>
          <option value="C" {{ $grade === 'C' ? 'selected' : '' }}>C</option>
          <option value="D" {{ $grade === 'D' ? 'selected' : '' }}>D</option>
          <option value="E" {{ $grade === 'E' ? 'selected' : '' }}>E</option>
        </select>
      </div>
      <div class="col-md-2 mb-2">
        <label>Limit</label>
        <select name="limit" class="form-control">
          <option value="10" {{ (int) $limit === 10 ? 'selected' : '' }}>10</option>
          <option value="25" {{ (int) $limit === 25 ? 'selected' : '' }}>25</option>
          <option value="50" {{ (int) $limit === 50 ? 'selected' : '' }}>50</option>
          <option value="100" {{ (int) $limit === 100 ? 'selected' : '' }}>100</option>
        </select>
      </div>
      <div class="col-12 d-flex mt-2">
        <button class="btn btn-primary mr-2">
          <i class="fas fa-filter"></i> Terapkan
        </button>
        <a href="{{ route($routeBase . '.rekomendasi-pkl.export', request()->query()) }}" class="btn btn-success mr-2">
          <i class="fas fa-file-csv"></i> Export CSV
        </a>
        @if($routeBase === 'admin.bk')
          <a href="{{ route('admin.bk.rekomendasi-pkl.settings') }}" class="btn btn-warning mr-2">
            <i class="fas fa-cog"></i> Pengaturan
          </a>
        @endif
        <a href="{{ route($routeBase . '.rekomendasi-pkl.index') }}" class="btn btn-secondary">Reset</a>
      </div>
    </form>

    <div class="table-responsive mt-3">
      <table class="table table-bordered table-hover mb-0">
        <thead>
          <tr>
            <th style="width:60px;">No</th>
            <th>Nama Siswa</th>
            <th style="width:120px;">Kelas</th>
            <th style="width:150px;">Persentase Kehadiran</th>
            <th style="width:130px;">Sikap Terakhir</th>
            <th style="width:110px;">Poin BK Aktif</th>
            <th style="width:105px;">Penalti BK</th>
            <th style="width:130px;">Nilai Akhir</th>
            <th style="width:110px;">Grade PKL</th>
            <th>Rekomendasi</th>
          </tr>
        </thead>
        <tbody>
          @forelse($rows as $i => $row)
            <tr>
              <td>{{ $rows->firstItem() + $i }}</td>
              <td>{{ $row['nama_siswa'] }}</td>
              <td>{{ $row['kelas'] }}</td>
              <td>
                @if($row['persentase_kehadiran'] === null)
                  -
                @else
                  {{ number_format($row['persentase_kehadiran'], 2) }}%
                @endif
              </td>
              <td>{{ $row['sikap_terakhir'] }}</td>
              <td>{{ $row['poin_bk'] }}</td>
              <td>
                @if($row['is_terminal'])
                  <span class="badge badge-dark">TERMINAL</span>
                @else
                  -{{ number_format($row['penalti_bk'], 2) }}
                @endif
              </td>
              <td>{{ $row['nilai_akhir'] === null ? '-' : number_format($row['nilai_akhir'], 2) }}</td>
              <td>
                @if($row['grade_pkl'] === null)
                  <span class="badge badge-secondary">-</span>
                @else
                  @php
                    $badgeClass = match ($row['grade_pkl']) {
                      'A' => 'badge-success',
                      'B' => 'badge-primary',
                      'C' => 'badge-info',
                      'D' => 'badge-warning',
                      default => 'badge-danger',
                    };
                  @endphp
                  <span class="badge {{ $badgeClass }}">{{ $row['grade_pkl'] }}</span>
                @endif
              </td>
              <td>
                {{ $row['grade_label'] }}
                @if($row['batas_grade'])
                  <div class="small text-muted">{{ $row['batas_grade'] }}</div>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="10" class="text-center text-muted">Data siswa tidak ditemukan.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-3">
      <div class="text-muted small">
        Menampilkan {{ $rows->firstItem() ?? 0 }} - {{ $rows->lastItem() ?? 0 }} dari {{ $rows->total() }} data
      </div>
      <div>{{ $rows->onEachSide(1)->links('pagination::bootstrap-4') }}</div>
    </div>
  </div>
</div>
@endsection
