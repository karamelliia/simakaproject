@extends('layouts.adminlte')
@section('title', 'Daftar Pelanggaran')
@section('page_title', 'Daftar Pelanggaran')

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

@if(false)
<div class="row mb-3">
  <div class="col-md-8">
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Top 5 Poin Historis Pelanggaran</h3>
      </div>
      <div class="card-body p-0">
        <table class="table table-sm mb-0">
          <thead>
            <tr>
              <th style="width:50px">No</th>
              <th>Siswa</th>
              <th style="width:160px">Total Poin</th>
            </tr>
          </thead>
          <tbody>
            @forelse($topSiswa as $idx => $row)
              <tr>
                <td>{{ $idx + 1 }}</td>
                <td>{{ $row->siswa->nama_siswa ?? '-' }}</td>
                <td><span class="badge badge-danger">{{ (int) $row->total_poin }}</span></td>
              </tr>
            @empty
              <tr>
                <td colspan="3" class="text-center text-muted">Belum ada data pelanggaran siswa.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Tambah Jenis Pelanggaran</h3>
      </div>
      <form method="POST" action="{{ route($routeBase . '.pelanggaran.jenis.store') }}">
        @csrf
        <div class="card-body">
          <div class="form-group mb-2">
            <label>Kode</label>
            <input type="text" name="kode" class="form-control form-control-sm" required>
          </div>
          <div class="form-group mb-2">
            <label>Nama Pelanggaran</label>
            <input type="text" name="nama_pelanggaran" class="form-control form-control-sm" required>
          </div>
          @if($supportsJenisKategori)
            <div class="form-group mb-2">
              <label>Kategori</label>
              <select name="kategori" class="form-control form-control-sm" required>
                @foreach($kategoriOptions as $kategori)
                  <option value="{{ $kategori }}">{{ $kategoriLabels[$kategori] ?? ucfirst($kategori) }}</option>
                @endforeach
              </select>
            </div>
          @endif
          <div class="form-group mb-2">
            <label>Poin Default</label>
            <input type="number" name="poin_default" class="form-control form-control-sm" min="0" max="10000" value="0" required>
            <small class="text-muted">Kategori sangat berat otomatis menggunakan poin terminal 10.000.</small>
          </div>
          @if($supportsJenisKategori)
            <div class="form-group mb-2">
              <label>Penanganan</label>
              <input type="text" name="penanganan" class="form-control form-control-sm" placeholder="Contoh: BK, Waka Kesis">
            </div>
            <div class="form-group mb-2">
              <label>Urutan</label>
              <input type="number" name="urutan" class="form-control form-control-sm" min="0" value="{{ $jenis->count() + 1 }}">
            </div>
          @endif
          <div class="form-group mb-0">
            <div class="custom-control custom-switch">
              <input type="checkbox" name="status_aktif" value="1" class="custom-control-input" id="statusAktifBaru" checked>
              <label class="custom-control-label" for="statusAktifBaru">Status Aktif</label>
            </div>
          </div>
        </div>
        <div class="card-footer">
          <button class="btn btn-primary btn-sm">Simpan Jenis</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="card mb-3">
  <div class="card-header">
    <h3 class="card-title">Master Jenis Pelanggaran</h3>
  </div>
  <div class="card-body table-responsive">
    <table class="table table-bordered table-hover mb-0">
      <thead>
        <tr>
          <th style="width:50px">No</th>
          <th style="width:120px">Kode</th>
          <th>Nama Pelanggaran</th>
          @if($supportsJenisKategori)
            <th style="width:130px">Kategori</th>
          @endif
          <th style="width:120px">Poin Default</th>
          @if($supportsJenisKategori)
            <th>Penanganan</th>
            <th style="width:100px">Urutan</th>
          @endif
          <th style="width:120px">Status</th>
          <th style="width:220px">Aksi</th>
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
              {{ $j->poin_default }}
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
              <button class="btn btn-warning btn-xs"
                      data-toggle="modal"
                      data-target="#modalEditJenis{{ $j->id }}">
                <i class="fas fa-edit"></i> Edit
              </button>
              <form method="POST"
                    action="{{ route($routeBase . '.pelanggaran.jenis.destroy', $j->id) }}"
                    class="d-inline"
                    onsubmit="return confirm('Hapus jenis pelanggaran ini?')">
                @csrf
                @method('DELETE')
                <button class="btn btn-danger btn-xs">
                  <i class="fas fa-trash"></i> Hapus
                </button>
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
                        <div class="col-md-2">
                          <label>Status</label>
                          <select name="status_aktif" class="form-control">
                            <option value="1" {{ $j->status_aktif ? 'selected' : '' }}>AKTIF</option>
                            <option value="0" {{ !$j->status_aktif ? 'selected' : '' }}>NONAKTIF</option>
                          </select>
                        </div>
                      @else
                        <div class="col-md-2">
                          <label>Status</label>
                          <select name="status_aktif" class="form-control">
                            <option value="1" {{ $j->status_aktif ? 'selected' : '' }}>AKTIF</option>
                            <option value="0" {{ !$j->status_aktif ? 'selected' : '' }}>NONAKTIF</option>
                          </select>
                        </div>
                      @endif
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Batal</button>
                    <button class="btn btn-primary">Simpan</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        @empty
          <tr>
            <td colspan="{{ $supportsJenisKategori ? '9' : '6' }}" class="text-center text-muted">Belum ada master jenis pelanggaran.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endif

