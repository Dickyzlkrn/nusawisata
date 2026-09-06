@extends('layouts.admin')

@section('title', 'Dataset Wisata & Versioning — NusaWisata')
@section('page-title', 'Dataset Management')

@section('content')
<div class="admin-page-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 24px;">
    <div>
        <h1>Manajemen Dataset & Versioning</h1>
        <p style="color: var(--color-text-muted); font-size: 14px;">Kelola repositori dataset riset, riwayat versi, pengujian validasi, dan sinkronisasi model Machine Learning.</p>
    </div>
    <div>
        <a href="#uploadDatasetSection" class="btn-primary" style="display: inline-flex; align-items: center; gap: 8px; border-radius: var(--radius-pill); padding: 10px 20px;">
            <i class="fa-solid fa-cloud-arrow-up"></i> Upload Dataset Baru
        </a>
    </div>
</div>

<!-- Active Dataset Highlight Card -->
@if($activeDataset)
    <div class="detail-card" style="margin-bottom: 32px; background: #ffffff; box-shadow: 0 4px 16px -2px rgba(0,0,0,0.06);">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px; margin-bottom: 16px;">
            <div>
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
                    <span class="badge badge-success" style="background: #dcfce7; color: #15803d; font-weight: 700; padding: 4px 12px; border-radius: var(--radius-pill);">
                        <i class="fa-solid fa-circle-check"></i> DATASET AKTIF ({{ $activeDataset->version }})
                    </span>
                    <span style="font-size: 13px; color: #64748b;">
                        Diaktifkan: {{ $activeDataset->activated_at?->format('d M Y H:i') ?? '-' }}
                    </span>
                </div>
                <h2 style="font-size: 20px; font-weight: 700; color: #0f172a; margin: 0;">
                    {{ $activeDataset->name }}
                </h2>
                <div style="font-size: 13px; color: #64748b; margin-top: 4px;">
                    Berkas asli: <code>{{ $activeDataset->original_filename }}</code>
                </div>
            </div>

            <div style="display: flex; align-items: center; gap: 10px;">
                <form action="{{ route('admin.dataset.rebuild_ml', $activeDataset) }}" method="POST" style="margin: 0;" class="confirm-action" data-confirm-title="Rebuild K-Means?" data-confirm-text="Jalankan ulang clustering K-Means pada dataset aktif?" data-confirm-btn="Ya, Rebuild">
                    @csrf
                    <button type="submit" class="btn-outline" style="font-size: 13px; padding: 8px 16px; border-radius: var(--radius-pill);">
                        <i class="fa-solid fa-arrows-rotate" style="margin-right: 6px;"></i> Rebuild K-Means
                    </button>
                </form>
            </div>
        </div>

        <!-- Active Dataset Real Statistics Grid -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 16px; background: #f8fafc; padding: 18px 20px; border-radius: var(--radius-lg); border: 1px solid #e2e8f0;">
            <div>
                <div style="font-size: 12px; color: #64748b; text-transform: uppercase; font-weight: 600;">Destinasi Wisata</div>
                <div style="font-size: 22px; font-weight: 800; color: #0f172a; margin-top: 2px;">{{ number_format($totalDestinations) }}</div>
            </div>
            <div>
                <div style="font-size: 12px; color: #64748b; text-transform: uppercase; font-weight: 600;">Total Ulasan (Ratings)</div>
                <div style="font-size: 22px; font-weight: 800; color: #0f172a; margin-top: 2px;">{{ number_format($totalRatings) }}</div>
            </div>
            <div>
                <div style="font-size: 12px; color: #64748b; text-transform: uppercase; font-weight: 600;">Wisatawan Terdaftar</div>
                <div style="font-size: 22px; font-weight: 800; color: #0f172a; margin-top: 2px;">{{ number_format($totalUsers) }}</div>
            </div>
            <div>
                <div style="font-size: 12px; color: #64748b; text-transform: uppercase; font-weight: 600;">Cakupan Wilayah</div>
                <div style="font-size: 22px; font-weight: 800; color: #0f172a; margin-top: 2px;">{{ $totalProvinces }} / 38 Provinsi</div>
            </div>
        </div>
    </div>
@endif

