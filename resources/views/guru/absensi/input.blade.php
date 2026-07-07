@extends('layouts.adminlte')
@section('title','Input Absensi')

@push('styles')
<style>
  .attendance-input-page .attendance-detail-label {
    color: #687985;
  }

  .attendance-input-page .attendance-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 0.85rem 1rem;
    border-bottom: 1px solid #dce5eb;
    background: #f8fafc;
  }

  .attendance-input-page .attendance-status-options {
    display: grid;
    grid-template-columns: repeat(4, minmax(42px, 1fr));
    gap: 0.35rem;
    min-width: 220px;
  }

  .attendance-input-page .attendance-status-options input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
  }

  .attendance-input-page .attendance-status-option {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 38px;
    margin: 0;
    border: 1px solid #ced4da;
    border-radius: 0.45rem;
    color: #52616b;
    background: #fff;
    cursor: pointer;
    font-weight: 700;
    transition: color 0.15s ease, background-color 0.15s ease, border-color 0.15s ease, transform 0.15s ease;
  }

  .attendance-input-page .attendance-status-option:hover {
    border-color: #8da0ae;
    transform: translateY(-1px);
  }

  .attendance-input-page .attendance-status-options input:focus + .attendance-status-option {
    box-shadow: 0 0 0 0.2rem rgba(36, 85, 122, 0.16);
  }

  .attendance-input-page .attendance-status-options input[value="H"]:checked + .attendance-status-option {
    border-color: #28865f;
    color: #fff;
    background: #28865f;
  }

  .attendance-input-page .attendance-status-options input[value="S"]:checked + .attendance-status-option {
    border-color: #d29a28;
    color: #fff;
    background: #d29a28;
  }

  .attendance-input-page .attendance-status-options input[value="I"]:checked + .attendance-status-option {
    border-color: #347fa5;
    color: #fff;
    background: #347fa5;
  }

  .attendance-input-page .attendance-status-options input[value="A"]:checked + .attendance-status-option {
    border-color: #b84f4f;
    color: #fff;
    background: #b84f4f;
  }

  @media (max-width: 767.98px) {
    .attendance-input-page {
      padding-right: 0;
      padding-left: 0;
      padding-bottom: 4.75rem;
    }

    .attendance-input-page .attendance-title {
      font-size: 1.25rem;
    }

    .attendance-input-page .attendance-back {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 42px;
      height: 42px;
      border: 1px solid #dce5eb;
      border-radius: 50%;
      background: #fff;
    }

    .attendance-input-page .attendance-detail-row {
      padding: 0.55rem 0;
      border-bottom: 1px solid #edf1f4;
    }

    .attendance-input-page .attendance-detail-row:last-child {
      border-bottom: 0;
    }

    .attendance-input-page .attendance-detail-label,
    .attendance-input-page .attendance-detail-value {
      margin-top: 0 !important;
    }

    .attendance-input-page .attendance-detail-label {
      margin-bottom: 0.2rem;
      font-size: 0.78rem;
      text-transform: uppercase;
    }

    .attendance-input-page .attendance-table-wrap {
      overflow: visible;
      border: 0 !important;
    }

    .attendance-input-page .attendance-toolbar {
      display: block;
      padding: 0.75rem;
    }

    .attendance-input-page .attendance-toolbar-text {
      display: block;
      margin-bottom: 0.6rem;
      font-size: 0.85rem;
    }

    .attendance-input-page .attendance-all-present {
      width: 100%;
      min-height: 42px;
    }

    .attendance-input-page .attendance-input-table,
    .attendance-input-page .attendance-input-table tbody,
    .attendance-input-page .attendance-input-table tr,
    .attendance-input-page .attendance-input-table td {
      display: block;
      width: 100%;
    }

    .attendance-input-page .attendance-input-table thead {
      display: none;
    }

    .attendance-input-page .attendance-input-table tbody {
      padding: 0.75rem;
      counter-reset: student-card;
    }

    .attendance-input-page .attendance-input-table tr {
      position: relative;
      margin-bottom: 0.85rem;
      padding: 1rem;
      border: 1px solid #dce5eb;
      border-radius: 0.8rem;
      background: #fff;
      box-shadow: 0 0.25rem 0.75rem rgba(35, 67, 89, 0.07);
      counter-increment: student-card;
    }

    .attendance-input-page .attendance-input-table tr:last-child {
      margin-bottom: 0;
    }

    .attendance-input-page .attendance-input-table td {
      padding: 0 !important;
      border: 0;
    }

    .attendance-input-page .attendance-input-table .student-number {
      position: absolute;
      top: 1rem;
      right: 1rem;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 2rem;
      height: 2rem;
      border-radius: 50%;
      color: var(--simaka-primary);
      background: #e7f0f7;
      font-size: 0.8rem;
      font-weight: 700;
    }

    .attendance-input-page .attendance-input-table .student-name {
      padding-right: 2.75rem !important;
      color: #243746;
      font-size: 1rem;
      font-weight: 700;
    }

    .attendance-input-page .attendance-input-table .student-nis {
      margin-top: 0.15rem;
      color: #687985;
      font-size: 0.82rem;
    }

    .attendance-input-page .attendance-input-table .student-nis::before {
      content: "NIS: ";
    }

    .attendance-input-page .attendance-input-table .student-field {
      margin-top: 0.85rem;
    }

    .attendance-input-page .attendance-status-options {
      min-width: 0;
      width: 100%;
      gap: 0.45rem;
    }

    .attendance-input-page .attendance-status-option {
      min-height: 46px;
      font-size: 1rem;
    }

    .attendance-input-page .attendance-input-table .student-field::before {
      content: attr(data-label);
      display: block;
      margin-bottom: 0.35rem;
      color: #687985;
      font-size: 0.75rem;
      font-weight: 700;
      text-transform: uppercase;
    }

    .attendance-input-page .attendance-input-table .form-control {
      min-height: 44px;
      font-size: 16px;
    }

    .attendance-input-page .attendance-input-table .attendance-empty {
      padding: 1.5rem 0.75rem !important;
      text-align: center;
    }

    .attendance-input-page .attendance-card-footer {
      position: fixed;
      right: 0;
      bottom: 0;
      left: 0;
      z-index: 1030;
      padding: 0.7rem 1rem !important;
      border-top: 1px solid #dce5eb !important;
      background: rgba(255, 255, 255, 0.96) !important;
      box-shadow: 0 -0.35rem 1rem rgba(35, 67, 89, 0.12);
    }

    .attendance-input-page .attendance-save {
      width: 100%;
      min-height: 46px;
      font-weight: 700;
    }
  }
