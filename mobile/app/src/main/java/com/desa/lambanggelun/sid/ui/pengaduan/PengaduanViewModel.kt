package com.desa.lambanggelun.sid.ui.pengaduan

import android.net.Uri
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.desa.lambanggelun.sid.data.api.ApiClient
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch
import okhttp3.MediaType.Companion.toMediaTypeOrNull
import okhttp3.RequestBody.Companion.toRequestBody

val COMPLAINT_CATEGORIES = listOf(
    "Infrastruktur",
    "Pelayanan Publik",
    "Keamanan",
    "Sosial",
    "Lainnya"
)

data class PengaduanUiState(
    val nik: String = "",
    val reporterName: String = "",
    val phone: String = "",
    val email: String = "",
    val subject: String = "",
    val category: String = COMPLAINT_CATEGORIES[0],
    val location: String = "",
    val description: String = "",
    val evidenceUri: Uri? = null,
    val evidenceFileName: String? = null,
    val isSubmitting: Boolean = false,
    val submitSuccess: Boolean = false,
    val ticketCode: String = "",
    val errorMessage: String? = null
)

class PengaduanViewModel : ViewModel() {
    private val _state = MutableStateFlow(PengaduanUiState())
    val state: StateFlow<PengaduanUiState> = _state

    fun onNikChange(v: String) { _state.value = _state.value.copy(nik = v) }
    fun onReporterNameChange(v: String) { _state.value = _state.value.copy(reporterName = v) }
    fun onPhoneChange(v: String) { _state.value = _state.value.copy(phone = v) }
    fun onEmailChange(v: String) { _state.value = _state.value.copy(email = v) }
    fun onSubjectChange(v: String) { _state.value = _state.value.copy(subject = v) }
    fun onCategoryChange(v: String) { _state.value = _state.value.copy(category = v) }
    fun onLocationChange(v: String) { _state.value = _state.value.copy(location = v) }
    fun onDescriptionChange(v: String) { _state.value = _state.value.copy(description = v) }
    fun onEvidenceSelected(uri: Uri?, fileName: String?) {
        _state.value = _state.value.copy(evidenceUri = uri, evidenceFileName = fileName)
    }
    fun clearError() { _state.value = _state.value.copy(errorMessage = null) }
    fun reset() { _state.value = PengaduanUiState() }

    fun submit(contentResolver: android.content.ContentResolver) {
        val s = _state.value
        if (s.nik.isBlank() || s.reporterName.isBlank() || s.phone.isBlank()) {
            _state.value = s.copy(errorMessage = "NIK, nama, dan no. WA wajib diisi")
            return
        }
        if (s.subject.isBlank() || s.description.isBlank()) {
            _state.value = s.copy(errorMessage = "Judul dan deskripsi pengaduan wajib diisi")
            return
        }

        viewModelScope.launch {
            _state.value = _state.value.copy(isSubmitting = true, errorMessage = null)
            try {
                fun String.toRB() = this.toRequestBody("text/plain".toMediaTypeOrNull())

                // Build multipart evidence part if selected
                val evidencePart = s.evidenceUri?.let { uri ->
                    val stream = contentResolver.openInputStream(uri)
                    val bytes = stream?.readBytes() ?: byteArrayOf()
                    stream?.close()
                    val mimeType = contentResolver.getType(uri) ?: "image/jpeg"
                    val rb = bytes.toRequestBody(mimeType.toMediaTypeOrNull())
                    okhttp3.MultipartBody.Part.createFormData(
                        "evidence", s.evidenceFileName ?: "evidence.jpg", rb
                    )
                }

                val response = ApiClient.service.submitComplaint(
                    nik          = s.nik.toRB(),
                    reporterName = s.reporterName.toRB(),
                    phone        = s.phone.toRB(),
                    email        = if (s.email.isNotBlank()) s.email.toRB() else null,
                    subject      = s.subject.toRB(),
                    category     = s.category.toRB(),
                    location     = if (s.location.isNotBlank()) s.location.toRB() else null,
                    description  = s.description.toRB(),
                    evidence     = evidencePart
                )
                if (response.success) {
                    _state.value = _state.value.copy(
                        isSubmitting = false,
                        submitSuccess = true,
                        ticketCode = response.ticket_code ?: response.ticket_number ?: ""
                    )
                } else {
                    _state.value = _state.value.copy(isSubmitting = false, errorMessage = response.message ?: "Gagal mengirim laporan")
                }
            } catch (e: Exception) {
                var errorMsg = "Gagal terhubung ke server"
                if (e is retrofit2.HttpException) {
                    try {
                        val body = e.response()?.errorBody()?.string()
                        if (body?.contains("message") == true) {
                            val json = org.json.JSONObject(body)
                            if (json.has("message")) errorMsg = json.getString("message")
                        }
                    } catch (ex: Exception) { /* ignore */ }
                }
                _state.value = _state.value.copy(isSubmitting = false, errorMessage = errorMsg)
            }
        }
    }
}
