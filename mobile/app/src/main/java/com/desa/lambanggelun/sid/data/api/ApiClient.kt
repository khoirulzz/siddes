package com.desa.lambanggelun.sid.data.api

import retrofit2.Retrofit
import retrofit2.converter.moshi.MoshiConverterFactory
import retrofit2.http.*
import com.squareup.moshi.Moshi
import com.squareup.moshi.kotlin.reflect.KotlinJsonAdapterFactory
import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import okhttp3.MultipartBody
import okhttp3.RequestBody

// --- Models ---
data class BaseResponse<T>(
    val success: Boolean,
    val message: String? = null,
    val data: T? = null,
    // Form fields specific to some endpoints
    val full_name: String? = null,
    val address_detail: String? = null,
    val ticket_number: String? = null,
    val ticket_code: String? = null
)

data class TaxObject(
    val nop: String,
    val tax_name: String,
    val address: String,
    val tax_year: Int,
    val amount_due: Double
)

data class NewsItem(
    val id: Int,
    val title: String,
    val slug: String,
    val excerpt: String?,
    val thumbnail: String?,
    val clean_content: String?,
    val published_at: String?
)

data class PaginatedData<T>(
    val current_page: Int,
    val data: List<T>,
    val total: Int
)

data class LetterSubmitRequest(
    val nik: String,
    val phone: String,
    val email: String?,
    val letter_type: String,
    val dynamic_data: Map<String, String>
)

data class PbbSubmitRequest(
    val applicant_name: String,
    val phone: String,
    val email: String?,
    val nops: List<TaxObject>
)

// --- API Service ---
interface SidApiService {
    @GET("api/v1/village-info")
    suspend fun getVillageInfo(): BaseResponse<Map<String, String>>

    @GET("api/v1/letter-types")
    suspend fun getLetterTypes(): BaseResponse<Map<String, Any>>

    @GET("api/v1/check-nik")
    suspend fun checkNik(@Query("nik") nik: String): BaseResponse<Any>

    @GET("api/v1/search-nop")
    suspend fun searchNop(@Query("nop") nop: String, @Query("tax_year") taxYear: Int? = null): BaseResponse<TaxObject>

    @GET("api/v1/news")
    suspend fun getNews(@Query("limit") limit: Int = 10): BaseResponse<PaginatedData<NewsItem>>

    @GET("api/v1/letters/search")
    suspend fun searchLetterByTicket(@Query("ticket_number") ticket: String): BaseResponse<Map<String, Any>>

    @GET("api/v1/pbb/search")
    suspend fun searchPbbByTicket(@Query("ticket_code") ticket: String): BaseResponse<Map<String, Any>>

    @GET("api/v1/complaints/search")
    suspend fun searchComplaintByTicket(@Query("ticket_code") ticket: String): BaseResponse<Map<String, Any>>

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

// --- Retrofit Instance ---
object ApiClient {
    private const val BASE_URL = "https://desalambanggelun.web.id/"

    private val moshi = Moshi.Builder()
        .add(KotlinJsonAdapterFactory())
        .build()

    private val client = OkHttpClient.Builder()
        .addInterceptor(HttpLoggingInterceptor().apply { level = HttpLoggingInterceptor.Level.BODY })
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