<!-- Dataset Processing Pipeline Timeline -->
<div class="detail-card" style="margin-bottom: 28px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
        <h3 style="font-size: 16px; font-weight: 700; color: var(--color-primary-dark); margin: 0; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-timeline" style="color: #2563eb;"></i> Alur Pipeline Pemrosesan Dataset Riset
        </h3>
        <span class="badge badge-success" style="font-size: 11px;">Pipeline Terintegrasi</span>
    </div>
    <p style="font-size: 12.5px; color: var(--color-text-muted); margin-bottom: 16px;">
        Urutan tahapan otomatis dari upload file raw Excel hingga siap menjadi rujukan algoritma K-Means dan Collaborative Filtering.
    </p>

    <div style="display: flex; align-items: center; justify-content: space-between; gap: 6px; overflow-x: auto; padding-bottom: 8px;">
        @php
            $stages = [
                ['name' => 'Upload', 'icon' => 'fa-cloud-arrow-up'],
                ['name' => 'Validasi', 'icon' => 'fa-clipboard-check'],
                ['name' => 'Import', 'icon' => 'fa-file-import'],
                ['name' => 'Feature Eng', 'icon' => 'fa-gears'],
                ['name' => 'K-Means', 'icon' => 'fa-diagram-project'],
                ['name' => 'Evaluasi', 'icon' => 'fa-chart-line'],
                ['name' => 'CF Prep', 'icon' => 'fa-network-wired'],
                ['name' => 'Ready', 'icon' => 'fa-circle-check'],
                ['name' => 'Active', 'icon' => 'fa-bolt'],
            ];
        @endphp
        @foreach($stages as $idx => $st)
            <div style="display: flex; align-items: center; gap: 6px; flex-shrink: 0;">
                <div style="display: flex; flex-direction: column; align-items: center; text-align: center; background: #f8fafc; border: 1px solid #e2e8f0; padding: 8px 12px; border-radius: 8px; min-width: 85px;">
                    <i class="fa-solid {{ $st['icon'] }}" style="color: #2563eb; font-size: 14px; margin-bottom: 4px;"></i>
                    <span style="font-size: 11px; font-weight: 700; color: #0f172a;">{{ $st['name'] }}</span>
                    <span style="font-size: 9.5px; color: #16a34a; font-weight: 600;">✓ Selesai</span>
                </div>
                @if(!$loop->last)
                    <i class="fa-solid fa-arrow-right" style="color: #cbd5e1; font-size: 11px;"></i>
                @endif
            </div>
        @endforeach
    </div>
</div>

