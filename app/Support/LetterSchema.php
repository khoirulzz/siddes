<?php

namespace App\Support;

class LetterSchema
{
    public const TYPE_SKU = 'Surat Keterangan Usaha';
    public const TYPE_SKD = 'Surat Keterangan Domisili';
    public const TYPE_SKK = 'Surat Keterangan Kematian';
    public const TYPE_SPK = 'Surat Pengantar Kehilangan';
    public const TYPE_SKTM = 'Surat Keterangan Tidak Mampu';
    public const TYPE_SKB = 'Surat Keterangan Bepergian';
    public const TYPE_SKM = 'Surat Keterangan Menikah';
    public const TYPE_SPPK = 'Surat Pengantar Permohonan SKCK';
    public const TYPE_SPP = 'Surat Pernyataan Penghasilan';
    public const TYPE_SKKER = 'Surat Keterangan Kerja';

    /**
     * @return array<int, string>
     */
    public static function types(): array
    {
        return array_keys(self::definitions());
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function definitions(): array
    {
        return [
            self::TYPE_SKU => [
                'code' => 'SKU',
                'template' => 'sku-template.docx',
                'number_placeholder' => 'sku_nomor',
                'fields' => [
                    [
                        'name' => 'nama_usaha',
                        'label' => 'Nama Usaha',
                        'placeholder' => 'Contoh: Toko Maju Jaya',
                        'required' => true,
                        'max' => 255,
                    ],
                    [
                        'name' => 'keperluan',
                        'label' => 'Keperluan Surat',
                        'placeholder' => 'Contoh: Pengajuan KUR',
                        'required' => true,
                        'max' => 255,
                    ],
                ],
            ],
            self::TYPE_SKD => [
                'code' => 'SKD',
                'template' => 'skd-template.docx',
                'number_placeholder' => 'skd_nomor',
                'fields' => [
                    [
                        'name' => 'keperluan',
                        'label' => 'Keperluan Surat',
                        'placeholder' => 'Contoh: Administrasi kerja',
                        'required' => true,
                        'max' => 255,
                    ],
                    [
                        'name' => 'dusun_asal',
                        'label' => 'Dusun Asal (Opsional)',
                        'placeholder' => 'Contoh: Panumbangan',
                        'required' => false,
                        'max' => 120,
                    ],
                    [
                        'name' => 'rt_asal',
                        'label' => 'RT Asal (Opsional)',
                        'placeholder' => 'Contoh: 001',
                        'required' => false,
                        'max' => 10,
                    ],
                    [
                        'name' => 'rw_asal',
                        'label' => 'RW Asal (Opsional)',
                        'placeholder' => 'Contoh: 003',
                        'required' => false,
                        'max' => 10,
                    ],
                    [
                        'name' => 'desa_asal',
                        'label' => 'Desa Asal (Opsional)',
                        'placeholder' => 'Contoh: Desa X',
                        'required' => false,
                        'max' => 120,
                    ],
                    [
                        'name' => 'kecamatan_asal',
                        'label' => 'Kecamatan Asal (Opsional)',
                        'placeholder' => 'Contoh: Paninggaran',
                        'required' => false,
                        'max' => 120,
                    ],
                    [
                        'name' => 'kabupaten_asal',
                        'label' => 'Kabupaten Asal (Opsional)',
                        'placeholder' => 'Contoh: Pekalongan',
                        'required' => false,
                        'max' => 120,
                    ],
                ],
            ],
            self::TYPE_SKK => [
                'code' => 'SKK',
                'template' => 'skk-template.docx',
                'number_placeholder' => 'skk_nomor',
                'fields' => [
                    [
                        'name' => 'nama_almarhum',
                        'label' => 'Nama Almarhum/Almarhumah',
                        'placeholder' => 'Nama lengkap almarhum',
                        'required' => true,
                        'max' => 255,
                    ],
                    [
                        'name' => 'jenis_kelamin_almarhum',
                        'label' => 'Jenis Kelamin Almarhum',
                        'placeholder' => 'Pilih jenis kelamin',
                        'required' => true,
                        'max' => 30,
                        'type' => 'select',
                        'options' => ['Laki-laki', 'Perempuan'],
                    ],
                    [
                        'name' => 'tgl_lahir_almarhum',
                        'label' => 'Tanggal Lahir Almarhum',
                        'placeholder' => '',
                        'required' => true,
                        'type' => 'date',
                    ],
                    [
                        'name' => 'alamat_almarhum',
                        'label' => 'Alamat Almarhum',
                        'placeholder' => 'Alamat terakhir almarhum',
                        'required' => true,
                        'max' => 255,
                    ],
                    [
                        'name' => 'tgl_meninggal',
                        'label' => 'Tanggal Meninggal',
                        'placeholder' => '',
                        'required' => true,
                        'type' => 'date',
                    ],
                    [
                        'name' => 'hari_meninggal',
                        'label' => 'Hari Meninggal (Opsional)',
                        'placeholder' => 'Contoh: Senin',
                        'required' => false,
                        'max' => 30,
                    ],
                    [
                        'name' => 'waktu_meninggal',
                        'label' => 'Waktu Meninggal',
                        'placeholder' => '',
                        'required' => true,
                        'max' => 30,
                        'type' => 'time',
                    ],
                    [
                        'name' => 'tempat_meninggal',
                        'label' => 'Tempat Meninggal',
                        'placeholder' => 'Contoh: Rumah / RS',
                        'required' => true,
                        'max' => 255,
                    ],
                    [
                        'name' => 'penyebab_meninggal',
                        'label' => 'Penyebab Meninggal',
                        'placeholder' => 'Contoh: Sakit',
                        'required' => true,
                        'max' => 255,
                    ],
                    [
                        'name' => 'hubungan_pemohon',
                        'label' => 'Hubungan Pelapor',
                        'placeholder' => 'Contoh: Anak kandung',
                        'required' => true,
                        'max' => 100,
                    ],
                    [
                        'name' => 'usia_almarhum',
                        'label' => 'Usia Almarhum (Opsional)',
                        'placeholder' => 'Diisi otomatis dari tanggal lahir/meninggal jika memungkinkan',
                        'required' => false,
                        'max' => 3,
                    ],
                    [
                        'name' => 'usia_pemohon',
                        'label' => 'Usia Pemohon (Opsional)',
                        'placeholder' => 'Diisi otomatis dari data kependudukan jika tersedia',
                        'required' => false,
                        'max' => 3,
                    ],
                ],
            ],
            self::TYPE_SPK => [
                'code' => 'SPK',
                'template' => 'spk-template.docx',
                'number_placeholder' => 'spk_nomor',
                'fields' => [
                    [
                        'name' => 'kehilangan_benda',
                        'label' => 'Barang yang Hilang',
                        'placeholder' => 'Contoh: KTP / SIM / STNK',
                        'required' => true,
                        'max' => 255,
                    ],
                    [
                        'name' => 'lokasi_kehilangan',
                        'label' => 'Lokasi Kehilangan',
                        'placeholder' => 'Contoh: Pasar Paninggaran',
                        'required' => true,
                        'max' => 255,
                    ],
                    [
                        'name' => 'lama_hilang',
                        'label' => 'Lama Kehilangan',
                        'placeholder' => 'Contoh: 2 hari',
                        'required' => true,
                        'max' => 120,
                    ],
                ],
            ],
            self::TYPE_SKTM => [
                'code' => 'SKTM',
                'template' => 'sktm-template.docx',
                'number_placeholder' => 'sktm_nomor',
                'fields' => [
                    [
                        'name' => 'keperluan',
                        'label' => 'Keperluan Surat',
                        'placeholder' => 'Contoh: Pengajuan beasiswa',
                        'required' => true,
                        'max' => 255,
                    ],
                ],
            ],
            self::TYPE_SKB => [
                'code' => 'SKB',
                'template' => 'skb-template.docx',
                'number_placeholder' => 'skb_nomor',
                'fields' => [
                    [
                        'name' => 'desa_tujuan',
                        'label' => 'Desa/Kelurahan Tujuan',
                        'placeholder' => 'Contoh: Desa Bojong',
                        'required' => true,
                        'max' => 120,
                    ],
                    [
                        'name' => 'kec_tujuan',
                        'label' => 'Kecamatan Tujuan',
                        'placeholder' => 'Contoh: Paninggaran',
                        'required' => true,
                        'max' => 120,
                    ],
                    [
                        'name' => 'kota_tujuan',
                        'label' => 'Kabupaten/Kota Tujuan',
                        'placeholder' => 'Contoh: Pekalongan',
                        'required' => true,
                        'max' => 120,
                    ],
                    [
                        'name' => 'prov_tujuan',
                        'label' => 'Provinsi Tujuan',
                        'placeholder' => 'Contoh: Jawa Tengah',
                        'required' => true,
                        'max' => 120,
                    ],
                    [
                        'name' => 'keperluan',
                        'label' => 'Keperluan Bepergian',
                        'placeholder' => 'Contoh: Urusan keluarga',
                        'required' => true,
                        'max' => 255,
                    ],
                ],
            ],
            self::TYPE_SKM => [
                'code' => 'SKM',
                'template' => 'skm-template.docx',
                'number_placeholder' => 'skm_nomor',
                'fields' => [
                    [
                        'name' => 'nama_suami',
                        'label' => 'Nama Calon Suami',
                        'placeholder' => 'Nama lengkap calon suami',
                        'required' => true,
                        'max' => 255,
                    ],
                    [
                        'name' => 'ayah_suami',
                        'label' => 'Nama Ayah Calon Suami',
                        'placeholder' => 'Nama ayah kandung calon suami',
                        'required' => true,
                        'max' => 255,
                    ],
                    [
                        'name' => 'tempat_lahir_suami',
                        'label' => 'Tempat Lahir Calon Suami',
                        'placeholder' => 'Contoh: Pekalongan',
                        'required' => true,
                        'max' => 120,
                    ],
                    [
                        'name' => 'tanggal_lahir_suami',
                        'label' => 'Tanggal Lahir Calon Suami',
                        'placeholder' => '',
                        'required' => true,
                        'type' => 'date',
                    ],
                    [
                        'name' => 'nik_suami',
                        'label' => 'NIK Calon Suami',
                        'placeholder' => '16 digit NIK calon suami',
                        'required' => true,
                        'max' => 20,
                    ],
                    [
                        'name' => 'pekerjaan_suami',
                        'label' => 'Pekerjaan Calon Suami',
                        'placeholder' => 'Contoh: Karyawan',
                        'required' => true,
                        'max' => 120,
                    ],
                    [
                        'name' => 'alamat_suami',
                        'label' => 'Alamat Calon Suami',
                        'placeholder' => 'Alamat lengkap calon suami',
                        'required' => true,
                        'max' => 255,
                    ],
                    [
                        'name' => 'nama_istri',
                        'label' => 'Nama Calon Istri',
                        'placeholder' => 'Nama lengkap calon istri',
                        'required' => true,
                        'max' => 255,
                    ],
                    [
                        'name' => 'ayah_istri',
                        'label' => 'Nama Ayah Calon Istri',
                        'placeholder' => 'Nama ayah kandung calon istri',
                        'required' => true,
                        'max' => 255,
                    ],
                    [
                        'name' => 'tempat_lahir_istri',
                        'label' => 'Tempat Lahir Calon Istri',
                        'placeholder' => 'Contoh: Pekalongan',
                        'required' => true,
                        'max' => 120,
                    ],
                    [
                        'name' => 'tanggal_lahir_istri',
                        'label' => 'Tanggal Lahir Calon Istri',
                        'placeholder' => '',
                        'required' => true,
                        'type' => 'date',
                    ],
                    [
                        'name' => 'nik_istri',
                        'label' => 'NIK Calon Istri',
                        'placeholder' => '16 digit NIK calon istri',
                        'required' => true,
                        'max' => 20,
                    ],
                    [
                        'name' => 'pekerjaan_istri',
                        'label' => 'Pekerjaan Calon Istri',
                        'placeholder' => 'Contoh: Ibu Rumah Tangga',
                        'required' => true,
                        'max' => 120,
                    ],
                    [
                        'name' => 'alamat_istri',
                        'label' => 'Alamat Calon Istri',
                        'placeholder' => 'Alamat lengkap calon istri',
                        'required' => true,
                        'max' => 255,
                    ],
                    [
                        'name' => 'tanggal_nikah',
                        'label' => 'Tanggal Pelaksanaan Pernikahan',
                        'placeholder' => '',
                        'required' => true,
                        'type' => 'date',
                    ],
                    [
                        'name' => 'mas_kawin',
                        'label' => 'Mas Kawin',
                        'placeholder' => 'Contoh: Uang Rp 500.000',
                        'required' => true,
                        'max' => 255,
                    ],
                    [
                        'name' => 'saksi_suami',
                        'label' => 'Saksi dari Pihak Suami',
                        'placeholder' => 'Nama saksi dari pihak suami',
                        'required' => true,
                        'max' => 255,
                    ],
                    [
                        'name' => 'hub_dg_suami',
                        'label' => 'Hubungan Saksi dengan Pihak Suami',
                        'placeholder' => 'Contoh: Paman',
                        'required' => true,
                        'max' => 120,
                    ],
                    [
                        'name' => 'saksi_istri',
                        'label' => 'Saksi dari Pihak Istri',
                        'placeholder' => 'Nama saksi dari pihak istri',
                        'required' => true,
                        'max' => 255,
                    ],
                    [
                        'name' => 'hub_dg_istri',
                        'label' => 'Hubungan Saksi dengan Pihak Istri',
                        'placeholder' => 'Contoh: Kakak',
                        'required' => true,
                        'max' => 120,
                    ],
                ],
            ],
            self::TYPE_SPPK => [
                'code' => 'SPPK',
                'template' => 'skck-template.docx',
                'number_placeholder' => 'sppk_nomor',
                'fields' => [
                    [
                        'name' => 'status_kawin',
                        'label' => 'Status Perkawinan',
                        'placeholder' => 'Pilih status',
                        'required' => true,
                        'type' => 'select',
                        'options' => ['Belum Kawin', 'Kawin', 'Cerai Hidup', 'Cerai Mati'],
                        'max' => 40,
                    ],
                    [
                        'name' => 'no_hp',
                        'label' => 'Nomor Handphone',
                        'placeholder' => 'Nomor HP aktif yang bisa dihubungi',
                        'required' => true,
                        'max' => 30,
                    ],
                    [
                        'name' => 'email',
                        'label' => 'Email Pemohon',
                        'placeholder' => 'Contoh: warga@email.com',
                        'required' => false,
                        'max' => 120,
                    ],
                    [
                        'name' => 'keperluan',
                        'label' => 'Keperluan Pengurusan SKCK',
                        'placeholder' => 'Contoh: Melamar kerja',
                        'required' => true,
                        'max' => 255,
                    ],
                ],
            ],
            self::TYPE_SPP => [
                'code' => 'SPP',
                'template' => 'spp-template.docx',
                'number_placeholder' => 'spp_nomor',
                'fields' => [
                    [
                        'name' => 'jumlah_penghasilan',
                        'label' => 'Jumlah Penghasilan per Bulan',
                        'placeholder' => 'Contoh: 2.500.000',
                        'required' => true,
                        'max' => 60,
                    ],
                    [
                        'name' => 'terbilang_penghasilan',
                        'label' => 'Terbilang Penghasilan',
                        'placeholder' => 'Contoh: dua juta lima ratus ribu rupiah',
                        'required' => true,
                        'max' => 255,
                    ],
                ],
            ],
            self::TYPE_SKKER => [
                'code' => 'SKKERJA',
                'template' => 'skkerja-template.docx',
                'number_placeholder' => 'skker_nomor',
                'fields' => [
                    [
                        'name' => 'tujuan',
                        'label' => 'Tujuan Pengantar Jalan',
                        'placeholder' => 'Contoh: PT Maju Terus',
                        'required' => true,
                        'max' => 255,
                    ],
                    [
                        'name' => 'nama_rt',
                        'label' => 'Nama Ketua RT',
                        'placeholder' => 'Nama ketua RT setempat',
                        'required' => true,
                        'max' => 120,
                    ],
                    [
                        'name' => 'nama_rw',
                        'label' => 'Nama Ketua RW',
                        'placeholder' => 'Nama ketua RW setempat',
                        'required' => true,
                        'max' => 120,
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function definition(?string $type): ?array
    {
        if (! $type) {
            return null;
        }

        return self::definitions()[$type] ?? null;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function fieldsMap(): array
    {
        $map = [];

        foreach (self::definitions() as $definition) {
            foreach ($definition['fields'] as $field) {
                $map[$field['name']] = $field;
            }
        }

        return $map;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function allDynamicFields(): array
    {
        return array_values(self::fieldsMap());
    }

    /**
     * @return array<int, string>
     */
    public static function requiredFieldsForType(?string $type): array
    {
        $definition = self::definition($type);
        if (! $definition) {
            return [];
        }

        return collect($definition['fields'])
            ->filter(fn (array $field) => (bool) ($field['required'] ?? false))
            ->pluck('name')
            ->values()
            ->all();
    }

    public static function codeForType(?string $type): string
    {
        return (string) (self::definition($type)['code'] ?? 'SRT');
    }

    public static function templateForType(?string $type): ?string
    {
        $template = self::definition($type)['template'] ?? null;
        if (! is_string($template) || trim($template) === '') {
            return null;
        }

        return $template;
    }

    public static function numberPlaceholderForType(?string $type): ?string
    {
        $placeholder = self::definition($type)['number_placeholder'] ?? null;
        if (! is_string($placeholder) || trim($placeholder) === '') {
            return null;
        }

        return $placeholder;
    }
}
