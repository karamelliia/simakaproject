@extends('layouts.adminlte')
@section('title','Pengaturan Jam Pelajaran')

@section('content')
<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <div class="d-flex align-items-center">
      <a href="{{ route('admin.absensi.jadwal') }}" class="btn btn-link p-0 mr-2">
        <i class="fas fa-arrow-left"></i>
      </a>
      <div>
        <h4 class="mb-0">Pengaturan Jam Pelajaran</h4>
        <small class="text-muted">Waktu ini menentukan jam absensi guru yang sedang aktif.</small>
      </div>
    </div>
    <a href="{{ route('admin.absensi.jadwal') }}" class="btn btn-outline-secondary">
      <i class="fas fa-calendar-alt"></i> Kembali ke Jadwal
    </a>
  </div>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
  @endif
  @if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
  @endif

  <div class="alert alert-info">
    Cukup atur dua pola: <b>Senin–Kamis</b> sebanyak 10 jam dan <b>Jumat</b> sebanyak 8 jam.
    Waktu istirahat diperbolehkan dengan memberi jeda antara jam selesai dan jam mulai berikutnya.
  </div>

  <form method="POST" action="{{ route('admin.absensi.jadwal.jam.store') }}" id="formJamPelajaran">
    @csrf
    @method('PUT')

    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <b>Waktu Setiap Jam Pelajaran</b>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-bordered table-striped mb-0">
            <thead class="bg-dark text-white text-center">
              <tr>
                <th style="width:130px">Hari</th>
                <th style="width:100px">Jam Ke</th>
                <th>Jam Mulai</th>
                <th>Jam Selesai</th>
                <th style="width:150px">Durasi</th>
              </tr>
            </thead>
            <tbody>
              @foreach($polaRules as $polaKey => $pola)
                @for($jamKe = 1; $jamKe <= $pola['jumlah_jam']; $jamKe++)
                  @php
                    $slot = $polaJam[$polaKey . '_' . $jamKe] ?? null;
                    $mulai = old("jam.$polaKey.$jamKe.mulai", $slot?->jam_mulai ? substr($slot->jam_mulai, 0, 5) : '');
                    $selesai = old("jam.$polaKey.$jamKe.selesai", $slot?->jam_selesai ? substr($slot->jam_selesai, 0, 5) : '');
                  @endphp
                  <tr data-pola="{{ $polaKey }}" data-jam="{{ $jamKe }}">
                    @if($jamKe === 1)
                      <th rowspan="{{ $pola['jumlah_jam'] }}" class="align-middle text-center bg-light">
                        {{ $pola['label'] }}
                        <div class="small text-muted font-weight-normal">{{ $pola['jumlah_jam'] }} jam</div>
                      </th>
                    @endif
                    <td class="text-center align-middle"><b>{{ $jamKe }}</b></td>
                    <td>
                      <input type="time"
                             name="jam[{{ $polaKey }}][{{ $jamKe }}][mulai]"
                             value="{{ $mulai }}"
                             class="form-control jam-mulai"
                             required>
                    </td>
                    <td>
                      <input type="time"
                             name="jam[{{ $polaKey }}][{{ $jamKe }}][selesai]"
                             value="{{ $selesai }}"
                             class="form-control jam-selesai"
                             required>
                    </td>
                    <td class="align-middle text-center">
                      <span class="badge badge-secondary durasi">-</span>
                    </td>
                  </tr>
                @endfor
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
      <div class="card-footer text-right">
        <button type="submit" class="btn btn-primary">
          <i class="fas fa-save"></i> Simpan Pengaturan Jam
        </button>
      </div>
    </div>
  </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  function updateDuration(row) {
    const start = row.querySelector('.jam-mulai').value;
    const end = row.querySelector('.jam-selesai').value;
    const output = row.querySelector('.durasi');

    if (!start || !end) {
      output.textContent = '-';
      output.className = 'badge badge-secondary durasi';
      return;
    }

    const [startHour, startMinute] = start.split(':').map(Number);
    const [endHour, endMinute] = end.split(':').map(Number);
    const minutes = (endHour * 60 + endMinute) - (startHour * 60 + startMinute);

    output.textContent = minutes > 0 ? minutes + ' menit' : 'Tidak valid';
    output.className = 'badge durasi ' + (minutes > 0 ? 'badge-success' : 'badge-danger');
  }

  document.querySelectorAll('tbody tr[data-pola]').forEach(function (row) {
    row.querySelectorAll('input[type="time"]').forEach(function (input) {
      input.addEventListener('change', function () { updateDuration(row); });
    });
    updateDuration(row);
  });

});
</script>
@endpush
