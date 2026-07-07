@extends('layouts.adminlte')
@section('title','Setup Modul Absensi')

@section('content')
<div class="container-fluid">
  <div class="card">
    <div class="card-header">
      <h5 class="mb-0">Setup Modul Absensi</h5>
    </div>
    <div class="card-body">
      <div class="alert alert-warning">
        Tabel modul absensi per-jam belum tersedia di database.
      </div>

      <p class="mb-2">Jalankan perintah berikut di terminal project:</p>
      <pre class="p-3 bg-light border">php artisan migrate</pre>

      <p class="mb-0 text-muted">
        Setelah migrate selesai, refresh halaman ini.
      </p>
    </div>
  </div>
</div>
@endsection

