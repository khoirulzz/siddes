package com.desa.lambanggelun.sid.data

import com.desa.lambanggelun.sid.data.api.GroqApiClient
import com.desa.lambanggelun.sid.data.api.GroqMessage
import com.desa.lambanggelun.sid.data.api.GroqRequest
import com.desa.lambanggelun.sid.data.api.GroqTool
import com.desa.lambanggelun.sid.data.api.GroqFunction
import com.desa.lambanggelun.sid.data.api.GroqFunctionParameters
import com.desa.lambanggelun.sid.data.api.GroqFunctionProperty
import com.desa.lambanggelun.sid.ui.ai.PengaduanDraftData
import com.desa.lambanggelun.sid.ui.tracking.TrackResult
import com.desa.lambanggelun.sid.data.api.ApiClient
import com.squareup.moshi.Moshi
import com.squareup.moshi.kotlin.reflect.KotlinJsonAdapterFactory

data class CheckTicketArgs(
    val kategori: String,
    val nomor_tiket: String
)

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
PENTING: Jika pengguna menceritakan keluhan, kerusakan (seperti jalan rusak, lampu mati), atau meminta membuat laporan/pengaduan, JANGAN PERNAH menolak atau berkata kamu tidak bisa mengakses sistem. Kamu memiliki akses ke tool `buat_draft_laporan_pengaduan`. Kamu WAJIB memanggil tool tersebut secara langsung untuk membuatkan draft laporan bagi pengguna. Jika permintaannya belum jelas apakah ingin mengeksekusi laporan atau hanya meminta teks, tawarkan ke dia untuk membuat laporan otomatis ke sistem. Jangan suruh pengguna membuka menu secara manual jika mereka sudah meminta bantuanmu!

ATURAN LACAK TIKET:
PENTING: Jika pengguna menanyakan status tiket, melacak surat, atau memberikan format nomor tiket (misal SRT-2026-..., PBB-2026-..., ADU-2026-...), gunakan tool `cek_status_tiket`. Tentukan kategorinya berdasarkan prefix (SRT=Surat, PBB=PBB, ADU=Pengaduan) atau konteks pengguna.
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
        val draftData: PengaduanDraftData? = null,
        val trackResult: TrackResult? = null
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
            ),
            GroqTool(
                type = "function",
                function = GroqFunction(
                    name = "cek_status_tiket",
                    description = "Gunakan fungsi ini untuk mengecek status tiket pengajuan surat, pembayaran PBB, atau laporan pengaduan.",
                    parameters = GroqFunctionParameters(
                        type = "object",
                        properties = mapOf(
                            "kategori" to GroqFunctionProperty(
                                type = "string",
                                description = "Kategori tiket",
                                enum = listOf("Surat", "PBB", "Pengaduan")
                            ),
                            "nomor_tiket" to GroqFunctionProperty(
                                type = "string",
                                description = "Nomor tiket yang akan dilacak (contoh: SRT-2026-1234)"
                            )
                        ),
                        required = listOf("kategori", "nomor_tiket")
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
            } else if (toolCall.function.name == "cek_status_tiket") {
                val adapter = moshi.adapter(CheckTicketArgs::class.java)
                val args = adapter.fromJson(toolCall.function.arguments)
                if (args != null) {
                    try {
                        val result = when (args.kategori.lowercase()) {
                            "surat" -> {
                                val r = ApiClient.service.searchLetterByTicket(args.nomor_tiket)
                                if (r.success) TrackResult(
                                    ticketCode = r.ticket_number ?: args.nomor_tiket,
                                    status = r.status ?: "-",
                                    label1 = "Jenis Surat", value1 = r.letter_type ?: "-",
                                    label2 = "No. Surat", value2 = r.official_number ?: "-",
                                    downloadUrl = r.download_url
                                ) else null
                            }
                            "pbb" -> {
                                val r = ApiClient.service.searchPbbByTicket(args.nomor_tiket)
                                if (r.success) TrackResult(
                                    ticketCode = r.ticket_code ?: args.nomor_tiket,
                                    status = r.status ?: "-",
                                    label1 = "Pemohon", value1 = r.applicant_name ?: "-",
                                    label2 = "Total", value2 = r.total_amount?.let { "Rp ${it.toLong()}" } ?: "-"
                                ) else null
                            }
                            else -> {
                                val r = ApiClient.service.searchComplaintByTicket(args.nomor_tiket)
                                if (r.success) TrackResult(
                                    ticketCode = r.ticket_code ?: args.nomor_tiket,
                                    status = r.status ?: "-",
                                    label1 = "Judul", value1 = r.subject ?: "-",
                                    label2 = "Kategori", value2 = r.category ?: "-"
                                ) else null
                            }
                        }
                        if (result != null) {
                            return AiResponse(
                                text = "Berikut adalah hasil pencarian untuk tiket ${args.nomor_tiket}:",
                                trackResult = result
                            )
                        } else {
                            return AiResponse(text = "Maaf, tiket dengan nomor ${args.nomor_tiket} tidak ditemukan pada kategori ${args.kategori}.")
                        }
                    } catch (e: Exception) {
                        return AiResponse(text = "Maaf, terjadi kesalahan saat menghubungi server untuk melacak tiket Anda.")
                    }
                }
            }
        }

        return AiResponse(text = choice.content ?: "")
    }

    fun clearCache() {
        cache.clear()
    }

    fun getCacheSize(): Int = cache.size
}
