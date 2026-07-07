@extends('layouts.adminlte')
@section('title', 'Pengaturan Rekomendasi PKL')
@section('page_title', 'Pengaturan Rekomendasi PKL')

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

<div class="card">
  <div class="card-header">
    <h3 class="card-title">Konfigurasi Perhitungan</h3>
  </div>
  <div class="card-body">
    <form method="POST" action="{{ route('admin.bk.rekomendasi-pkl.settings.update') }}">
      @csrf
      @method('PUT')

      <h5 class="mb-3">Bobot Komponen (%)</h5>
      <div class="row">
        <div class="col-md-6">
          <label>Kehadiran</label>
          <input type="number" step="0.01" min="0" max="100" name="weight_kehadiran" class="form-control" value="{{ old('weight_kehadiran', $weights['kehadiran']) }}" required>
        </div>
        <div class="col-md-6">
          <label>Sikap</label>
          <input type="number" step="0.01" min="0" max="100" name="weight_sikap" class="form-control" value="{{ old('weight_sikap', $weights['sikap']) }}" required>
        </div>
      </div>
      <small class="text-muted">Kedua bobot dinormalisasi menjadi 100%. Penalti BK dikurangkan setelah nilai dasar dihitung.</small>

      <h5 class="mt-4 mb-3">Batas Grade</h5>
      <div class="row">
        <div class="col-md-3">
          <label>A</label>
          <input type="number" step="0.01" min="0" max="100" name="grade_a" class="form-control" value="{{ old('grade_a', $thresholds['A']) }}" required>
        </div>
        <div class="col-md-3">
          <label>B</label>
          <input type="number" step="0.01" min="0" max="100" name="grade_b" class="form-control" value="{{ old('grade_b', $thresholds['B']) }}" required>
        </div>
        <div class="col-md-3">
          <label>C</label>
          <input type="number" step="0.01" min="0" max="100" name="grade_c" class="form-control" value="{{ old('grade_c', $thresholds['C']) }}" required>
        </div>
        <div class="col-md-3">
          <label>D</label>
          <input type="number" step="0.01" min="0" max="100" name="grade_d" class="form-control" value="{{ old('grade_d', $thresholds['D']) }}" required>
        </div>
      </div>

      <div class="alert alert-info mt-4 mb-0">
        Jika data kehadiran atau sikap belum tersedia, nilai dan grade PKL tidak dihitung sampai data dilengkapi.
      </div>

      <h5 class="mt-4 mb-3">Kebijakan Penalti BK</h5>
      <div class="table-responsive">
        <table class="table table-sm table-bordered mb-2">
          <thead>
            <tr><th>Poin BK Aktif</th><th>Penalti</th><th>Pembatas Grade</th></tr>
          </thead>
          <tbody>
            <tr><td>0</td><td>0</td><td>-</td></tr>
            <tr><td>1-24</td><td>-2</td><td>-</td></tr>
            <tr><td>25-49</td><td>-5</td><td>-</td></tr>
            <tr><td>50-99</td><td>-10</td><td>-</td></tr>
            <tr><td>100-199</td><td>-15</td><td>-</td></tr>
            <tr><td>200-399</td><td>-25</td><td>Maksimal C</td></tr>
            <tr><td>400-699</td><td>-30</td><td>Maksimal D</td></tr>
            <tr><td>700-9.999</td><td>-40</td><td>Maksimal E</td></tr>
            <tr><td>Terminal 10.000</td><td>Nilai menjadi 0</td><td>Grade E</td></tr>
          </tbody>
        </table>
      </div>
      <small class="text-muted">
        Poin ringan dibatasi 500 per tahun dan turun 50% setelah 3 bulan tanpa pelanggaran.
        Pelanggaran absensi tidak dihitung lagi sebagai poin PKL agar tidak terjadi pengurangan ganda.
      </small>

      <div class="mt-4 d-flex">
        <button class="btn btn-primary mr-2">
          <i class="fas fa-save"></i> Simpan Pengaturan
        </button>
        <a href="{{ route('admin.bk.rekomendasi-pkl.index') }}" class="btn btn-secondary">Kembali</a>
      </div>
    </form>
  </div>
</div>
@endsection
