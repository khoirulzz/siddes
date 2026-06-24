package com.desa.lambanggelun.sid.data.api

import retrofit2.Retrofit
import retrofit2.converter.moshi.MoshiConverterFactory
import retrofit2.http.*
import com.squareup.moshi.Json
import com.squareup.moshi.Moshi
import com.squareup.moshi.kotlin.reflect.KotlinJsonAdapterFactory
import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import okhttp3.MultipartBody
import okhttp3.RequestBody
import java.util.concurrent.TimeUnit

// ─── Response Wrapper ────────────────────────────────────────────────────────

data class BaseResponse<T>(
    val success: Boolean,
    val message: String? = null,
    val data: T? = null,
    // Fields returned by check-nik
    val full_name: String? = null,
    val address_detail: String? = null,
    // Fields returned by letter submit
    val ticket_number: String? = null,
    val ticket_code: String? = null,
    val download_url: String? = null,
    val download_docx_url: String? = null
)

// ─── Domain Models ────────────────────────────────────────────────────────────

data class TaxObject(
    val nop: String,
    val tax_name: String,
    val address: String,
    val tax_year: Int,
    @Json(name = "amount_due") val amount_due: Double
)

data class ApiNewsItem(
    val id: Int,
    val title: String,
    val slug: String,
    val excerpt: String?,
    val thumbnail: String?,
    @Json(name = "clean_content") val clean_content: String?,
    @Json(name = "published_at") val published_at: String?,
    val category: String? = null
)

data class PaginatedData<T>(
    @Json(name = "current_page") val current_page: Int,
    val data: List<T>,
    val total: Int
)

// ─── Letter Types (from API) ──────────────────────────────────────────────────

data class LetterField(
    val name: String,
    val label: String,
    val placeholder: String?,
    val required: Boolean,
    val max: Int? = null,
    val type: String? = null,   // "text" | "select" | "date" | "time"
    val options: List<String>? = null
)

data class LetterTypeInfo(
    val code: String,
    val template: String,
    @Json(name = "number_placeholder") val numberPlaceholder: String,
    val fields: List<LetterField>
)

// ─── Request Models ───────────────────────────────────────────────────────────

data class LetterSubmitRequest(
    val nik: String,
    val phone: String,
    val email: String?,
    @Json(name = "letter_type") val letter_type: String,
    @Json(name = "dynamic_data") val dynamic_data: Map<String, String>
)

data class PbbSubmitRequest(
    @Json(name = "applicant_name") val applicant_name: String,
    val phone: String,
    val email: String?,
    val nops: List<String>
)

data class SearchNopResponse(
    val success: Boolean,
    val message: String? = null,
    val nop: String? = null,
    val tax_name: String? = null,
    val address: String? = null,
    val tax_year: Int? = null,
    val amount_due: Double? = null,
    val status: String? = null
) {
    fun toTaxObject(): TaxObject {
        return TaxObject(
            nop = nop ?: "",
            tax_name = tax_name ?: "Objek Pajak",
            address = address ?: "",
            tax_year = tax_year ?: 2026,
            amount_due = amount_due ?: 0.0
        )
    }
}

// ─── Tracking Result Models ───────────────────────────────────────────────────

data class LetterTrackResultResponse(
    val success: Boolean,
    val message: String? = null,
    val ticket_number: String? = null,
    val official_number: String? = null,
    val letter_type: String? = null,
    val status: String? = null,
    val submitted_at: String? = null,
    val download_url: String? = null
)

data class PbbTrackResultResponse(
    val success: Boolean,
    val message: String? = null,
    val ticket_code: String? = null,
    val applicant_name: String? = null,
    val status: String? = null,
    val submitted_at: String? = null,
    val total_amount: Double? = null
)

data class ComplaintTrackResultResponse(
    val success: Boolean,
    val message: String? = null,
    val ticket_code: String? = null,
    val subject: String? = null,
    val status: String? = null,
    val category: String? = null,
    val submitted_at: String? = null,
    val reporter_name: String? = null,
    val evidence_url: String? = null
)

// ─── API Service Interface ────────────────────────────────────────────────────

interface SidApiService {

    @GET("api/v1/village-info")
    suspend fun getVillageInfo(): BaseResponse<Map<String, String>>

    /** Returns Map<letterTypeName, LetterTypeInfo> */
    @GET("api/v1/letter-types")
    suspend fun getLetterTypes(): BaseResponse<Map<String, LetterTypeInfo>>

    @GET("api/v1/check-nik")
    suspend fun checkNik(@Query("nik") nik: String): BaseResponse<Any>

    @GET("api/v1/search-nop")
    suspend fun searchNop(
        @Query("nop") nop: String,
        @Query("tax_year") taxYear: Int? = null
    ): SearchNopResponse

    @GET("api/v1/news")
    suspend fun getNews(@Query("limit") limit: Int = 20): BaseResponse<PaginatedData<ApiNewsItem>>

    @GET("api/v1/letters/search")
    suspend fun searchLetterByTicket(
        @Query("ticket_number") ticket: String
    ): LetterTrackResultResponse

    @GET("api/v1/pbb/search")
    suspend fun searchPbbByTicket(
        @Query("ticket_code") ticket: String
    ): PbbTrackResultResponse

    @GET("api/v1/complaints/search")
    suspend fun searchComplaintByTicket(
        @Query("ticket_code") ticket: String
    ): ComplaintTrackResultResponse

    @POST("api/v1/letters")
    suspend fun submitLetter(@Body request: LetterSubmitRequest): BaseResponse<Any>

    @POST("api/v1/pbb")
    suspend fun submitPbb(@Body request: PbbSubmitRequest): BaseResponse<Any>

    @Multipart
    @POST("api/v1/complaints")
    suspend fun submitComplaint(
        @Part("nik") nik: RequestBody,
        @Part("reporter_name") reporterName: RequestBody,
        @Part("phone") phone: RequestBody,
        @Part("email") email: RequestBody?,
        @Part("subject") subject: RequestBody,
        @Part("category") category: RequestBody,
        @Part("location") location: RequestBody?,
        @Part("description") description: RequestBody,
        @Part evidence: MultipartBody.Part?
    ): BaseResponse<Any>
}

// ─── Retrofit Instance ────────────────────────────────────────────────────────

object ApiClient {
    // Updated URL – all traffic now goes to Render deployment
    private const val BASE_URL = "https://siddes.onrender.com/"

    private val moshi = Moshi.Builder()
        .add(KotlinJsonAdapterFactory())
        .build()

    private val client = OkHttpClient.Builder()
        // Render.com cold starts can take up to 50 seconds
        .connectTimeout(60, TimeUnit.SECONDS)
        .readTimeout(60, TimeUnit.SECONDS)
        .writeTimeout(60, TimeUnit.SECONDS)
        .addInterceptor { chain ->
            val request = chain.request().newBuilder()
                .addHeader("Accept", "application/json")
                .build()
            chain.proceed(request)
        }
        .addInterceptor(HttpLoggingInterceptor().apply {
            level = HttpLoggingInterceptor.Level.BODY
        })
        .build()

    val service: SidApiService by lazy {
        Retrofit.Builder()
            .baseUrl(BASE_URL)
            .client(client)
            .addConverterFactory(MoshiConverterFactory.create(moshi))
            .build()
            .create(SidApiService::class.java)
    }
}
