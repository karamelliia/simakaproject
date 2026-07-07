@extends('layouts.adminlte')
@section('title','Kelola Absensi')

@section('content')
<div class="container-fluid">
  <div class="d-flex align-items-center mb-3">
    <a href="{{ route('guru.wali-kelas.absensi.index') }}" class="btn btn-link p-0 mr-2">
      <i class="fas fa-arrow-left"></i>
    </a>
    <h4 class="mb-0">Absensi: {{ $kelas->nama_kelas }}</h4>
  </div>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

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
    <div class="card-body pb-2">
      <form method="GET" class="d-flex justify-content-end">
        <div style="width:260px;" class="d-flex" >
          <input type="text" class="form-control form-control-sm" name="q" value="{{ $q }}" placeholder="Search siswa...">
          <button class="btn btn-secondary btn-sm ml-2"><i class="fas fa-search"></i></button>
        </div>
      </form>
    </div>

    <div class="card-body pt-0 table-responsive p-0">
      <form method="POST" action="{{ route('guru.wali-kelas.absensi.update', $kelas->id) }}">
        @csrf
        <table class="table table-bordered table-sm mb-0">
          <thead class="bg-dark text-white">
            <tr>
              <th style="width:50px;">#</th>
              <th>Nama Siswa</th>
              <th style="width:140px;">NIS</th>
              <th style="width:80px;">L/P</th>
              <th style="width:90px;">Sakit</th>
              <th style="width:90px;">Izin</th>
              <th style="width:90px;">Alpa</th>
            </tr>
          </thead>
          <tbody>
            @forelse($siswa as $i => $s)
              @php $row = $data[$s->id] ?? null; @endphp
              <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $s->nama_siswa }}</td>
                <td>{{ $s->nis ?? '-' }}</td>
                <td>{{ $s->jenis_kelamin ?? '-' }}</td>
                <td>
                  <input type="number" min="0" class="form-control form-control-sm" name="sakit[{{ $s->id }}]" value="{{ $row?->sakit ?? 0 }}">
                </td>
                <td>
                  <input type="number" min="0" class="form-control form-control-sm" name="izin[{{ $s->id }}]" value="{{ $row?->izin ?? 0 }}">
                </td>
                <td>
                  <input type="number" min="0" class="form-control form-control-sm" name="alpa[{{ $s->id }}]" value="{{ $row?->tanpa_keterangan ?? 0 }}">
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center text-muted py-4">Data siswa tidak ditemukan.</td>
              </tr>
            @endforelse
          </tbody>
        </table>

        <div class="p-3">
          <button class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

