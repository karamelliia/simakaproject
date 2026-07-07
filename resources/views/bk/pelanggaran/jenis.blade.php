@extends('layouts.adminlte')
@section('title', 'Master Jenis Pelanggaran')
@section('page_title', 'Master Jenis Pelanggaran')

@section('content')
@if(session('success'))
  <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
  <div class="alert alert-danger">{{ session('error') }}</div>
@endif
@if($errors->any())
  <div class="alert alert-danger">
    <ul class="mb-0 pl-3">
      @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif

<div class="d-flex justify-content-between align-items-center mb-3">
  <a href="{{ route($routeBase . '.pelanggaran.index') }}" class="btn btn-secondary">
    <i class="fas fa-arrow-left"></i> Kembali
  </a>
  <button class="btn btn-primary" data-toggle="modal" data-target="#modalTambahJenis">
    <i class="fas fa-plus"></i> Tambah Jenis Pelanggaran
  </button>
</div>

<div class="card">
  <div class="card-header">
    <h3 class="card-title">Daftar Master Jenis Pelanggaran</h3>
  </div>
  <div class="card-body table-responsive">
    <table class="table table-bordered table-hover mb-0">
      <thead>
        <tr>
          <th style="width:50px">No</th>
          <th style="width:110px">Kode</th>
          <th>Nama Pelanggaran</th>
          @if($supportsJenisKategori)
            <th style="width:150px">Kategori</th>
          @endif
          <th style="width:140px">Poin</th>
          @if($supportsJenisKategori)
            <th>Penanganan</th>
            <th style="width:90px">Urutan</th>
          @endif
          <th style="width:110px">Status</th>
          <th style="width:180px">Aksi</th>
        </tr>
      </thead>
      <tbody>
        @forelse($jenis as $i => $j)
          <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $j->kode }}</td>
            <td>{{ $j->nama_pelanggaran }}</td>
            @if($supportsJenisKategori)
              <td>{{ $kategoriLabels[$j->kategori] ?? ucfirst($j->kategori) }}</td>
            @endif
            <td>
              {{ number_format($j->poin_default, 0, ',', '.') }}
              @if($j->is_terminal)
                <span class="badge badge-dark">TERMINAL</span>
              @endif
              @if(isset($j->affects_pkl_score) && !$j->affects_pkl_score)
                <span class="badge badge-info">ABSENSI</span>
              @endif
            </td>
            @if($supportsJenisKategori)
              <td>{{ $j->penanganan ?: '-' }}</td>
              <td>{{ $j->urutan }}</td>
            @endif
            <td>
              <span class="badge {{ $j->status_aktif ? 'badge-success' : 'badge-secondary' }}">
                {{ $j->status_aktif ? 'AKTIF' : 'NONAKTIF' }}
              </span>
            </td>
            <td>
              <button class="btn btn-warning btn-xs" data-toggle="modal" data-target="#modalEditJenis{{ $j->id }}">
                <i class="fas fa-edit"></i> Edit
              </button>
              <form method="POST"
                    action="{{ route($routeBase . '.pelanggaran.jenis.destroy', $j->id) }}"
                    class="d-inline"
                    onsubmit="return confirm('Hapus jenis pelanggaran ini?')">
                @csrf
                @method('DELETE')
                <button class="btn btn-danger btn-xs"><i class="fas fa-trash"></i> Hapus</button>
              </form>
            </td>
          </tr>

          <div class="modal fade" id="modalEditJenis{{ $j->id }}" tabindex="-1">
            <div class="modal-dialog modal-lg modal-dialog-centered">
              <div class="modal-content">
                <form method="POST" action="{{ route($routeBase . '.pelanggaran.jenis.update', $j->id) }}">
                  @csrf
                  @method('PUT')
                  <div class="modal-header">
                    <h4 class="modal-title">Edit Jenis Pelanggaran</h4>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                  </div>
                  <div class="modal-body">
                    <div class="row">
                      <div class="col-md-3">
                        <label>Kode</label>
                        <input type="text" name="kode" class="form-control" value="{{ $j->kode }}" required>
                      </div>
                      <div class="col-md-5">
                        <label>Nama Pelanggaran</label>
                        <input type="text" name="nama_pelanggaran" class="form-control" value="{{ $j->nama_pelanggaran }}" required>
                      </div>
                      @if($supportsJenisKategori)
                        <div class="col-md-2">
                          <label>Kategori</label>
                          <select name="kategori" class="form-control" required>
                            @foreach($kategoriOptions as $kategori)
                              <option value="{{ $kategori }}" {{ $j->kategori === $kategori ? 'selected' : '' }}>
                                {{ $kategoriLabels[$kategori] ?? ucfirst($kategori) }}
                              </option>
                            @endforeach
                          </select>
                        </div>
                      @endif
                      <div class="col-md-2">
                        <label>Poin</label>
                        <input type="number" name="poin_default" class="form-control" value="{{ $j->poin_default }}" min="0" max="10000" required>
                      </div>
                    </div>
                    <div class="row mt-3">
                      @if($supportsJenisKategori)
                        <div class="col-md-8">
                          <label>Penanganan</label>
                          <input type="text" name="penanganan" class="form-control" value="{{ $j->penanganan }}">
                        </div>
                        <div class="col-md-2">
                          <label>Urutan</label>
                          <input type="number" name="urutan" class="form-control" value="{{ $j->urutan }}" min="0">
                        </div>
                      @endif
                      <div class="col-md-2">
                        <label>Status</label>
                        <select name="status_aktif" class="form-control">
                          <option value="1" {{ $j->status_aktif ? 'selected' : '' }}>AKTIF</option>
                          <option value="0" {{ !$j->status_aktif ? 'selected' : '' }}>NONAKTIF</option>
                        </select>
                      </div>
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Batal</button>
                    <button class="btn btn-primary">Simpan Perubahan</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        @empty
          <tr>
            <td colspan="{{ $supportsJenisKategori ? 9 : 6 }}" class="text-center text-muted">
              Belum ada master jenis pelanggaran.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="modalTambahJenis" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="{{ route($routeBase . '.pelanggaran.jenis.store') }}">
        @csrf
        <div class="modal-header">
          <h4 class="modal-title">Tambah Jenis Pelanggaran</h4>
          <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-3">
              <label>Kode</label>
              <input type="text" name="kode" class="form-control" required>
            </div>
            <div class="col-md-5">
              <label>Nama Pelanggaran</label>
              <input type="text" name="nama_pelanggaran" class="form-control" required>
            </div>
            @if($supportsJenisKategori)
              <div class="col-md-2">
                <label>Kategori</label>
                <select name="kategori" class="form-control" required>
                  @foreach($kategoriOptions as $kategori)
                    <option value="{{ $kategori }}">{{ $kategoriLabels[$kategori] ?? ucfirst($kategori) }}</option>
                  @endforeach
                </select>
              </div>
            @endif
            <div class="col-md-2">
              <label>Poin</label>
              <input type="number" name="poin_default" class="form-control" min="0" max="10000" value="0" required>
            </div>
          </div>
          @if($supportsJenisKategori)
            <div class="row mt-3">
              <div class="col-md-9">
                <label>Penanganan</label>
                <input type="text" name="penanganan" class="form-control" placeholder="Contoh: BK, Waka Kesis">
              </div>
              <div class="col-md-3">
                <label>Urutan</label>
                <input type="number" name="urutan" class="form-control" min="0" value="{{ $jenis->count() + 1 }}">
              </div>
            </div>
          @endif
          <div class="custom-control custom-switch mt-3">
            <input type="checkbox" name="status_aktif" value="1" class="custom-control-input" id="statusAktifBaru" checked>
            <label class="custom-control-label" for="statusAktifBaru">Status Aktif</label>
          </div>
          <small class="text-muted">Kategori sangat berat otomatis menggunakan poin terminal 10.000.</small>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-dismiss="modal">Batal</button>
          <button class="btn btn-primary">Simpan Jenis</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
