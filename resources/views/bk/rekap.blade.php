@extends('layouts.adminlte')
@section('title', 'Rekap Pelanggaran Siswa')
@section('page_title', 'Rekap Pelanggaran Siswa')
@section('content')
<div class="row">
@foreach([
 ['value'=>$statistik['jumlah_siswa'],'label'=>'Siswa Tercatat','color'=>'bg-info'],
 ['value'=>$statistik['jumlah_pelanggaran'],'label'=>'Jumlah Pelanggaran','color'=>'bg-warning'],
 ['value'=>$statistik['total_poin'],'label'=>'Akumulasi Poin','color'=>'bg-danger']
] as $card)
<div class="col-md-4"><div class="small-box {{ $card['color'] }}">
  <div class="inner"><h3>{{ $card['value'] }}</h3><p>{{ $card['label'] }}</p></div>
</div></div>
@endforeach
</div>

<div class="card"><div class="card-header"><h3 class="card-title">Filter Rekap</h3></div><div class="card-body">
<form method="GET" action="{{ route($routeBase.'.index') }}"><div class="row">
<div class="col-md-3 form-group"><label>Tahun Pelajaran</label><select name="tahun_id" class="form-control"><option value="">Semua tahun</option>
@foreach($tahunOptions as $tahun)<option value="{{ $tahun->id }}" {{ (string)$filters['tahun_id']===(string)$tahun->id?'selected':'' }}>{{ $tahun->tahun_pelajaran }} - {{ $tahun->semester }}</option>@endforeach
</select></div>
<div class="col-md-2 form-group"><label>Semester</label><select name="semester" class="form-control"><option value="">Semua</option>
@foreach(['Ganjil','Genap'] as $semester)<option value="{{ $semester }}" {{ $filters['semester']===$semester?'selected':'' }}>{{ $semester }}</option>@endforeach
</select></div>
<div class="col-md-3 form-group"><label>Kelas</label><select name="kelas_id" class="form-control"><option value="">Semua kelas</option>
@foreach($kelasOptions as $kelas)<option value="{{ $kelas->id }}" {{ (string)$filters['kelas_id']===(string)$kelas->id?'selected':'' }}>{{ $kelas->nama_kelas }}</option>@endforeach
</select></div>
<div class="col-md-4 form-group"><label>Siswa</label><select name="siswa_id" class="form-control"><option value="">Semua siswa</option>
@foreach($siswaOptions as $siswa)<option value="{{ $siswa->id }}" {{ (string)$filters['siswa_id']===(string)$siswa->id?'selected':'' }}>{{ $siswa->nama_siswa }} ({{ $siswa->nis ?? '-' }})</option>@endforeach
</select></div></div>
<div class="row"><div class="col-md-3 form-group"><label>Tanggal Dari</label><input type="date" name="tanggal_dari" value="{{ $filters['tanggal_dari'] }}" class="form-control"></div>
<div class="col-md-3 form-group"><label>Tanggal Sampai</label><input type="date" name="tanggal_sampai" value="{{ $filters['tanggal_sampai'] }}" class="form-control"></div>
<div class="col-md-6 d-flex align-items-end justify-content-end form-group"><a href="{{ route($routeBase.'.index') }}" class="btn btn-light mr-2">Reset</a><button class="btn btn-primary mr-2"><i class="fas fa-filter"></i> Terapkan</button><a href="{{ route($routeBase.'.pdf',request()->query()) }}" target="_blank" class="btn btn-danger"><i class="fas fa-file-pdf"></i> Export PDF</a></div></div>
</form></div></div>
<div class="card"><div class="card-header"><h3 class="card-title">Hasil Rekap per Siswa</h3></div>
<div class="card-body table-responsive p-0"><table class="table table-bordered table-hover mb-0">
<thead><tr><th>No</th><th>NIS</th><th>Nama Siswa</th><th>Kelas</th><th class="text-center">Jumlah Pelanggaran</th><th class="text-center">Total Poin</th><th>Terakhir</th></tr></thead>
<tbody>@forelse($rekap as $i => $row)
<tr><td>{{ $rekap->firstItem()+$i }}</td><td>{{ $row->siswa->nis ?? '-' }}</td><td>{{ $row->siswa->nama_siswa ?? '-' }}</td><td>{{ $row->siswa?->kelas?->nama_kelas ?? '-' }}</td><td class="text-center"><span class="badge badge-warning">{{ $row->jumlah_pelanggaran }}</span></td><td class="text-center"><span class="badge badge-danger">{{ $row->total_poin }}</span></td><td>{{ $row->pelanggaran_terakhir ? \Carbon\Carbon::parse($row->pelanggaran_terakhir)->translatedFormat('d F Y') : '-' }}</td></tr>
@empty<tr><td colspan="7" class="text-center text-muted py-4">Belum ada data pelanggaran sesuai filter.</td></tr>@endforelse</tbody>
</table></div>
@if($rekap->hasPages())<div class="card-footer">{{ $rekap->links() }}</div>@endif
</div>
@endsection
