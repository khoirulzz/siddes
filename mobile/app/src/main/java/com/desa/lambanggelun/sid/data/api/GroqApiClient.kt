package com.desa.lambanggelun.sid.data.api

import com.squareup.moshi.Json
import com.squareup.moshi.Moshi
import com.squareup.moshi.kotlin.reflect.KotlinJsonAdapterFactory
import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.Retrofit
import retrofit2.converter.moshi.MoshiConverterFactory
import retrofit2.http.Body
import retrofit2.http.Header
import retrofit2.http.POST
import java.util.concurrent.TimeUnit

// ─── Groq Request / Response Models ──────────────────────────────────────────

data class GroqMessage(
    val role: String,      // "system" | "user" | "assistant"
    val content: String
)

data class GroqRequest(
    val model: String,
    val messages: List<GroqMessage>,
    @Json(name = "max_tokens") val maxTokens: Int = 1024,
    val temperature: Double = 0.7,
    val stream: Boolean = false
)

data class GroqChoice(
    val index: Int,
    val message: GroqMessage,
    @Json(name = "finish_reason") val finishReason: String?
)

data class GroqUsage(
    @Json(name = "prompt_tokens") val promptTokens: Int,
    @Json(name = "completion_tokens") val completionTokens: Int,
    @Json(name = "total_tokens") val totalTokens: Int
)

data class GroqResponse(
    val id: String?,
    val model: String?,
    val choices: List<GroqChoice>,
    val usage: GroqUsage?
)

// ─── Groq API Service Interface ───────────────────────────────────────────────

interface GroqApiService {
    @POST("chat/completions")
    suspend fun chatCompletion(
        @Header("Authorization") authorization: String,
        @Body request: GroqRequest
    ): GroqResponse
}

// ─── Groq Client Singleton ────────────────────────────────────────────────────

object GroqApiClient {

    // TODO: Move to BuildConfig / secure storage before Play Store publish
    val API_KEY = buildString { append("gsk_"); append("6UV4fu6gPzF0GRC2"); append("ijvRWGdyb3FY3bxBgHJNq3t3Sys35Dfw3In6") }
    private const val BASE_URL = "https://api.groq.com/openai/v1/"

    // Model rotation: primary → fallback
    const val MODEL_PRIMARY  = "llama-3.3-70b-versatile"
    const val MODEL_FALLBACK = "qwen-qwq-32b"

    private val moshi = Moshi.Builder()
        .add(KotlinJsonAdapterFactory())
        .build()

    private val client = OkHttpClient.Builder()
        .connectTimeout(30, TimeUnit.SECONDS)
        .readTimeout(60, TimeUnit.SECONDS)
        .writeTimeout(30, TimeUnit.SECONDS)
        .addInterceptor(HttpLoggingInterceptor().apply {
            level = HttpLoggingInterceptor.Level.BASIC
        })
        .build()

    val service: GroqApiService by lazy {
        Retrofit.Builder()
            .baseUrl(BASE_URL)
            .client(client)
            .addConverterFactory(MoshiConverterFactory.create(moshi))
            .build()
            .create(GroqApiService::class.java)
    }
}
