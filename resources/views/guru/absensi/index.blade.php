@extends('layouts.adminlte')
@section('title','Absensi Mapel')

@push('styles')
<style>
  .attendance-page .attendance-summary .form-group {
    margin-bottom: 0;
  }

  .attendance-page .attendance-summary .form-control[readonly] {
    background-color: #f8fafc;
    color: #334155;
    font-weight: 600;
  }

  @media (max-width: 767.98px) {
    .attendance-page {
      padding-right: 0;
      padding-left: 0;
    }

    .attendance-page .attendance-title {
      font-size: 1.25rem;
    }

    .attendance-page .attendance-summary .form-group {
      margin-bottom: 0.85rem;
    }

    .attendance-page .attendance-summary .row:last-child .form-group:last-child {
      margin-bottom: 0;
    }

    .attendance-page .attendance-refresh {
      width: 100%;
      min-height: 44px;
    }

    .attendance-page .attendance-alert {
      font-size: 0.9rem;
      line-height: 1.45;
    }

    .attendance-page .attendance-table-wrap {
      overflow: visible;
      border: 0 !important;
    }

    .attendance-page .attendance-table,
    .attendance-page .attendance-table tbody,
    .attendance-page .attendance-table tr,
    .attendance-page .attendance-table td {
      display: block;
      width: 100%;
    }

    .attendance-page .attendance-table thead {
      display: none;
    }

    .attendance-page .attendance-table tbody {
      padding: 0.75rem;
    }

    .attendance-page .attendance-table tr {
      margin-bottom: 0.75rem;
      padding: 0.9rem;
      border: 1px solid #dce5eb;
      border-left: 4px solid var(--simaka-primary);
      border-radius: 0.75rem;
      background: #fff;
      box-shadow: 0 0.25rem 0.75rem rgba(35, 67, 89, 0.07);
    }

    .attendance-page .attendance-table tr:last-child {
      margin-bottom: 0;
    }

    .attendance-page .attendance-table td {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 1rem;
      padding: 0.45rem 0 !important;
      border: 0;
      text-align: right;
    }

    .attendance-page .attendance-table td::before {
      content: attr(data-label);
      flex: 0 0 4.5rem;
      color: #687985;
      font-size: 0.78rem;
      font-weight: 700;
      text-align: left;
      text-transform: uppercase;
    }

    .attendance-page .attendance-table td.attendance-action {
      display: block;
      padding-top: 0.75rem !important;
    }

    .attendance-page .attendance-table td.attendance-action::before {
      display: none;
    }

    .attendance-page .attendance-table .btn {
      width: 100%;
      min-height: 44px;
      padding-top: 0.65rem;
      padding-bottom: 0.65rem;
      font-weight: 700;
    }

    .attendance-page .attendance-table .attendance-empty {
      display: block;
      padding: 1.5rem 0.75rem !important;
      text-align: center;
    }

    .attendance-page .attendance-table .attendance-empty::before {
      display: none;
    }
  }
</style>
@endpush

@section('content')
<div class="container-fluid attendance-page">
  <div class="d-flex align-items-center mb-3">
    <h4 class="mb-0 attendance-title">Absensi Mapel</h4>
  </div>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  <div class="card mb-3 attendance-summary">
    <div class="card-body">
      <div class="row">
        <div class="col-md-4">
          <div class="form-group">
            <label>Tanggal</label>
            <input type="text" class="form-control" value="{{ \Carbon\Carbon::parse($tanggal)->translatedFormat('l, j F Y') }}" readonly>
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label>Hari</label>
            <input type="text" class="form-control" value="{{ $hari ?? '-' }}" readonly>
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label>Jam Sekarang</label>
            <input type="text" class="form-control" value="{{ $jamSekarang }}" readonly>
          </div>
        </div>
        <div class="col-md-2">
          <div class="form-group">
            <label>Slot Aktif</label>
            <input type="text" class="form-control" value="{{ $slotAktif ? 'Jam ke-' . $slotAktif->jam_ke . ' (' . substr($slotAktif->jam_mulai, 0, 5) . ' - ' . substr($slotAktif->jam_selesai, 0, 5) . ')' : 'Tidak ada' }}" readonly>
          </div>
        </div>
      </div>
      <div class="row mt-3">
        <div class="col-md-6">
          <div class="form-group">
            <label>Tahun Pelajaran Aktif</label>
            <input type="text" class="form-control" value="{{ $tahunAktif?->tahun_pelajaran }} - {{ $tahunAktif?->semester }}" readonly>
          </div>
        </div>
        <div class="col-md-6 d-flex align-items-end">
          <a href="{{ route('guru.absensi.index') }}" class="btn btn-primary attendance-refresh">
            <i class="fas fa-sync-alt mr-1"></i> Refresh
          </a>
        </div>
      </div>
    </div>
  </div>

  @if(!$slotAktif)
    <div class="alert alert-warning attendance-alert">
      Tidak ada jam pelajaran aktif saat ini. Absensi mapel hanya tampil ketika waktu sekarang berada di dalam slot jadwal yang sedang berlangsung.
    </div>
  @endif

  <div class="card">
    <div class="card-body table-responsive p-0 attendance-table-wrap">
      <table class="table table-bordered table-sm mb-0 attendance-table">
        <thead class="bg-dark text-white">
          <tr>
            <th style="width:70px;">Jam</th>
            <th>Kelas</th>
            <th>Mapel</th>
            <th style="width:160px;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse($jadwal as $j)
            <tr>
              <td data-label="Jam">
                {{ $j->jam_ke }}
                @if($slotAktif)
                  <div class="small text-muted">{{ substr($slotAktif->jam_mulai, 0, 5) }} - {{ substr($slotAktif->jam_selesai, 0, 5) }}</div>
                @endif
              </td>
              <td data-label="Kelas">{{ $j->kelas->nama_kelas ?? '-' }}</td>
              <td data-label="Mapel">{{ $j->mapel->nama_mapel ?? '-' }}</td>
              <td data-label="Aksi" class="attendance-action">
                <a href="{{ route('guru.absensi.input', ['jadwal' => $j->id, 'tanggal' => $tanggal]) }}" class="btn btn-success btn-sm">
                  <i class="fas fa-edit"></i> Input
                </a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="4" class="text-center text-muted py-4 attendance-empty">Tidak ada jadwal absensi mapel yang aktif saat ini.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
