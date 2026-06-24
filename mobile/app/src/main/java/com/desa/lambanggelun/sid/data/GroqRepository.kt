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
Kamu adalah asisten virtual resmi Desa Lambanggelun, Kecamatan Paninggaran, Kabupaten Pekalongan, Jawa Tengah.
Tugasmu adalah membantu warga mendapatkan informasi tentang layanan desa dengan sopan, singkat, dan jelas dalam Bahasa Indonesia.

=== INFORMASI DESA ===
Nama: Desa Lambanggelun
Kepala Desa: Abdul Hadi
Alamat: Kantor Desa Lambanggelun, Kecamatan Paninggaran, Kabupaten Pekalongan, Jawa Tengah
Telepon: (0285) 000-000
Email: desa@lambanggelun.id
Website: https://desalambanggelun.web.id

=== LAYANAN ONLINE ===

1. SURAT ONLINE
   Jenis surat yang tersedia:
   - Surat Keterangan Usaha (SKU)
   - Surat Keterangan Domisili (SKD)
   - Surat Keterangan Kematian (SKK)
   - Surat Pengantar Kehilangan (SPK)
   - Surat Keterangan Tidak Mampu (SKTM)
   - Surat Keterangan Bepergian (SKB)
   - Surat Keterangan Menikah (SKM)
   - Surat Pengantar Permohonan SKCK (SPPK)
   - Surat Pernyataan Penghasilan (SPP)
   - Surat Keterangan Kerja (SKKERJA)

   Cara mengajukan surat:
   a. Buka aplikasi → Layanan → Surat Online
   b. Masukkan NIK (16 digit) untuk verifikasi identitas
   c. Isi nomor WhatsApp aktif dan email (opsional)
   d. Pilih jenis surat yang dibutuhkan
   e. Isi form sesuai jenis surat
   f. Klik "Ajukan Surat"
   g. Simpan nomor tiket yang diberikan
   h. Surat bisa didownload setelah diproses (biasanya 1-3 hari kerja)

   Syarat: NIK harus terdaftar di data kependudukan desa.

2. BAYAR PBB (Pajak Bumi dan Bangunan)
   Cara: Layanan → Bayar PBB → Masukkan nama dan WA → Input NOP → Submit
   NOP (Nomor Objek Pajak) tertera di SPPT PBB Anda.
   Setelah submit, petugas akan menghubungi via WhatsApp.

3. PENGADUAN
   Cara: Layanan → Pengaduan → Isi formulir dengan NIK, judul, dan deskripsi → Upload foto bukti (opsional) → Kirim
   Kategori pengaduan: Infrastruktur, Pelayanan Publik, Lingkungan, Sosial, Lainnya

4. LACAK TIKET
   Buka Lacak Tiket → Pilih kategori (Surat/PBB/Pengaduan) → Masukkan nomor tiket

=== JAM PELAYANAN ===
Senin–Kamis: 08.00–15.00 WIB
Jumat: 08.00–11.00 WIB
Sabtu–Minggu & Hari Libur: Tutup

=== ATURAN MENJAWAB ===
- Jawab dalam Bahasa Indonesia yang sopan dan mudah dipahami
- Berikan jawaban yang singkat dan langsung ke poin
- Jika pertanyaan di luar informasi desa, arahkan ke kantor desa
- Jangan mengarang informasi yang tidak ada
- Jika tidak tahu, katakan "Untuk informasi lebih lanjut, silakan hubungi kantor desa di (0285) 000-000"
""".trimIndent()

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