<!-- Dataset Versions History Table -->
<div class="detail-card" style="margin-bottom: 32px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <h3 style="font-size: 18px; font-weight: 700; color: var(--color-primary-dark); margin: 0;">
            Riwayat Versi Dataset (Dataset Versioning)
        </h3>
        <span style="font-size: 13px; color: var(--color-text-muted);">
            Total: {{ $datasetVersions->count() }} Versi
        </span>
    </div>

    <div class="data-table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Versi</th>
                    <th>Nama & File</th>
                    <th>Status</th>
                    <th>Destinasi</th>
                    <th>Ulasan</th>
                    <th>Pengguna</th>
                    <th>Provinsi</th>
                    <th>Waktu Upload</th>
                    <th style="text-align: right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($datasetVersions as $v)
                    <tr>
                        <td>
                            <strong style="font-size: 14px; color: #0f172a;">{{ $v->version }}</strong>
                        </td>
                        <td>
                            <div style="font-weight: 600; color: #0f172a;">{{ $v->name }}</div>
                            <div style="font-size: 12px; color: #64748b;">{{ $v->original_filename }}</div>
                            @if($v->error_message)
                                <div style="font-size: 11.5px; color: #b91c1c; margin-top: 2px;">
                                    <i class="fa-solid fa-triangle-exclamation"></i> {{ Str::limit($v->error_message, 60) }}
                                </div>
                            @endif
                        </td>
                        <td>
                            @if($v->status === 'active')
                                <span class="badge badge-success" style="background: #dcfce7; color: #15803d; font-weight: 700;">ACTIVE</span>
                            @elseif($v->status === 'ready')
                                <span class="badge badge-primary" style="background: #dbeafe; color: #1d4ed8; font-weight: 700;">READY</span>
                            @elseif($v->status === 'archived')
                                <span class="badge badge-secondary" style="background: #f1f5f9; color: #64748b;">ARCHIVED</span>
                            @elseif($v->status === 'failed')
                                <span class="badge badge-danger" style="background: #fee2e2; color: #b91c1c;">FAILED</span>
                            @else
                                <span class="badge badge-warning" style="background: #fef3c7; color: #b45309;">{{ strtoupper($v->status) }}</span>
                            @endif
                        </td>
                        <td>{{ number_format($v->destinations_count) }}</td>
                        <td>{{ number_format($v->ratings_count) }}</td>
                        <td>{{ number_format($v->users_count) }}</td>
                        <td>{{ $v->provinces_count }}</td>
                        <td style="font-size: 12.5px; color: #64748b;">
                            {{ $v->uploaded_at?->format('d M Y H:i') ?? '-' }}
                        </td>
                        <td style="text-align: right;">
                            <div style="display: inline-flex; align-items: center; gap: 6px;">
                                @if($v->status === 'ready')
                                    <form action="{{ route('admin.dataset.activate', $v) }}" method="POST" style="margin: 0;">
                                        @csrf
                                        <button type="submit" class="btn-primary" style="font-size: 12px; padding: 5px 12px; border-radius: var(--radius-pill);">
                                            <i class="fa-solid fa-play"></i> Aktifkan
                                        </button>
                                    </form>
                                @elseif($v->status === 'archived')
                                    <form action="{{ route('admin.dataset.rollback', $v) }}" method="POST" style="margin: 0;" class="confirm-action" data-confirm-title="Rollback Dataset?" data-confirm-text="Pulihkan versi ini menjadi dataset aktif?" data-confirm-btn="Ya, Pulihkan">
                                        @csrf
                                        <button type="submit" class="btn-outline" style="font-size: 12px; padding: 5px 12px; border-radius: var(--radius-pill);">
                                            <i class="fa-solid fa-clock-rotate-left"></i> Rollback
                                        </button>
                                    </form>
                                @elseif($v->status === 'failed')
                                    <form action="{{ route('admin.dataset.reprocess', $v) }}" method="POST" style="margin: 0;">
                                        @csrf
                                        <button type="submit" class="btn-outline" style="font-size: 12px; padding: 5px 12px; border-radius: var(--radius-pill); color: #2563eb; border-color: #93c5fd;" title="Proses Ulang Dataset">
                                            <i class="fa-solid fa-arrows-rotate"></i> Proses Ulang
                                        </button>
                                    </form>
                                @endif

                                @if($v->status !== 'failed')
                                    <form action="{{ route('admin.dataset.rebuild_ml', $v) }}" method="POST" style="margin: 0;">
                                        @csrf
                                        <button type="submit" class="btn-outline" style="font-size: 12px; padding: 5px 10px; border-radius: var(--radius-pill);" title="Rebuild Model K-Means">
                                            <i class="fa-solid fa-arrows-rotate"></i>
                                        </button>
                                    </form>
                                @endif

                                @if(!$v->isActive())
                                    <form action="{{ route('admin.dataset.destroy', $v) }}" method="POST" style="margin: 0;" class="confirm-action" data-confirm-title="Hapus Versi Dataset?" data-confirm-text="Versi dataset ini akan dihapus secara permanen." data-confirm-btn="Ya, Hapus" data-is-danger="true">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-outline" style="font-size: 12px; padding: 5px 10px; border-radius: var(--radius-pill); color: #ef4444; border-color: #fca5a5;" title="Hapus Dataset">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align: center; color: var(--color-text-muted); padding: 24px;">
                            Belum ada riwayat versi dataset.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 28px; margin-bottom: 32px;" id="uploadDatasetSection">
    <!-- Excel Dataset Upload Box -->
    <div class="detail-card">
        <h3 style="font-size: 18px; font-weight: 700; color: var(--color-primary-dark); margin-bottom: 8px; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-cloud-arrow-up" style="color: #2563eb;"></i> Upload & Validasi Dataset Baru
        </h3>
        <p style="font-size: 13px; line-height: 1.6; color: var(--color-text-muted); margin-bottom: 16px;">
            Unggah spreadsheet (.xlsx atau .csv) baru. Sistem akan memvalidasi skema kolom, membersihkan data, mengisolasi data dalam transaksi, dan melatih K-Means secara otomatis.
        </p>

        <form action="{{ route('admin.dataset.upload') }}" method="POST" enctype="multipart/form-data" id="datasetUploadForm" style="margin-bottom: 20px;">
            @csrf
            <div class="form-group" style="margin-bottom: 12px;">
                <label for="name" class="form-label">Nama Dataset / Keterangan (Opsional):</label>
                <input type="text" name="name" id="name" class="form-input" placeholder="Contoh: Dataset Wisata Indonesia 2026 Batch 2" style="padding: 10px;">
            </div>

            <div class="form-group" style="margin-bottom: 16px;">
                <label for="dataset_file" class="form-label">Pilih Berkas Spreadsheet (.xlsx / .csv):</label>
                <input type="file" name="dataset_file" id="dataset_file" class="form-input" accept=".xlsx,.csv,.txt" required style="padding: 10px;">
                <div style="font-size: 11.5px; color: #64748b; margin-top: 6px; line-height: 1.5;">
                    Mendukung 2 format: <strong>1) Interaksi Pengguna</strong> (<code>User_Id</code>, <code>Place_Id</code>, <code>Place_Ratings</code>, ...) atau <strong>2) Katalog Destinasi Wisata</strong> (<code>Place_Id</code>, <code>Place_Name</code>, <code>Province</code>, <code>Category</code>, <code>Price</code>, <code>Destination_Rating</code>, <code>Latitude</code>, <code>Longitude</code>, ...).
                </div>
            </div>

            <button type="submit" id="btnUploadDataset" class="btn-primary" style="border-radius: var(--radius-pill); width: 100%; justify-content: center; padding: 12px;">
                <i class="fa-solid fa-upload" style="margin-right: 6px;"></i> Validasi & Proses Dataset Baru
            </button>
        </form>

        @if($defaultDatasetExists)
            <div style="padding-top: 16px; border-top: 1px solid var(--color-border-light);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-weight: 700; font-size: 13px; color: var(--color-primary-dark);">
                            <i class="fa-solid fa-file-lines" style="color: #10b981; margin-right: 4px;"></i> Dataset Bawaan Riset
                        </div>
                        <div style="font-size: 12px; color: var(--color-text-muted);">
                            dataset_2000_wisata_38_provinsi.xlsx ({{ $defaultDatasetSize }})
                        </div>
                    </div>
                    <form action="{{ route('admin.dataset.import_default') }}" method="POST" style="margin: 0;" class="confirm-action" data-confirm-title="Import Ulang Default?" data-confirm-text="Proses ini akan mengimpor ulang dataset bawaan riset." data-confirm-btn="Ya, Impor">
                        @csrf
                        <button type="submit" class="btn-outline" style="font-size: 12px; padding: 6px 14px; border-radius: var(--radius-pill);">
                            <i class="fa-solid fa-arrows-rotate"></i> Import Ulang Default
                        </button>
                    </form>
                </div>
            </div>
        @endif
    </div>

    <!-- Crawler Google Maps Trigger Box -->
    <div class="detail-card">
        <h3 style="font-size: 18px; font-weight: 700; color: var(--color-primary-dark); margin-bottom: 8px; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-robot" style="color: var(--color-accent);"></i> Crawler Google Maps (Sinkronisasi)
        </h3>
        <p style="font-size: 13px; line-height: 1.6; color: var(--color-text-muted); margin-bottom: 16px;">
            Data Google Rating, Review Count, dan Popular Times harian diproses secara terpisah via pipeline scraping untuk menjaga kestabilan sistem rekomendasi.
        </p>

        <form action="{{ route('admin.dataset.crawl') }}" method="POST">
            @csrf
            <div class="form-group" style="margin-bottom: 16px;">
                <label for="province" class="form-label">Pilih Wilayah Sasaran Sinkronisasi:</label>
                <select name="province" id="province" class="form-select">
                    <option value="Semua Provinsi">Semua 38 Provinsi Nusantara</option>
                    <option value="Bali">Bali (Kawasan Khusus)</option>
                    <option value="DI Yogyakarta">DI Yogyakarta</option>
                    <option value="Jawa Timur">Jawa Timur</option>
                    <option value="Nusa Tenggara Timur">Nusa Tenggara Timur (Labuan Bajo)</option>
                    <option value="Papua Barat Daya">Papua Barat Daya (Raja Ampat)</option>
                </select>
            </div>

            <button type="submit" class="btn-outline" style="border-radius: var(--radius-pill); width: 100%; justify-content: center; height: 44px;">
                <i class="fa-solid fa-rotate" style="margin-right: 6px;"></i> Catat Log Sinkronisasi Eksternal
            </button>
        </form>
    </div>
</div>

<!-- Crawl & Import Logs Table -->
<div class="detail-card">
    <h3 style="font-size: 18px; font-weight: 700; color: var(--color-primary-dark); margin-bottom: 16px;">
        Riwayat Log Import & Sinkronisasi
    </h3>

    <div class="data-table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Sumber Data</th>
                    <th>Status</th>
                    <th>Item Diperbarui</th>
                    <th>Catatan / Pesan</th>
                    <th>Waktu Eksekusi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($crawlLogs as $log)
                    <tr>
                        <td><strong>{{ $log->source }}</strong></td>
                        <td>
                            <span class="badge badge-{{ $log->status === 'completed' ? 'success' : 'danger' }}">
                                {{ strtoupper($log->status) }}
                            </span>
                        </td>
                        <td>{{ number_format($log->items_crawled) }}</td>
                        <td style="font-size: 12.5px; color: var(--color-text-muted);">
                            {{ $log->error_message ?: 'Proses berjalan sukses tanpa kendala.' }}
                        </td>
                        <td style="font-size: 12.5px; color: var(--color-text-muted);">
                            {{ $log->started_at?->diffForHumans() ?? '-' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center; color: var(--color-text-muted); padding: 24px;">
                            Belum ada log sinkronisasi tercatat.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
