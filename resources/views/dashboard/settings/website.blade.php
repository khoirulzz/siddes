@extends('layouts.dashboard')

@section('title', 'Pengaturan Website')
@section('page_title', 'Pengaturan Website')

@section('content')
    <section class="panel settings-hero">
        <div>
            <h2>Pusat Pengaturan Website Desa</h2>
            <p class="muted">
                Kelola identitas desa, media utama, identitas kepala desa, dan data perangkat desa.
                Semua perubahan akan langsung terhubung ke halaman profil publik dan dokumen surat.
            </p>
        </div>
    </section>

    <div class="settings-shell">
        <section class="panel settings-card">
            <div class="settings-card-head">
                <h3>Informasi Desa</h3>
                <p class="muted">Data ini muncul di header, footer, dan informasi umum website.</p>
            </div>

            <form method="POST" action="{{ route('dashboard.website-settings.update-info') }}">
                @csrf
                @method('PUT')

                <div class="form-grid">
                    <div class="field">
                        <label for="name">Nama Desa</label>
                        <input id="name" name="name" type="text" value="{{ old('name', $info['name']) }}" required>
                    </div>
                    <div class="field">
                        <label for="district">Kabupaten / Wilayah</label>
                        <input id="district" name="district" type="text" value="{{ old('district', $info['district']) }}" required>
                    </div>
                    <div class="field">
                        <label for="phone">Nomor Telepon</label>
                        <input id="phone" name="phone" type="text" value="{{ old('phone', $info['phone']) }}" required>
                    </div>
                    <div class="field">
                        <label for="email">Email Resmi</label>
                        <input id="email" name="email" type="email" value="{{ old('email', $info['email']) }}" required>
                    </div>
                    <div class="field">
                        <label for="instagram_url">URL Instagram</label>
                        <input id="instagram_url" name="instagram_url" type="url" value="{{ old('instagram_url', $info['instagram_url']) }}">
                    </div>
                    <div class="field">
                        <label for="facebook_url">URL Facebook</label>
                        <input id="facebook_url" name="facebook_url" type="url" value="{{ old('facebook_url', $info['facebook_url']) }}">
                    </div>
                    <div class="field full">
                        <label for="map_link_url">URL Google Maps</label>
                        <input id="map_link_url" name="map_link_url" type="url" value="{{ old('map_link_url', $info['map_link_url']) }}">
                    </div>
                    <div class="field full">
                        <label for="address">Alamat Kantor Desa</label>
                        <textarea id="address" name="address" required>{{ old('address', $info['address']) }}</textarea>
                    </div>
                </div>

                <div class="actions" style="margin-top:0.85rem;">
                    <button class="btn btn-primary" type="submit">Simpan Informasi Desa</button>
                </div>
            </form>
        </section>

        <section class="panel settings-card">
            <div class="settings-card-head">
                <h3>Identitas Kepala Desa</h3>
                <p class="muted">
                    Digunakan di halaman profil desa dan otomatis dipakai untuk penandatanganan surat.
                </p>
            </div>

            <form method="POST" action="{{ route('dashboard.website-settings.update-headman') }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="form-grid">
                    <div class="field">
                        <label for="head_name">Nama Kepala Desa</label>
                        <input id="head_name" name="head_name" type="text" value="{{ old('head_name', $head['name']) }}" required>
                    </div>
                    <div class="field">
                        <label for="head_position">Jabatan Kepala Desa</label>
                        <input id="head_position" name="head_position" type="text" value="{{ old('head_position', $head['position']) }}" required>
                    </div>
                    <div class="field">
                        <label for="head_photo_url">URL Foto Kepala Desa</label>
                        <input id="head_photo_url" name="head_photo_url" type="url" value="{{ old('head_photo_url', $head['photo_value']) }}">
                    </div>
                    <div class="field">
                        <label for="head_photo_file">Upload Foto Kepala Desa</label>
                        <input id="head_photo_file" name="head_photo_file" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                    </div>
                    <div class="field full">
                        <small class="muted">Jika URL dan upload diisi bersamaan, sistem akan memakai file upload.</small>
                    </div>
                </div>

                <div class="settings-preview-grid" style="margin-top:0.75rem;">
                    <article class="settings-preview-card settings-preview-head">
                        <small>Preview Kepala Desa</small>
                        @if($head['photo_preview'])
                            <img src="{{ $head['photo_preview'] }}" alt="{{ $head['name'] }}">
                        @endif
                        <h4>{{ $head['name'] }}</h4>
                        <p>{{ $head['position'] }}</p>
                    </article>
                </div>

                <div class="actions" style="margin-top:0.85rem;">
                    <button class="btn btn-primary" type="submit">Simpan Identitas Kepala Desa</button>
                </div>
            </form>
        </section>

        <section class="panel settings-card">
            <div class="settings-card-head">
                <h3>Media Hero dan Profil</h3>
                <p class="muted">Atur gambar hero beranda, hero profil, dan galeri foto profil desa.</p>
            </div>

            <form method="POST" action="{{ route('dashboard.website-settings.update-media') }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="form-grid">
                    <div class="field">
                        <label for="hero_image_url">URL Hero Beranda</label>
                        <input id="hero_image_url" name="hero_image_url" type="url" value="{{ old('hero_image_url', $media['hero_value']) }}">
                    </div>
                    <div class="field">
                        <label for="hero_image_file">Upload Hero Beranda</label>
                        <input id="hero_image_file" name="hero_image_file" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                    </div>
                    <div class="field">
                        <label for="profile_hero_image_url">URL Hero Profil</label>
                        <input id="profile_hero_image_url" name="profile_hero_image_url" type="url" value="{{ old('profile_hero_image_url', $media['profile_hero_value']) }}">
                    </div>
                    <div class="field">
                        <label for="profile_hero_image_file">Upload Hero Profil</label>
                        <input id="profile_hero_image_file" name="profile_hero_image_file" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                    </div>
                    <div class="field full">
                        <small class="muted">Jika URL dan upload diisi bersamaan, sistem akan memakai file upload.</small>
                    </div>
                    <div class="field full">
                        <label for="profile_gallery_images">Galeri Profil Desa (1 URL per baris)</label>
                        <textarea id="profile_gallery_images" name="profile_gallery_images" rows="6">{{ old('profile_gallery_images', $media['gallery_value']) }}</textarea>
                    </div>
                </div>

                <div class="actions" style="margin-top:0.85rem;">
                    <button class="btn btn-primary" type="submit">Simpan Media</button>
                </div>
            </form>

            <div class="settings-preview-grid" style="margin-top:0.9rem;">
                <article class="settings-preview-card">
                    <small>Preview Hero Beranda</small>
                    @if($media['hero_preview'])
                        <img src="{{ $media['hero_preview'] }}" alt="Preview Hero Beranda">
                    @else
                        <p class="muted">Belum ada gambar hero beranda.</p>
                    @endif
                </article>
                <article class="settings-preview-card">
                    <small>Preview Hero Profil</small>
                    @if($media['profile_hero_preview'])
                        <img src="{{ $media['profile_hero_preview'] }}" alt="Preview Hero Profil">
                    @else
                        <p class="muted">Belum ada gambar hero profil.</p>
                    @endif
                </article>
            </div>

            @if(!empty($media['gallery_preview']))
                <div class="settings-gallery-grid">
                    @foreach($media['gallery_preview'] as $galleryImage)
                        <img src="{{ $galleryImage }}" alt="Preview Galeri Profil">
                    @endforeach
                </div>
            @endif
        </section>

        <section class="panel settings-card">
            <div class="settings-card-head">
                <h3>Data Perangkat Desa</h3>
                <p class="muted">
                    Nama dan foto perangkat yang disimpan di sini akan langsung tampil di bagian
                    <strong>Data Perangkat Desa</strong> pada halaman profil publik.
                </p>
            </div>

            <form method="POST" action="{{ route('dashboard.website-settings.staff.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-grid">
                    <div class="field">
                        <label for="staff_name">Nama</label>
                        <input id="staff_name" name="name" type="text" value="{{ old('name') }}" required>
                    </div>
                    <div class="field">
                        <label for="staff_position">Jabatan</label>
                        <input id="staff_position" name="position" type="text" value="{{ old('position') }}" required>
                    </div>
                    <div class="field">
                        <label for="staff_photo_url">URL Foto (opsional)</label>
                        <input id="staff_photo_url" name="photo_url" type="url" value="{{ old('photo_url') }}">
                    </div>
                    <div class="field">
                        <label for="staff_photo_file">Upload Foto (opsional)</label>
                        <input id="staff_photo_file" name="photo_file" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                    </div>
                    <div class="field field-inline-check">
                        <label>
                            <input type="checkbox" name="is_active" value="1" checked>
                            Tampilkan di halaman profil
                        </label>
                    </div>
                    <div class="field full">
                        <small class="muted">Jika URL dan upload diisi bersamaan, sistem akan memakai file upload.</small>
                    </div>
                </div>

                <div class="actions" style="margin-top:0.85rem;">
                    <button class="btn btn-primary" type="submit">Tambah Perangkat Desa</button>
                </div>
            </form>

            <div class="settings-staff-table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th style="width:110px;">Foto</th>
                            <th>Nama & Jabatan</th>
                            <th style="width:120px;">Status</th>
                            <th style="width:300px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($staffMembers as $staff)
                            <tr>
                                <td>
                                    @if($staff->photo_url)
                                        <img src="{{ $staff->photo_url }}" alt="{{ $staff->name }}" class="settings-staff-thumb">
                                    @else
                                        <span class="muted">Tanpa foto</span>
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ $staff->name }}</strong><br>
                                    <span class="muted">{{ $staff->position }}</span>
                                </td>
                                <td><span class="status-badge">{{ $staff->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                                <td>
                                    <details class="settings-edit-detail">
                                        <summary>Edit Perangkat</summary>
                                        <form method="POST" action="{{ route('dashboard.website-settings.staff.update', $staff) }}" enctype="multipart/form-data">
                                            @csrf
                                            @method('PUT')
                                            <div class="field" style="margin-bottom:0.4rem;">
                                                <label for="name_{{ $staff->id }}">Nama</label>
                                                <input id="name_{{ $staff->id }}" name="name" type="text" value="{{ $staff->name }}" required>
                                            </div>
                                            <div class="field" style="margin-bottom:0.4rem;">
                                                <label for="position_{{ $staff->id }}">Jabatan</label>
                                                <input id="position_{{ $staff->id }}" name="position" type="text" value="{{ $staff->position }}" required>
                                            </div>
                                            <div class="field field-inline-check" style="margin-bottom:0.4rem;">
                                                <label>
                                                    <input type="checkbox" name="is_active" value="1" @checked($staff->is_active)>
                                                    Tampilkan di halaman profil
                                                </label>
                                            </div>
                                            <div class="field" style="margin-bottom:0.4rem;">
                                                <label for="photo_url_{{ $staff->id }}">URL Foto (opsional)</label>
                                                <input
                                                    id="photo_url_{{ $staff->id }}"
                                                    name="photo_url"
                                                    type="url"
                                                    value="{{ \Illuminate\Support\Str::startsWith((string) $staff->photo_path, ['http://', 'https://']) ? $staff->photo_path : '' }}">
                                            </div>
                                            <div class="field" style="margin-bottom:0.55rem;">
                                                <label for="photo_file_{{ $staff->id }}">Upload Foto Baru (opsional)</label>
                                                <input id="photo_file_{{ $staff->id }}" name="photo_file" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                                            </div>
                                            <div class="field" style="margin-bottom:0.55rem;">
                                                <small class="muted">Jika URL dan upload diisi bersamaan, sistem akan memakai file upload.</small>
                                            </div>
                                            <button class="btn btn-primary" type="submit">Simpan Perubahan</button>
                                        </form>
                                    </details>

                                    <form method="POST" action="{{ route('dashboard.website-settings.staff.destroy', $staff) }}" onsubmit="return confirm('Hapus data perangkat desa ini?')" style="margin-top:0.45rem;">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-danger" type="submit">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">Belum ada data perangkat desa.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
