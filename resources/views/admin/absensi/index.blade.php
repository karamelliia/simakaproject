@extends('layouts.adminlte')
@section('title','Absensi')

@section('content')
<div class="container-fluid">
  <div class="d-flex align-items-center mb-3">
    <h4 class="mb-0">Absensi</h4>
  </div>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  <div class="card">
    <div class="card-body pb-0">
      <a href="{{ route('admin.absensi.jadwal') }}" class="btn btn-primary btn-sm">
        <i class="fas fa-calendar-alt"></i> Kelola Jadwal Pelajaran
      </a>
    </div>

    <form method="GET" class="card-body pb-2">
      <div class="d-flex justify-content-between align-items-center" style="gap:12px;">
        <div class="d-flex align-items-center" style="gap:8px;">
          <span class="text-muted">Tampilkan</span>
          <select name="limit" class="form-control form-control-sm" style="width:85px;" onchange="this.form.submit()">
            <option value="10" {{ (int)$limit===10 ? 'selected' : '' }}>10</option>
            <option value="25" {{ (int)$limit===25 ? 'selected' : '' }}>25</option>
            <option value="50" {{ (int)$limit===50 ? 'selected' : '' }}>50</option>
            <option value="100" {{ (int)$limit===100 ? 'selected' : '' }}>100</option>
          </select>
          <span class="text-muted">data</span>
        </div>

        <div class="d-flex" style="gap:8px;width:260px;">
          <input type="text" name="q" class="form-control form-control-sm" placeholder="Search..." value="{{ $q }}">
          <button class="btn btn-sm btn-secondary"><i class="fas fa-search"></i></button>
        </div>
      </div>
    </form>

    <div class="card-body pt-0 table-responsive">
      <table class="table table-bordered table-sm mb-0">
        <thead class="bg-dark text-white">
          <tr>
            <th style="width:50px;">#</th>
            <th>Kelas</th>
            <th>Wali Kelas</th>
            <th>Tahun Pelajaran</th>
            <th style="width:120px;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @php $start = ($kelas->currentPage() - 1) * $kelas->perPage(); @endphp
          @forelse($kelas as $i => $k)
            <tr>
              <td>{{ $start + $i + 1 }}</td>
              <td>{{ $k->nama_kelas }}</td>
              <td>{{ $k->wali->pengguna->nama ?? '-' }}</td>
              <td>{{ $tahunAktif?->tahun_pelajaran ?? '-' }} - {{ $tahunAktif?->semester ?? '-' }}</td>
              <td>
                <a href="{{ route('admin.absensi.rekap', $k->id) }}" class="btn btn-info btn-sm">
                  <i class="fas fa-print"></i> Rekap
                </a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="text-center text-muted py-4">Data kelas belum tersedia.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="card-footer d-flex justify-content-between align-items-center">
      <div class="text-muted small">
        Menampilkan {{ $kelas->firstItem() ?? 0 }} - {{ $kelas->lastItem() ?? 0 }} dari {{ $kelas->total() }} data
      </div>
      <div>{{ $kelas->links('pagination::bootstrap-4') }}</div>
    </div>
  </div>
</div>
@endsection
