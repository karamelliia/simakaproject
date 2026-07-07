@extends('layouts.adminlte')
@section('title','Jadwal Absensi')

@section('content')
<div class="container-fluid">
  <div class="d-flex align-items-center mb-3">
    <a href="{{ route('admin.dashboard') }}" class="btn btn-link p-0 mr-2" title="Kembali ke Dashboard">
      <i class="fas fa-arrow-left"></i>
    </a>
    <h4 class="mb-0">Jadwal Pelajaran Untuk Absensi</h4>
  </div>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
  @endif
  @if($errors->any())
    <div class="alert alert-danger">
      {{ $errors->first() }}
    </div>
  @endif

  <div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
      <b>Pengisian Jadwal Per Kelas</b>
      <div>
        <a href="{{ route('admin.absensi.jadwal.jam') }}"
           class="btn btn-warning btn-sm mr-2"
           title="Atur waktu mulai dan selesai setiap jam pelajaran">
          <i class="fas fa-clock"></i> Pengaturan Jam Pelajaran
        </a>
        <a href="{{ route('admin.absensi.jadwal.export.assignment', $selectedKelasId ? ['kelas_id' => $selectedKelasId] : []) }}"
           class="btn btn-success btn-sm mr-2"
           title="Ekspor jadwal dalam format XLSX yang dapat langsung diimpor kembali">
          <i class="fas fa-file-excel"></i> Ekspor Jadwal
        </a>
        <button type="button" class="btn btn-info btn-sm" data-toggle="modal" data-target="#modalImportJadwal">
          <i class="fas fa-file-import"></i> Impor Jadwal
        </button>
      </div>
    </div>
    <div class="card-body">
      <div class="alert alert-info py-2 mb-3">
        Alur pengisian: <b>pilih kelas</b>, lalu pilih <b>pembelajaran (mapel — guru)</b> yang sudah ditetapkan pada Data Pembelajaran.
        Dengan begitu guru dan mata pelajaran tidak perlu dipilih dua kali dan tidak dapat tertukar.
      </div>

      <form method="GET" action="{{ route('admin.absensi.jadwal') }}" class="mb-3">
        <div class="row">
          <div class="col-md-4">
            <label>Pilih Kelas</label>
            <select name="kelas_id" class="form-control" onchange="this.form.submit()" required>
              <option value="">-- pilih kelas --</option>
              @foreach($kelas as $k)
                <option value="{{ $k->id }}" {{ (string) $selectedKelasId === (string) $k->id ? 'selected' : '' }}>
                  {{ $k->nama_kelas }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="col-md-8 d-flex align-items-end">
            <div class="text-muted">
              Format jadwal absensi: <b>Senin - Kamis 10 jam pelajaran</b>, <b>Jumat 8 jam pelajaran</b>.
            </div>
          </div>
        </div>
      </form>

      @if($selectedKelas)
        @if($pembelajaranOptions->isEmpty())
          <div class="alert alert-warning">
            Belum ada Data Pembelajaran yang valid untuk kelas <b>{{ $selectedKelas->nama_kelas }}</b>.
            Tambahkan pasangan mata pelajaran dan guru pada menu <b>Data Pembelajaran</b> terlebih dahulu.
          </div>
        @endif

        <form method="POST" action="{{ route('admin.absensi.jadwal.store') }}">
          @csrf
          <input type="hidden" name="data_kelas_id" value="{{ $selectedKelas->id }}">

          <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
              <b>Kelas {{ $selectedKelas->nama_kelas }}</b>
              <div class="text-muted small">Setiap kotak menggunakan pasangan mapel dan guru dari Data Pembelajaran kelas ini.</div>
            </div>
            <button class="btn btn-primary" {{ $pembelajaranOptions->isEmpty() ? 'disabled' : '' }}>
              <i class="fas fa-save"></i> Simpan
            </button>
          </div>

          <div class="table-responsive">
            <table class="table table-bordered table-sm mb-0">
              <thead class="bg-dark text-white text-center">
                <tr>
                  <th style="width: 90px;">Jam</th>
                  @foreach($slotRules as $hari => $jamList)
                    <th>{{ $hari }}</th>
                  @endforeach
                </tr>
              </thead>
              <tbody>
                @for($jam = 1; $jam <= 10; $jam++)
                  <tr>
                    <th class="bg-light text-center align-middle">Jam {{ $jam }}</th>
                    @foreach($slotRules as $hari => $jamList)
                      @php
                        $slot = $jadwalPerSlot[$hari . '_' . $jam] ?? null;
                        $allowed = in_array($jam, $jamList, true);
                        $pairKey = $slot ? ($slot->data_mapel_id . '|' . $slot->guru_id) : '';
                        $defaultPembelajaranId = $pembelajaranIdByPair[$pairKey] ?? '';
                        $oldPembelajaran = old("slots.$hari.$jam.data_pembelajaran_id", $defaultPembelajaranId);
                      @endphp
                      <td class="align-top" style="min-width: 220px;">
                        @if(!$allowed)
                          <div class="text-center text-muted py-4">Tidak ada slot</div>
                        @else
                          @if($slot)
                            <div class="small text-muted mb-2">Jadwal tersimpan, bisa diedit.</div>
                          @else
                            <div class="small text-muted mb-2">Slot kosong.</div>
                          @endif
                          <div class="form-group mb-0">
                            <select name="slots[{{ $hari }}][{{ $jam }}][data_pembelajaran_id]"
                                    class="form-control form-control-sm"
                                    {{ $pembelajaranOptions->isEmpty() ? 'disabled' : '' }}>
                              <option value="">Pilih pembelajaran</option>
                              @foreach($pembelajaranOptions as $pembelajaran)
                                <option value="{{ $pembelajaran->id }}" {{ (string) $oldPembelajaran === (string) $pembelajaran->id ? 'selected' : '' }}>
                                  {{ $pembelajaran->mapel->nama_mapel }} — {{ $pembelajaran->guru->nama }}
                                </option>
                              @endforeach
                            </select>
                          </div>
                        @endif
                      </td>
                    @endforeach
                  </tr>
                @endfor
              </tbody>
            </table>
          </div>

          <div class="mt-3">
            <button class="btn btn-primary" {{ $pembelajaranOptions->isEmpty() ? 'disabled' : '' }}>
              <i class="fas fa-save"></i> Simpan
            </button>
          </div>
        </form>
      @else
        <div class="alert alert-secondary mb-0">
          Pilih kelas terlebih dahulu untuk menampilkan tabel jadwal mingguan.
        </div>
      @endif
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-header">
      <b>Cek Jadwal</b>
    </div>
    <div class="card-body">
      <form method="GET" action="{{ route('admin.absensi.jadwal') }}">
        @if($selectedKelasId)
          <input type="hidden" name="kelas_id" value="{{ $selectedKelasId }}">
        @endif
        <div class="d-flex align-items-end">
          <button type="submit" name="check" value="1" class="btn btn-success mr-2">
            <i class="fas fa-clipboard-check"></i> Cek Jadwal Terpenuhi
          </button>
          <a href="{{ route('admin.absensi.jadwal') }}" class="btn btn-secondary">
            Reset
          </a>
        </div>
      </form>
    </div>
  </div>

  @if($check)
    <div class="card mb-3">
      <div class="card-header">
        <b>Hasil Cek Kelengkapan Jadwal</b>
      </div>
      <div class="card-body">
        @php
          $kelasLengkap = collect($kelengkapanJadwal)->where('lengkap', true)->count();
          $kelasBelumLengkap = collect($kelengkapanJadwal)->where('lengkap', false)->count();
        @endphp

        <div class="mb-3">
          <span class="badge badge-success mr-2">Lengkap: {{ $kelasLengkap }} kelas</span>
          <span class="badge badge-warning">Belum lengkap: {{ $kelasBelumLengkap }} kelas</span>
        </div>

        @if($kelasBelumLengkap === 0)
          <div class="alert alert-success mb-0">
            Semua kelas sudah memenuhi format jadwal absensi.
          </div>
        @else
          <div class="table-responsive">
            <table class="table table-bordered table-sm mb-0">
              <thead class="bg-light">
                <tr>
                  <th style="width: 60px;">#</th>
                  <th>Kelas</th>
                  <th>Status</th>
                  <th>Terisi</th>
                  <th>Jadwal Yang Belum Terisi</th>
                </tr>
              </thead>
              <tbody>
                @foreach($kelengkapanJadwal as $index => $item)
                  <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item['kelas']->nama_kelas }}</td>
                    <td>
                      @if($item['lengkap'])
                        <span class="badge badge-success">Lengkap</span>
                      @else
                        <span class="badge badge-warning">Belum lengkap</span>
                      @endif
                    </td>
                    <td>{{ $item['total_terisi'] }}/{{ $item['total_wajib'] }}</td>
                    <td>
                      @if($item['lengkap'])
                        <span class="text-success">Semua slot sudah terisi.</span>
                      @else
                        @foreach($item['detail_kosong'] as $detail)
                          <div>
                            <b>{{ $detail['hari'] }}</b>: jam {{ implode(', ', $detail['jam']) }}
                          </div>
                        @endforeach
                      @endif
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @endif
      </div>
    </div>
  @endif

</div>

<div class="modal fade" id="modalImportJadwal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Import Jadwal Pembelajaran Absensi</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <form method="POST" action="{{ route('admin.absensi.jadwal.import') }}" enctype="multipart/form-data">
        @csrf
        <div class="modal-body">
          <div class="alert alert-warning">
            File harus <b>.xlsx</b>. Gunakan format ini:
            <a href="{{ route('admin.absensi.jadwal.import.format') }}">Download Format Import</a>
          </div>

          <div class="form-group">
            <label>File XLSX</label>
            <input type="file" name="file" class="form-control" accept=".xlsx" required>
          </div>

          <div class="custom-control custom-checkbox">
            <input type="checkbox" class="custom-control-input" id="check-import-jadwal" name="yakin" value="1" required>
            <label class="custom-control-label" for="check-import-jadwal">
              Saya yakin data import sudah benar
            </label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
          <button type="submit" class="btn btn-primary">Import</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
