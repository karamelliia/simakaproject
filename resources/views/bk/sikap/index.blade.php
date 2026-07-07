@extends('layouts.adminlte')
@section('title', 'Sikap Siswa')
@section('page_title', 'Sikap Siswa')

@section('content')
@if(session('success'))
  <div class="alert alert-success">{{ session('success') }}</div>
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

<div class="row mb-3">
  @foreach($predikatCounts as $label => $jumlah)
    <div class="col-md-3">
      <div class="small-box bg-secondary">
        <div class="inner">
          <h3>{{ $jumlah }}</h3>
          <p>{{ $label }}</p>
        </div>
        <div class="icon"><i class="fas fa-user-check"></i></div>
      </div>
    </div>
  @endforeach
</div>

<div class="card">
  <div class="card-header">
    <h3 class="card-title">Data Sikap Siswa</h3>
  </div>
  <div class="card-body">
    <div class="d-flex justify-content-between mb-3">
      <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalTambahSikap">
        <i class="fas fa-plus"></i> Tambah Penilaian
      </button>
      <button class="btn btn-info btn-sm" data-toggle="modal" data-target="#modalFilterSikap">
        <i class="fas fa-filter"></i> Filter Data
      </button>
    </div>

    <form method="GET" class="d-flex justify-content-between align-items-center mb-2">
      <div>
        <label class="mb-0">
          Tampilkan
          <select name="limit" class="custom-select custom-select-sm w-auto" onchange="this.form.submit()">
            <option value="10" {{ (int)$limit===10 ? 'selected' : '' }}>10</option>
            <option value="25" {{ (int)$limit===25 ? 'selected' : '' }}>25</option>
            <option value="50" {{ (int)$limit===50 ? 'selected' : '' }}>50</option>
            <option value="100" {{ (int)$limit===100 ? 'selected' : '' }}>100</option>
          </select>
          data
        </label>
      </div>
      <div class="d-flex">
        <input type="text" name="q" value="{{ $q }}" class="form-control form-control-sm" placeholder="Cari siswa / aspek..." style="width:240px;">
        <button class="btn btn-sm btn-secondary ml-2"><i class="fas fa-search"></i></button>
      </div>
      <input type="hidden" name="kelas_id" value="{{ $kelasId }}">
      <input type="hidden" name="predikat" value="{{ $predikat }}">
      <input type="hidden" name="status" value="{{ $status }}">
      <input type="hidden" name="tanggal_dari" value="{{ $tanggalDari }}">
      <input type="hidden" name="tanggal_sampai" value="{{ $tanggalSampai }}">
    </form>

    <div class="table-responsive">
      <table class="table table-bordered table-hover mb-0">
        <thead>
          <tr>
            <th style="width:60px">No</th>
            <th>Siswa</th>
            <th style="width:110px">Kelas</th>
            <th style="width:120px">Tanggal</th>
            <th>Aspek</th>
            <th style="width:120px">Predikat</th>
            <th style="width:140px">Status</th>
            <th style="width:220px">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse($sikap as $i => $row)
            <tr>
              <td>{{ $sikap->firstItem() + $i }}</td>
              <td>{{ $row->siswa->nama_siswa ?? '-' }}</td>
              <td>{{ $row->kelas->nama_kelas ?? '-' }}</td>
              <td>{{ optional($row->tanggal_penilaian)->translatedFormat('d F Y') }}</td>
              <td>{{ $row->aspek_sikap }}</td>
              <td>{{ $row->predikat }}</td>
              <td>{{ $row->status }}</td>
              <td>
                <button class="btn btn-warning btn-xs" data-toggle="modal" data-target="#modalEditSikap{{ $row->id }}">
                  <i class="fas fa-edit"></i> Edit
                </button>
                <form method="POST" action="{{ route($routeBase.'.sikap.destroy', $row->id) }}" class="d-inline" onsubmit="return confirm('Hapus data sikap ini?')">
                  @csrf
                  @method('DELETE')
                  <button class="btn btn-danger btn-xs">
                    <i class="fas fa-trash"></i> Hapus
                  </button>
                </form>
              </td>
            </tr>

            <div class="modal fade" id="modalEditSikap{{ $row->id }}" tabindex="-1">
              <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content">
                  <form method="POST" action="{{ route($routeBase.'.sikap.update', $row->id) }}">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                      <h4 class="modal-title">Edit Penilaian Sikap</h4>
                      <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                      <div class="row">
                        <div class="col-md-4">
                          <label>Siswa</label>
                          <select name="data_siswa_id" class="form-control" required>
                            @foreach($siswaOptions as $s)
                              <option value="{{ $s->id }}" {{ (int)$row->data_siswa_id === (int)$s->id ? 'selected' : '' }}>
                                {{ $s->nama_siswa }} - {{ $s->kelas->nama_kelas ?? '-' }}
                              </option>
                            @endforeach
                          </select>
                        </div>
                        <div class="col-md-2">
                          <label>Tanggal</label>
                          <input type="date" name="tanggal_penilaian" class="form-control" value="{{ optional($row->tanggal_penilaian)->format('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-3">
                          <label>Aspek Sikap</label>
                          <input type="text" name="aspek_sikap" class="form-control" value="{{ $row->aspek_sikap }}" required>
                        </div>
                        <div class="col-md-3">
                          <label>Predikat</label>
                          <select name="predikat" class="form-control" required>
                            @foreach($predikatOptions as $p)
                              <option value="{{ $p }}" {{ $row->predikat === $p ? 'selected' : '' }}>{{ $p }}</option>
                            @endforeach
                          </select>
                        </div>
                      </div>
                      <div class="row mt-3">
                        <div class="col-md-4">
                          <label>Status</label>
                          <select name="status" class="form-control" required>
                            @foreach($statusOptions as $st)
                              <option value="{{ $st }}" {{ $row->status === $st ? 'selected' : '' }}>{{ $st }}</option>
                            @endforeach
                          </select>
                        </div>
                        <div class="col-md-8">
                          <label>Catatan</label>
                          <textarea name="catatan" class="form-control" rows="2">{{ $row->catatan }}</textarea>
                        </div>
                      </div>
                      <div class="form-group mt-3 mb-0">
                        <label>Tindak Lanjut</label>
                        <textarea name="tindak_lanjut" class="form-control" rows="2">{{ $row->tindak_lanjut }}</textarea>
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
              <td colspan="8" class="text-center text-muted">Data sikap siswa belum tersedia.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-3">
      <div class="text-muted small">
        Menampilkan {{ $sikap->firstItem() ?? 0 }} - {{ $sikap->lastItem() ?? 0 }} dari {{ $sikap->total() }} data
      </div>
      <div>{{ $sikap->onEachSide(1)->links('pagination::bootstrap-4') }}</div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalTambahSikap" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="{{ route($routeBase.'.sikap.store') }}">
        @csrf
        <div class="modal-header">
          <h4 class="modal-title">Tambah Penilaian Sikap</h4>
          <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-4">
              <label>Siswa</label>
              <select name="data_siswa_id" class="form-control" required>
                <option value="">Pilih Siswa</option>
                @foreach($siswaOptions as $s)
                  <option value="{{ $s->id }}">{{ $s->nama_siswa }} - {{ $s->kelas->nama_kelas ?? '-' }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-2">
              <label>Tanggal</label>
              <input type="date" name="tanggal_penilaian" class="form-control" value="{{ date('Y-m-d') }}" required>
            </div>
            <div class="col-md-3">
              <label>Aspek Sikap</label>
              <input type="text" name="aspek_sikap" class="form-control" required>
            </div>
            <div class="col-md-3">
              <label>Predikat</label>
              <select name="predikat" class="form-control" required>
                @foreach($predikatOptions as $p)
                  <option value="{{ $p }}">{{ $p }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="row mt-3">
            <div class="col-md-4">
              <label>Status</label>
              <select name="status" class="form-control" required>
                @foreach($statusOptions as $st)
                  <option value="{{ $st }}">{{ $st }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-8">
              <label>Catatan</label>
              <textarea name="catatan" class="form-control" rows="2"></textarea>
            </div>
          </div>
          <div class="form-group mt-3 mb-0">
            <label>Tindak Lanjut</label>
            <textarea name="tindak_lanjut" class="form-control" rows="2"></textarea>
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

<div class="modal fade" id="modalFilterSikap" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form method="GET">
        <div class="modal-header">
          <h4 class="modal-title">Filter Data Sikap</h4>
          <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-4">
              <label>Kelas</label>
              <select name="kelas_id" class="form-control">
                <option value="">Semua</option>
                @foreach($kelasOptions as $k)
                  <option value="{{ $k->id }}" {{ (string)$kelasId === (string)$k->id ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-4">
              <label>Predikat</label>
              <select name="predikat" class="form-control">
                <option value="">Semua</option>
                @foreach($predikatOptions as $p)
                  <option value="{{ $p }}" {{ $predikat === $p ? 'selected' : '' }}>{{ $p }}</option>
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
          <a href="{{ route($routeBase.'.sikap.index') }}" class="btn btn-secondary">Reset</a>
          <button class="btn btn-primary">Terapkan</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
