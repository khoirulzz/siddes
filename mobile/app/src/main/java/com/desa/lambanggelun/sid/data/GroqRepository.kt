package com.desa.lambanggelun.sid.data

import com.desa.lambanggelun.sid.data.api.GroqApiClient
import com.desa.lambanggelun.sid.data.api.GroqMessage
import com.desa.lambanggelun.sid.data.api.GroqRequest
import com.desa.lambanggelun.sid.data.api.GroqTool
import com.desa.lambanggelun.sid.data.api.GroqFunction
import com.desa.lambanggelun.sid.data.api.GroqFunctionParameters
import com.desa.lambanggelun.sid.data.api.GroqFunctionProperty
import com.desa.lambanggelun.sid.ui.ai.PengaduanDraftData
import com.squareup.moshi.Moshi
import com.squareup.moshi.kotlin.reflect.KotlinJsonAdapterFactory

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
"Untuk informasi lebih lanjut, silakan hubungi atau datang ke kantor desa, atau kunjungi website desa."

ATURAN PENGADUAN / LAPORAN:
PENTING: Jika pengguna menceritakan keluhan, kerusakan (seperti jalan rusak, lampu mati), atau meminta membuat laporan/pengaduan, JANGAN PERNAH menolak atau berkata kamu tidak bisa mengakses sistem. Kamu memiliki akses ke tool `buat_draft_laporan_pengaduan`. Kamu WAJIB memanggil tool tersebut secara langsung untuk membuatkan draft laporan bagi pengguna. Jangan suruh pengguna membuka menu secara manual jika mereka sudah meminta bantuanmu!
"""".trimIndent()

    /**
     * Ask a question to Groq AI.
     * Returns cached answer if available, otherwise calls API with model rotation.
     *
     * @param question User's question
     * @param conversationHistory Previous messages for context (last 6 messages max)
     */
    data class AiResponse(
        val text: String?,
        val draftData: PengaduanDraftData? = null
    )

    private val moshi = Moshi.Builder().add(KotlinJsonAdapterFactory()).build()

    private fun getTools(): List<GroqTool> {
        return listOf(
            GroqTool(
                type = "function",
                function = GroqFunction(
                    name = "buat_draft_laporan_pengaduan",
                    description = "Gunakan fungsi ini jika pengguna secara jelas meminta untuk membuat, melaporkan, atau mengajukan aduan/laporan (misalnya jalan rusak, lampu mati).",
                    parameters = GroqFunctionParameters(
                        type = "object",
                        properties = mapOf(
                            "subject" to GroqFunctionProperty(
                                type = "string",
                                description = "Judul singkat laporan pengaduan"
                            ),
                            "category" to GroqFunctionProperty(
                                type = "string",
                                description = "Kategori pengaduan",
                                enum = listOf("Infrastruktur", "Pelayanan Publik", "Keamanan", "Sosial", "Lainnya")
                            ),
                            "location" to GroqFunctionProperty(
                                type = "string",
                                description = "Lokasi kejadian jika disebutkan, atau biarkan kosong"
                            ),
                            "description" to GroqFunctionProperty(
                                type = "string",
                                description = "Deskripsi lengkap terkait pengaduan berdasarkan informasi pengguna"
                            )
                        ),
                        required = listOf("subject", "category", "description")
                    )
                )
            )
        )
    }

    suspend fun ask(
        question: String,
        conversationHistory: List<GroqMessage> = emptyList()
    ): Result<AiResponse> {
        val key = cacheKey(question)

        // Return from cache if available (only for standalone questions without history)
        if (conversationHistory.isEmpty() && cache.containsKey(key)) {
            return Result.success(AiResponse(text = cache[key]!!))
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
    ): Result<AiResponse> {
        // Try primary model first
        runCatching {
            callGroq(GroqApiClient.MODEL_PRIMARY, messages)
        }.onSuccess { response ->
            if (shouldCache && response.draftData == null && response.text != null) cache[cacheKey] = response.text
            return Result.success(response)
        }.onFailure { primaryError ->
            // Fallback to secondary model
            runCatching {
                callGroq(GroqApiClient.MODEL_FALLBACK, messages)
            }.onSuccess { response ->
                if (shouldCache && response.draftData == null && response.text != null) cache[cacheKey] = response.text
                return Result.success(response)
            }.onFailure { fallbackError ->
                return Result.failure(fallbackError)
            }
        }
        return Result.failure(Exception("Unexpected error"))
    }

    private suspend fun callGroq(model: String, messages: List<GroqMessage>): AiResponse {
        val request = GroqRequest(
            model = model,
            messages = messages,
            tools = getTools(),
            tool_choice = "auto",
            maxTokens = 1024,
            temperature = 0.7
        )

        val response = GroqApiClient.service.chatCompletion(
            authorization = "Bearer ${GroqApiClient.API_KEY}",
            request = request
        )

        val choice = response.choices.firstOrNull()?.message
            ?: throw Exception("Empty response from AI")

        if (!choice.tool_calls.isNullOrEmpty()) {
            val toolCall = choice.tool_calls.first()
            if (toolCall.function.name == "buat_draft_laporan_pengaduan") {
                val adapter = moshi.adapter(PengaduanDraftData::class.java)
                val draft = adapter.fromJson(toolCall.function.arguments)
                return AiResponse(
                    text = "Baik, saya telah menyiapkan draf laporan untuk Anda. Silakan ketuk tombol di bawah ini untuk melengkapi dan mengirim laporan Anda.",
                    draftData = draft
                )
            }
        }

        return AiResponse(text = choice.content ?: "")
    }

    fun clearCache() {
        cache.clear()
    }

    fun getCacheSize(): Int = cache.size
}
