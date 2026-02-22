<?php

namespace App\Support;

class LetterSchema
{
    public const TYPE_SKU = 'Surat Keterangan Usaha';
    public const TYPE_SKD = 'Surat Keterangan Domisili';
    public const TYPE_SKK = 'Surat Keterangan Kematian';
    public const TYPE_SPK = 'Surat Pengantar Kehilangan';
    public const TYPE_SKTM = 'Surat Keterangan Tidak Mampu';

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
                        'placeholder' => 'Contoh: 2 hari yang lalu',
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
