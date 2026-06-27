package com.desa.lambanggelun.sid.data

import com.desa.lambanggelun.sid.data.api.GroqApiClient
import com.desa.lambanggelun.sid.data.api.GroqMessage
import com.desa.lambanggelun.sid.data.api.GroqRequest

/**
 * Repository for Groq AI interactions.
 * - Maintains a lightweight in-memory cache (question → answer).
 * - Includes a village service knowledge base as system prompt.
 * - Rotates models: primary (LLama 70b) → fallback (Qwen).
 */
object GroqRepository {

    // Simple in-memory cache: normalized question → answer
    private val cache = mutableMapOf<String, String>()

    // Normalize key: lowercase + trim + collapse whitespace
    private fun cacheKey(q: String) = q.lowercase().trim().replace(Regex("\\s+"), " ")

    // ─── Knowledge Base (System Prompt) ──────────────────────────────────────
    private val systemPrompt = """
kamu adalah Asisten Virtual Resmi Desa Lambanggelun, Kecamatan Paninggaran, Kabupaten Pekalongan, Jawa Tengah.
DATA DESA
Kepala Desa: Abdul Hadi
Alamat: Kantor Desa Lambanggelun, Kecamatan Paninggaran, Kabupaten Pekalongan, Jawa Tengah
Telepon: (0285) 000-000
Email: desa@lambanggelun.id
Website: https://desalambanggelun.web.id

LAYANAN
Surat Online
Jenis surat:
Surat Keterangan Usaha
Surat Keterangan Domisili
Surat Keterangan Kematian
Surat Pengantar Kehilangan
Surat Keterangan Tidak Mampu
Surat Keterangan Bepergian
Surat Keterangan Menikah
Surat Pengantar Permohonan SKCK
Surat Pernyataan Penghasilan
Surat Keterangan Kerja

Cara pengajuan:
Layanan → Surat Online → masukkan NIK (16 digit) → isi nomor WhatsApp aktif dan email (opsional) → pilih jenis surat → isi formulir → klik "Ajukan Surat" → simpan nomor tiket → surat dapat diunduh dalam format PDF/Word.

Catatan: Validasi tanda tangan dan stempel tetap dilakukan di balai desa.
Syarat: NIK harus terdaftar pada data kependudukan desa.

Bayar PBB
Layanan → Bayar PBB → isi nama dan nomor WhatsApp → masukkan NOP (Nomor Objek Pajak pada SPPT PBB) → Submit.
Petugas akan menghubungi melalui WhatsApp.
Pengaduan
Layanan → Pengaduan → isi NIK, judul, dan deskripsi → unggah foto bukti (opsional) → Kirim.

Kategori:
Infrastruktur
Pelayanan Publik
Lingkungan
Sosial
Lainnya

Lacak Tiket:
Lacak Tiket → pilih kategori (Surat, PBB, atau Pengaduan) → masukkan nomor tiket.

JAM PELAYANAN

Senin–Kamis: 08.00–15.00 WIB
Jumat: 08.00–11.00 WIB
Sabtu, Minggu, dan Hari Libur: Tutup

ATURAN MENJAWAB

Gunakan Bahasa Indonesia yang sopan, singkat, jelas, dan mudah dipahami.
Jawab langsung sesuai informasi yang tersedia.
Jangan mengarang atau menambah informasi.
Jika pertanyaan di luar informasi yang tersedia, arahkan ke kantor desa atau website desa.
Jika informasi tidak diketahui, jawab:
"Untuk informasi lebih lanjut, silakan hubungi atau datang ke kantor desa, atau kunjungi website desa.
"""".trimIndent()

    /**
     * Ask a question to Groq AI.
     * Returns cached answer if available, otherwise calls API with model rotation.
     *
     * @param question User's question
     * @param conversationHistory Previous messages for context (last 6 messages max)
     */
    suspend fun ask(
        question: String,
        conversationHistory: List<GroqMessage> = emptyList()
    ): Result<String> {
        val key = cacheKey(question)

        // Return from cache if available (only for standalone questions without history)
        if (conversationHistory.isEmpty() && cache.containsKey(key)) {
            return Result.success(cache[key]!!)
        }

        // Build message list
        val messages = mutableListOf<GroqMessage>().apply {
            add(GroqMessage(role = "system", content = systemPrompt))
            // Add last 6 messages of history for context (to keep token usage reasonable)
            addAll(conversationHistory.takeLast(6))
            add(GroqMessage(role = "user", content = question))
        }

        // Try primary model, fallback to secondary
        return tryWithFallback(messages, key, conversationHistory.isEmpty())
    }

    private suspend fun tryWithFallback(
        messages: List<GroqMessage>,
        cacheKey: String,
        shouldCache: Boolean
    ): Result<String> {
        // Try primary model first
        runCatching {
            callGroq(GroqApiClient.MODEL_PRIMARY, messages)
        }.onSuccess { answer ->
            if (shouldCache) cache[cacheKey] = answer
            return Result.success(answer)
        }.onFailure { primaryError ->
            // Fallback to secondary model
            runCatching {
                callGroq(GroqApiClient.MODEL_FALLBACK, messages)
            }.onSuccess { answer ->
                if (shouldCache) cache[cacheKey] = answer
                return Result.success(answer)
            }.onFailure { fallbackError ->
                return Result.failure(fallbackError)
            }
        }
        return Result.failure(Exception("Unexpected error"))
    }

    private suspend fun callGroq(model: String, messages: List<GroqMessage>): String {
        val response = GroqApiClient.service.chatCompletion(
            authorization = "Bearer ${GroqApiClient.API_KEY}",
            request = GroqRequest(
                model = model,
                messages = messages,
                maxTokens = 1024,
                temperature = 0.7
            )
        )
        return response.choices.firstOrNull()?.message?.content
            ?: throw Exception("Empty response from AI")
    }

    fun clearCache() {
        cache.clear()
    }

    fun getCacheSize(): Int = cache.size
}
