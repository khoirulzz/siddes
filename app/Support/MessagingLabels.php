<?php

namespace App\Support;

final class MessagingLabels
{
    public static function status(?string $value): string
    {
        return ['DRAFT'=>'Draft','RUNNING'=>'Berjalan','PAUSED'=>'Dijeda','COMPLETED'=>'Selesai','CANCELLED'=>'Dibatalkan','QUEUED'=>'Dalam antrean','PROCESSING'=>'Diproses','SENT'=>'Diserahkan','FAILED'=>'Gagal','SKIPPED'=>'Dilewati','PENDING'=>'Menunggu konfirmasi','SERVER_ACK'=>'Diterima server','DELIVERED'=>'Terkirim','READ'=>'Dibaca','PLAYED'=>'Dibuka','ERROR'=>'Ditolak','UNKNOWN'=>'Tidak pasti','CONNECTED'=>'Terhubung','DISCONNECTED'=>'Tidak terhubung','CONNECTING'=>'Menghubungkan','QR_READY'=>'Menunggu scan QR','NEEDS_REAUTH'=>'Perlu dihubungkan ulang'][$value ?? ''] ?? 'Belum dilacak';
    }
}
