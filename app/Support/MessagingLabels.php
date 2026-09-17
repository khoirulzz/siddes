<?php

namespace App\Support;

final class MessagingLabels
{
    public static function connection(array $state): array
    {
        $presentation = match ($state['status'] ?? null) {
            'CONNECTED' => ['tone' => 'ready', 'icon' => 'check', 'title' => 'WhatsApp terhubung', 'description' => 'Akun siap digunakan untuk mengirim informasi desa.'],
            'CONNECTING' => ['tone' => 'pairing', 'icon' => 'clock', 'title' => 'Menghubungkan WhatsApp', 'description' => 'Sedang menyiapkan koneksi. Status akan diperbarui otomatis.'],
            'QR_READY' => ['tone' => 'pairing', 'icon' => 'qr', 'title' => 'Pindai kode QR', 'description' => 'Tautkan akun WhatsApp untuk mulai mengirim informasi.'],
            'NEEDS_REAUTH' => ['tone' => 'warning', 'icon' => 'alert', 'title' => 'Hubungkan kembali WhatsApp', 'description' => 'Akun perlu ditautkan ulang. Pengiriman menunggu koneksi kembali.'],
            'DISCONNECTED' => ['tone' => 'offline', 'icon' => 'minus', 'title' => 'WhatsApp belum terhubung', 'description' => 'Hubungkan akun WhatsApp untuk mengirim campaign.'],
            default => ['tone' => 'offline', 'icon' => 'minus', 'title' => 'Status belum tersedia', 'description' => 'Perbarui status untuk memeriksa koneksi WhatsApp.'],
        };
        if (($state['authPersistence'] ?? null) === 'degraded') {
            $presentation = ['tone' => 'warning', 'icon' => 'alert', 'title' => 'Koneksi perlu diperiksa', 'description' => 'Pengiriman dihentikan sementara karena ada gangguan koneksi. Coba perbarui status; hubungi pengelola jika gangguan berlanjut.'];
        }
        $presentation['ready'] = $presentation['tone'] === 'ready';
        $presentation['notice'] = match ($state['reason'] ?? null) {
            'AUTH_STATE_INVALID', 'SESSION_INVALID' => 'Tautan perangkat tidak lagi valid. Hubungkan kembali akun WhatsApp.',
            'SESSION_IN_USE', 'CONNECTION_REPLACED' => 'Akun sedang digunakan oleh koneksi lain. Hubungi pengelola layanan untuk memeriksanya.',
            'SESSION_LEASE_LOST', 'AUTH_PERSISTENCE_FAILED' => 'Koneksi dihentikan untuk menjaga keamanan akun. Hubungi pengelola jika masalah berlanjut.',
            'RESTORE_FAILED', 'CONNECTION_SETUP_FAILED', 'CONNECTION_CLOSED' => 'Koneksi terputus sementara. Layanan akan mencoba menghubungkan kembali.',
            null, '' => null,
            default => 'Koneksi sedang mengalami gangguan. Perbarui status atau hubungi pengelola layanan.',
        };
        return $presentation;
    }

    public static function status(?string $value): string
    {
        return ['DRAFT'=>'Draft','RUNNING'=>'Berjalan','PAUSED'=>'Dijeda','COMPLETED'=>'Selesai','CANCELLED'=>'Dibatalkan','QUEUED'=>'Dalam antrean','PROCESSING'=>'Diproses','SENT'=>'Diserahkan','FAILED'=>'Gagal','SKIPPED'=>'Dilewati','PENDING'=>'Menunggu konfirmasi','SERVER_ACK'=>'Diterima WhatsApp','DELIVERED'=>'Terkirim','READ'=>'Dibaca','PLAYED'=>'Dibuka','ERROR'=>'Ditolak','UNKNOWN'=>'Tidak pasti','CONNECTED'=>'Terhubung','DISCONNECTED'=>'Tidak terhubung','CONNECTING'=>'Menghubungkan','QR_READY'=>'Menunggu pemindaian QR','NEEDS_REAUTH'=>'Perlu dihubungkan ulang'][$value ?? ''] ?? 'Belum dilacak';
    }
}