</style>
@endpush

@section('content')
<div class="container-fluid attendance-input-page">
  <div class="d-flex align-items-center mb-3">
    <a href="{{ route('guru.absensi.index', ['tanggal' => $tanggal]) }}" class="btn btn-link p-0 mr-2 attendance-back" aria-label="Kembali">
      <i class="fas fa-arrow-left"></i>
    </a>
    <h4 class="mb-0 attendance-title">Input Absensi</h4>
  </div>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  <div class="card mb-3">
    <div class="card-body">
      <div class="attendance-detail-row row">
        <div class="col-md-2 font-weight-bold attendance-detail-label">Kelas</div>
        <div class="col-md-10 attendance-detail-value">{{ $jadwal->kelas->nama_kelas ?? '-' }}</div>
      </div>
      <div class="attendance-detail-row row">
        <div class="col-md-2 font-weight-bold attendance-detail-label">Mapel</div>
        <div class="col-md-10 attendance-detail-value">{{ $jadwal->mapel->nama_mapel ?? '-' }}</div>
      </div>
      <div class="attendance-detail-row row">
        <div class="col-md-2 font-weight-bold attendance-detail-label">Hari/Tanggal</div>
        <div class="col-md-10 attendance-detail-value">{{ $hari }}, {{ \Carbon\Carbon::parse($tanggal)->translatedFormat('d F Y') }}</div>
      </div>
      <div class="attendance-detail-row row">
        <div class="col-md-2 font-weight-bold attendance-detail-label">Jam Ke</div>
        <div class="col-md-10 attendance-detail-value">
          {{ $jadwal->jam_ke }}
          @if($slotJadwal)
            <span class="text-muted">({{ substr($slotJadwal->jam_mulai, 0, 5) }} - {{ substr($slotJadwal->jam_selesai, 0, 5) }})</span>
          @endif
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <form method="POST" action="{{ route('guru.absensi.store', $jadwal->id) }}">
      @csrf
      <input type="hidden" name="tanggal" value="{{ $tanggal }}">

      <div class="attendance-toolbar">
        <span class="attendance-toolbar-text text-muted">
          Pilih <strong>H</strong> Hadir, <strong>S</strong> Sakit, <strong>I</strong> Izin, atau <strong>A</strong> Alpa.
        </span>
        <button type="button" class="btn btn-outline-success btn-sm attendance-all-present" id="set-all-present">
          <i class="fas fa-user-check mr-1"></i> Semua Hadir
        </button>
      </div>

      <div class="card-body table-responsive p-0 attendance-table-wrap">
        <table class="table table-bordered table-sm mb-0 attendance-input-table">
          <thead class="bg-dark text-white">
            <tr>
              <th style="width:50px;">#</th>
              <th>Nama Siswa</th>
              <th style="width:140px;">NIS</th>
              <th style="width:240px;">Status</th>
              <th>Catatan</th>
            </tr>
          </thead>
          <tbody>
            @forelse($siswa as $i => $s)
              @php $old = $existing[$s->id] ?? null; @endphp
              <tr>
                <td class="student-number">{{ $i + 1 }}</td>
                <td class="student-name">{{ $s->nama_siswa }}</td>
                <td class="student-nis">{{ $s->nis ?? '-' }}</td>
                <td class="student-field" data-label="Status Kehadiran">
                  @php $val = old("status.{$s->id}", $old?->status ?? 'H'); @endphp
                  <div class="attendance-status-options" role="radiogroup" aria-label="Status kehadiran {{ $s->nama_siswa }}">
                    @foreach(['H' => 'Hadir', 'S' => 'Sakit', 'I' => 'Izin', 'A' => 'Alpa'] as $statusCode => $statusLabel)
                      <input type="radio"
                             name="status[{{ $s->id }}]"
                             id="status-{{ $s->id }}-{{ $statusCode }}"
                             value="{{ $statusCode }}"
                             {{ $val === $statusCode ? 'checked' : '' }}>
                      <label class="attendance-status-option"
                             for="status-{{ $s->id }}-{{ $statusCode }}"
                             title="{{ $statusLabel }}">
                        {{ $statusCode }}
                        <span class="sr-only">{{ $statusLabel }}</span>
                      </label>
                    @endforeach
                  </div>
                </td>
                <td class="student-field" data-label="Catatan">
                  <input type="text" name="catatan[{{ $s->id }}]" class="form-control form-control-sm"
                         value="{{ old("catatan.{$s->id}", $old?->catatan) }}" maxlength="255"
                         placeholder="Opsional" aria-label="Catatan untuk {{ $s->nama_siswa }}">
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="text-center text-muted py-4 attendance-empty">Tidak ada siswa pada kelas ini.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
      <div class="card-footer attendance-card-footer">
        <button class="btn btn-primary attendance-save"><i class="fas fa-save"></i> Simpan Absensi</button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    var setAllPresentButton = document.getElementById('set-all-present');

    if (!setAllPresentButton) {
      return;
    }

    setAllPresentButton.addEventListener('click', function () {
      document.querySelectorAll('.attendance-status-options input[value="H"]').forEach(function (input) {
        input.checked = true;
      });
    });
  });
</script>
@endpush
