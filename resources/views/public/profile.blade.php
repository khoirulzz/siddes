@extends('layouts.public')

@section('title', 'Profil Desa - ' . config('village.name'))

@section('content')
    <section class="profile-hero-modern" style="--profile-hero-image: url('{{ config('village.profile_hero_image_url') }}');">
        <div class="profile-hero-layer">
            <p class="profile-kicker">Profil Resmi Desa</p>
            <h1>{{ config('village.name') }}</h1>
            <p>
                Informasi kelembagaan, visi Misi, struktur organisasi,
                Data perangkat, Batas wilayah, dan Peta lokasi desa.
            </p>
        </div>
    </section>

    <nav class="profile-subnav">
        <a href="#profil-singkat">Profil Singkat</a>
        <a href="#visi-misi">Visi Misi</a>
        <a href="#struktur-organisasi">Struktur Organisasi</a>
        <a href="#perangkat-desa">Perangkat Desa</a>
        <a href="#batas-wilayah">Batas Wilayah</a>
        <a href="#peta-desa">Peta</a>
    </nav>

    <section id="profil-singkat" class="profile-section reveal-on-scroll">
        <h2>Profil Singkat Desa</h2>
        <p>
            {{ config('village.name') }} merupakan salah satu desa di Kecamatan Paninggaran, Kabupaten Pekalongan, Provinsi Jawa Tengah. Desa ini berada di wilayah dataran tinggi dengan ketinggian sekitar 500–600 mdpl dan berjarak kurang lebih 9 km dari pusat kecamatan. Lambanggelun memiliki luas sekitar 13,65 km² dengan jumlah penduduk sekitar 3.700 jiwa. <br> Secara historis, desa ini terbentuk dari penggabungan dua wilayah, yaitu Panumbangan dan Mandelun pada sekitar tahun 1930-an. Saat ini, Desa Lambanggelun terdiri dari lima dusun, yaitu Bojongireng, Panumbangan, Mandelun, Sasak, dan Simendem. Kehidupan masyarakatnya masih kental dengan budaya lokal, serta didukung potensi alam dan kegiatan pedesaan yang berkembang secara bertahap.
        </p>

        <div class="photo-cascade" data-cascade>
            <div class="photo-cascade-track">
                @foreach($villagePhotos as $photo)
                    <article class="photo-cascade-item">
                        <img src="{{ $photo }}" alt="Foto kegiatan {{ config('village.name') }}">
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section id="visi-misi" class="profile-section reveal-on-scroll">
        <h2>Visi dan Misi Desa</h2>
        <div class="profile-two-col">
            <article>
                <h3>Visi</h3>
                <p>
                    Mewujudkan Desa Lambanggelun yang maju, inklusif, transparan, dan berdaya saing
                    melalui pelayanan publik yang berkualitas.
                </p>
            </article>
            <article>
                <h3>Misi</h3>
                <ol>
                    <li>Meningkatkan mutu pelayanan administrasi berbasis digital.</li>
                    <li>Mendorong partisipasi aktif masyarakat dalam pembangunan desa.</li>
                    <li>Mengoptimalkan potensi lokal untuk kemandirian ekonomi warga.</li>
                    <li>Mewujudkan tata kelola pemerintahan yang akuntabel dan responsif.</li>
                </ol>
            </article>
        </div>
    </section>

    <section id="struktur-organisasi" class="profile-section reveal-on-scroll">
        <h2>Bagan Struktur Organisasi Pemerintah Desa</h2>
        <div class="org-chart-frame">
            <figure class="org-chart-image-wrap">
                <img
                    class="org-chart-image"
                    src="{{ config('village.organization_chart_url') }}"
                    alt="Bagan struktur organisasi Pemerintah Desa {{ config('village.name') }}"
                    loading="lazy"
                >
            </figure>
        </div>
    </section>

    <section id="perangkat-desa" class="profile-section reveal-on-scroll">
        <h2>Data Perangkat Desa</h2>
        <article class="village-head-card">
            <img src="{{ $villageHead['photo'] }}" alt="{{ $villageHead['name'] }}">
            <div>
                <small>Pimpinan Desa</small>
                <h3>{{ $villageHead['name'] }}</h3>
                <p>{{ $villageHead['position'] }}</p>
            </div>
        </article>
        <div class="staff-grid">
            @foreach($staffMembers as $member)
                <article class="staff-card interactive-card">
                    <img src="{{ $member['photo'] }}" alt="{{ $member['name'] }}">
                    <h3>{{ $member['name'] }}</h3>
                    <p>{{ $member['position'] }}</p>
                </article>
            @endforeach
        </div>
    </section>

    <section id="batas-wilayah" class="profile-section reveal-on-scroll">
        <h2>Visualisasi Batas Wilayah Desa</h2>
        <div class="profile-boundary">
            @if(config('village.batas_desa'))
                <figure class="boundary-image-wrap">
                    <img src="{{ config('village.batas_desa') }}" alt="Batas wilayah {{ config('village.name') }}" loading="lazy">
                </figure>
            @else
                <p class="muted">Gambar batas desa belum tersedia.</p>
            @endif
        </div>
    </section>

    <section id="peta-desa" class="profile-section reveal-on-scroll">
        <h2>Peta Lokasi Desa</h2>
        <div class="profile-map">
            <iframe
                src="{{ config('village.map_embed_url') }}"
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                allowfullscreen
                title="Peta {{ config('village.name') }}">
            </iframe>
        </div>
    </section>

    <script>
        const revealTargets = document.querySelectorAll('.reveal-on-scroll');
        const revealObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('revealed');
                    revealObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.15 });

        revealTargets.forEach((target) => revealObserver.observe(target));

        const cascade = document.querySelector('[data-cascade]');
        if (cascade) {
            const track = cascade.querySelector('.photo-cascade-track');
            const items = cascade.querySelectorAll('.photo-cascade-item');
            let active = 0;
            if (items.length > 1) {
                setInterval(() => {
                    active = (active + 1) % items.length;
                    track.style.transform = `translateX(-${active * 22}%)`;
                }, 3500);
            }
        }
    </script>
@endsection