<div class="card">
  <div class="card-header">
    <h3 class="card-title">Data Pelanggaran Siswa</h3>
  </div>
  <div class="card-body">
    <div class="d-flex justify-content-between mb-3">
      <div>
        <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalTambahPelanggaran">
          <i class="fas fa-plus"></i> Tambah Pelanggaran
        </button>
        <a href="{{ route($routeBase . '.pelanggaran.jenis.index') }}" class="btn btn-info btn-sm ml-1">
          <i class="fas fa-list"></i> Master Jenis Pelanggaran
        </a>
      </div>
    </div>

    <form method="GET" class="d-flex justify-content-between align-items-center mb-2">
      <div>
        <label class="mb-0">
          Tampilkan
          <select name="limit" class="custom-select custom-select-sm w-auto" onchange="this.form.submit()">
            <option value="10" {{ (int) $limit === 10 ? 'selected' : '' }}>10</option>
            <option value="25" {{ (int) $limit === 25 ? 'selected' : '' }}>25</option>
            <option value="50" {{ (int) $limit === 50 ? 'selected' : '' }}>50</option>
            <option value="100" {{ (int) $limit === 100 ? 'selected' : '' }}>100</option>
          </select>
          data
        </label>
      </div>
      <div class="d-flex">
        <input type="text" name="q" value="{{ $q }}" class="form-control form-control-sm" placeholder="Cari siswa / jenis..." style="width:240px;">
        <button class="btn btn-sm btn-secondary ml-2"><i class="fas fa-search"></i></button>
      </div>

      <input type="hidden" name="kelas_id" value="{{ $kelasId }}">
      <input type="hidden" name="jenis_id" value="{{ $jenisId }}">
      <input type="hidden" name="status" value="{{ $status }}">
      <input type="hidden" name="tanggal_dari" value="{{ $tanggalDari }}">
      <input type="hidden" name="tanggal_sampai" value="{{ $tanggalSampai }}">
    </form>

    <button type="button" class="btn btn-info btn-sm mb-2" data-toggle="modal" data-target="#modalFilterPelanggaran">
      <i class="fas fa-filter"></i> Filter Data
    </button>

    <div class="table-responsive">
      <table class="table table-bordered table-hover mb-0">
        <thead>
          <tr>
            <th style="width:60px">No</th>
            <th>Siswa</th>
            <th style="width:120px">Kelas</th>
            <th style="width:120px">Tanggal</th>
            <th>Jenis</th>
            <th style="width:90px">Poin</th>
            <th style="width:130px">Status</th>
            <th style="width:220px">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse($pelanggaran as $i => $row)
            <tr>
              <td>{{ $pelanggaran->firstItem() + $i }}</td>
              <td>{{ $row->siswa->nama_siswa ?? '-' }}</td>
              <td>{{ $row->kelas->nama_kelas ?? '-' }}</td>
              <td>{{ optional($row->tanggal)->translatedFormat('d F Y') }}</td>
              <td>{{ $row->jenis->nama_pelanggaran ?? '-' }}</td>
              <td>
                <span class="badge {{ $row->jenis?->is_terminal ? 'badge-dark' : 'badge-danger' }}">
                  {{ $row->poin }}
                </span>
              </td>
              <td>{{ $row->status }}</td>
              <td>
                <button class="btn btn-warning btn-xs"
                        data-toggle="modal"
                        data-target="#modalEditPelanggaran{{ $row->id }}">
                  <i class="fas fa-edit"></i> Edit
                </button>
                <form method="POST"
                      action="{{ route($routeBase . '.pelanggaran.destroy', $row->id) }}"
                      class="d-inline"
                      onsubmit="return confirm('Hapus data pelanggaran ini?')">
                  @csrf
                  @method('DELETE')
                  <button class="btn btn-danger btn-xs">
                    <i class="fas fa-trash"></i> Hapus
                  </button>
                </form>
              </td>
            </tr>

            <div class="modal fade" id="modalEditPelanggaran{{ $row->id }}" tabindex="-1">
              <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                  <form method="POST" action="{{ route($routeBase . '.pelanggaran.update', $row->id) }}">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                      <h4 class="modal-title">Edit Pelanggaran</h4>
                      <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                      <div class="row">
                        <div class="col-md-5">
                          <label>Siswa</label>
                          <select name="data_siswa_id" class="form-control" required>
                            @foreach($siswaOptions as $s)
                              <option value="{{ $s->id }}" {{ (int) $row->data_siswa_id === (int) $s->id ? 'selected' : '' }}>
                                {{ $s->nama_siswa }} - {{ $s->kelas->nama_kelas ?? '-' }}
                              </option>
                            @endforeach
                          </select>
                        </div>
                        <div class="col-md-3">
                          <label>Jenis</label>
                          <select name="bk_jenis_pelanggaran_id" class="form-control" required>
                            @foreach($jenis as $j)
                              <option value="{{ $j->id }}" {{ (int) $row->bk_jenis_pelanggaran_id === (int) $j->id ? 'selected' : '' }}>
                                {{ $j->nama_pelanggaran }}
                              </option>
                            @endforeach
                          </select>
                        </div>
                        <div class="col-md-2">
                          <label>Poin</label>
                          <input type="number" name="poin" class="form-control" value="{{ $row->poin }}" min="0" max="10000" required>
                        </div>
                        <div class="col-md-2">
                          <label>Status</label>
                          <select name="status" class="form-control" required>
                            @foreach($statusOptions as $st)
                              <option value="{{ $st }}" {{ $row->status === $st ? 'selected' : '' }}>{{ $st }}</option>
                            @endforeach
                          </select>
                        </div>
                      </div>
                      <div class="row mt-3">
                        <div class="col-md-3">
                          <label>Tanggal</label>
                          <input type="date" name="tanggal" class="form-control" value="{{ optional($row->tanggal)->format('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-9">
                          <label>Kronologi</label>
                          <textarea name="kronologi" class="form-control" rows="2">{{ $row->kronologi }}</textarea>
                        </div>
                      </div>
                      <div class="form-group mt-3 mb-0">
                        <label>Tindakan</label>
                        <textarea name="tindakan" class="form-control" rows="2">{{ $row->tindakan }}</textarea>
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
              <td colspan="8" class="text-center text-muted">Data pelanggaran siswa belum tersedia.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-3">
      <div class="text-muted small">
        Menampilkan {{ $pelanggaran->firstItem() ?? 0 }} - {{ $pelanggaran->lastItem() ?? 0 }}
        dari {{ $pelanggaran->total() }} data
      </div>
      <div>{{ $pelanggaran->onEachSide(1)->links('pagination::bootstrap-4') }}</div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalTambahPelanggaran" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="{{ route($routeBase . '.pelanggaran.store') }}">
        @csrf
        <div class="modal-header">
          <h4 class="modal-title">Tambah Pelanggaran Siswa</h4>
          <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-5">
              <label>Siswa</label>
              <select name="data_siswa_id" class="form-control" required>
                <option value="">Pilih Siswa</option>
                @foreach($siswaOptions as $s)
                  <option value="{{ $s->id }}">{{ $s->nama_siswa }} - {{ $s->kelas->nama_kelas ?? '-' }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-3">
              <label>Jenis</label>
              <select name="bk_jenis_pelanggaran_id" class="form-control" required>
                <option value="">Pilih Jenis</option>
                @foreach($jenis as $j)
                  <option value="{{ $j->id }}">{{ $j->nama_pelanggaran }} ({{ $j->poin_default }})</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-2">
              <label>Poin</label>
              <input type="number" name="poin" class="form-control" min="0" max="10000" placeholder="Auto">
            </div>
            <div class="col-md-2">
              <label>Status</label>
              <select name="status" class="form-control" required>
                @foreach($statusOptions as $st)
                  <option value="{{ $st }}">{{ $st }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="row mt-3">
            <div class="col-md-3">
              <label>Tanggal</label>
              <input type="date" name="tanggal" class="form-control" value="{{ date('Y-m-d') }}" required>
            </div>
            <div class="col-md-9">
              <label>Kronologi</label>
              <textarea name="kronologi" class="form-control" rows="2"></textarea>
            </div>
          </div>
          <div class="form-group mt-3 mb-0">
            <label>Tindakan</label>
            <textarea name="tindakan" class="form-control" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-dismiss="modal">Batal</button>
          <button class="btn btn-primary">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modalFilterPelanggaran" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form method="GET">
        <div class="modal-header">
          <h4 class="modal-title">Filter Data Pelanggaran</h4>
          <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-4">
              <label>Kelas</label>
              <select name="kelas_id" class="form-control">
                <option value="">Semua</option>
                @foreach($kelasOptions as $k)
                  <option value="{{ $k->id }}" {{ (string) $kelasId === (string) $k->id ? 'selected' : '' }}>
                    {{ $k->nama_kelas }}
                  </option>
                @endforeach
              </select>
            </div>
            <div class="col-md-4">
              <label>Jenis</label>
              <select name="jenis_id" class="form-control">
                <option value="">Semua</option>
                @foreach($jenis as $j)
                  <option value="{{ $j->id }}" {{ (string) $jenisId === (string) $j->id ? 'selected' : '' }}>
                    {{ $j->nama_pelanggaran }}
                  </option>
                @endforeach
              </select>
            </div>
            <div class="col-md-4">
              <label>Status</label>
              <select name="status" class="form-control">
                <option value="">Semua</option>
                @foreach($statusOptions as $st)
                  <option value="{{ $st }}" {{ $status === $st ? 'selected' : '' }}>{{ $st }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="row mt-3">
            <div class="col-md-6">
              <label>Tanggal Dari</label>
              <input type="date" name="tanggal_dari" class="form-control" value="{{ $tanggalDari }}">
            </div>
            <div class="col-md-6">
              <label>Tanggal Sampai</label>
              <input type="date" name="tanggal_sampai" class="form-control" value="{{ $tanggalSampai }}">
            </div>
          </div>
          <input type="hidden" name="q" value="{{ $q }}">
          <input type="hidden" name="limit" value="{{ $limit }}">
        </div>
        <div class="modal-footer">
          <a href="{{ route($routeBase . '.pelanggaran.index') }}" class="btn btn-secondary">Reset</a>
          <button class="btn btn-primary">Terapkan</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
