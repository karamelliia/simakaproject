@extends('layouts.adminlte')
@section('title','Kelola Ketidakhadiran')

@section('content')
<div class="container-fluid">

  {{-- HEADER: back + judul --}}
  <div class="d-flex align-items-center mb-3">
    <a href="{{ route('guru.wali-kelas.ketidakhadiran.index') }}"
       class="btn btn-link p-0 mr-2" title="Kembali">
      <i class="fas fa-arrow-left"></i>
    </a>
    <h4 class="mb-0">Data Ketidakhadiran</h4>
  </div>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  {{-- INFO KELAS --}}
  <div class="card mb-3">
    <div class="card-body">
      <div class="row">
        <div class="col-md-2 font-weight-bold">Kelas</div>
        <div class="col-md-10">: {{ $kelas->nama_kelas }}</div>

        <div class="col-md-2 font-weight-bold mt-2">Wali Kelas</div>
        <div class="col-md-10 mt-2">
          : {{ $namaWali ?? '-' }}

        </div>

        <div class="col-md-2 font-weight-bold mt-2">Tahun Pelajaran</div>
        <div class="col-md-10 mt-2">
          : {{ $tahunAktif->tahun_pelajaran }} - {{ $tahunAktif->semester }}
        </div>
      </div>
    </div>
  </div>

  <div class="card">

    {{-- toolbar: cari kanan --}}
    <div class="card-body pb-2">
      <div class="d-flex justify-content-end">
        <div style="width:220px;">
          <input type="text" class="form-control form-control-sm" id="searchSiswa" placeholder="Cari...">
        </div>
      </div>
    </div>

    <div class="card-body pt-0 table-responsive p-0">

      <form method="POST" action="{{ route('guru.wali-kelas.ketidakhadiran.update', $kelas->id) }}">
        @csrf

        <table class="table table-bordered table-sm mb-0" id="tableKetidakhadiran">
          <thead class="bg-dark text-white">
            <tr>
              <th style="width:60px;">No.</th>
              <th>Nama</th>
              <th style="width:140px;">NIS</th>
              <th style="width:90px;">L/P</th>
              <th style="width:90px;">Sakit</th>
              <th style="width:90px;">Izin</th>
              <th style="width:160px;">Tanpa Keterangan</th>
            </tr>
          </thead>

          <tbody>
            @foreach($siswa as $i => $s)
              @php $row = $data[$s->id] ?? null; @endphp
              <tr data-key="{{ strtolower(($s->nama_siswa ?? '').' '.($s->nis ?? '')) }}">
                <td class="text-center align-middle">{{ $i+1 }}</td>
                <td class="align-middle">{{ $s->nama_siswa }}</td>
                <td class="align-middle">{{ $s->nis ?? '-' }}</td>
                <td class="text-center align-middle">{{ $s->jenis_kelamin ?? '-' }}</td>

                <td class="align-middle">
                  <input type="number" min="0" class="form-control form-control-sm"
                         name="sakit[{{ $s->id }}]" value="{{ $row?->sakit ?? 0 }}">
                </td>
                <td class="align-middle">
                  <input type="number" min="0" class="form-control form-control-sm"
                         name="izin[{{ $s->id }}]" value="{{ $row?->izin ?? 0 }}">
                </td>
                <td class="align-middle">
                  <input type="number" min="0" class="form-control form-control-sm"
                         name="tanpa_keterangan[{{ $s->id }}]" value="{{ $row?->tanpa_keterangan ?? 0 }}">
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>

        <div class="p-3">
          <button class="btn btn-primary">
            <i class="fas fa-save"></i> Perbarui
          </button>
        </div>

      </form>
    </div>
  </div>

</div>
@endsection

@push('scripts')
<script>
(function () {
  const input = document.getElementById('searchSiswa');
  const table = document.getElementById('tableKetidakhadiran');
  if (!input || !table) return;

  input.addEventListener('input', function () {
    const q = (this.value || '').toLowerCase().trim();
    table.querySelectorAll('tbody tr').forEach(tr => {
      const key = tr.getAttribute('data-key') || '';
      tr.style.display = (!q || key.includes(q)) ? '' : 'none';
    });
  });
})();
</script>
@endpush
