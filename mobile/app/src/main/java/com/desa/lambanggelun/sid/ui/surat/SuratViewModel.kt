package com.desa.lambanggelun.sid.ui.surat

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.desa.lambanggelun.sid.data.api.ApiClient
import com.desa.lambanggelun.sid.data.api.LetterSubmitRequest
import com.desa.lambanggelun.sid.data.api.LetterTypeInfo
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch

data class SuratUiState(
    // Step 1 – NIK
    val nik: String = "",
    val isNikLoading: Boolean = false,
    val isNikVerified: Boolean = false,
    val nikError: String? = null,
    // Step 2 – Data pemohon
    val fullName: String = "",
    val phone: String = "",
    val email: String = "",
    // Step 3 – Letter type
    val letterTypes: Map<String, LetterTypeInfo> = emptyMap(),
    val isLetterTypesLoading: Boolean = false,
    val selectedTypeName: String = "",
    val selectedTypeInfo: LetterTypeInfo? = null,
    // Step 4 – Dynamic fields
    val dynamicFieldValues: Map<String, String> = emptyMap(),
    // Submit
    val isSubmitting: Boolean = false,
    val submitSuccess: Boolean = false,
    val ticketNumber: String = "",
    val downloadUrl: String? = null,
    val downloadDocxUrl: String? = null,
    val errorMessage: String? = null
)

class SuratViewModel : ViewModel() {

    private val _state = MutableStateFlow(SuratUiState())
    val state: StateFlow<SuratUiState> = _state

    fun onNikChange(value: String) { _state.value = _state.value.copy(nik = value, nikError = null) }
    fun onPhoneChange(value: String) { _state.value = _state.value.copy(phone = value) }
    fun onEmailChange(value: String) { _state.value = _state.value.copy(email = value) }

    fun checkNik() {
        val nik = _state.value.nik
        if (nik.length < 16) {
            _state.value = _state.value.copy(nikError = "NIK harus 16 digit")
            return
        }
        viewModelScope.launch {
            _state.value = _state.value.copy(isNikLoading = true, nikError = null)
            try {
                val response = ApiClient.service.checkNik(nik)
                if (response.success) {
                    _state.value = _state.value.copy(
                        isNikVerified = true,
                        fullName = response.full_name ?: "Pemohon",
                        isNikLoading = false
                    )
                    fetchLetterTypes()
                } else {
                    _state.value = _state.value.copy(
                        isNikVerified = false,
                        nikError = response.message ?: "NIK tidak ditemukan dalam data kependudukan",
                        isNikLoading = false
                    )
                }
            } catch (e: Exception) {
                var errorMsg = "Gagal terhubung ke server. Periksa koneksi internet."
                if (e is retrofit2.HttpException) {
                    try {
                        val body = e.response()?.errorBody()?.string()
                        if (body?.contains("message") == true) {
                            val json = org.json.JSONObject(body)
                            if (json.has("message")) errorMsg = json.getString("message")
                        } else {
                            errorMsg = "NIK tidak ditemukan atau tidak sesuai format"
                        }
                    } catch (ex: Exception) { errorMsg = "NIK tidak ditemukan atau tidak sesuai format" }
                }
                _state.value = _state.value.copy(
                    isNikVerified = false,
                    nikError = errorMsg,
                    isNikLoading = false
                )
            }
        }
    }

    private fun fetchLetterTypes() {
        viewModelScope.launch {
            _state.value = _state.value.copy(isLetterTypesLoading = true)
            try {
                val response = ApiClient.service.getLetterTypes()
                if (response.success && response.data != null) {
                    _state.value = _state.value.copy(
                        letterTypes = response.data,
                        isLetterTypesLoading = false
                    )
                }
            } catch (e: Exception) {
                _state.value = _state.value.copy(isLetterTypesLoading = false)
            }
        }
    }

    fun selectLetterType(name: String) {
        val info = _state.value.letterTypes[name]
        // Reset dynamic fields when type changes
        val emptyFields = info?.fields?.associate { it.name to "" } ?: emptyMap()
        _state.value = _state.value.copy(
            selectedTypeName = name,
            selectedTypeInfo = info,
            dynamicFieldValues = emptyFields
        )
    }

    fun onDynamicFieldChange(fieldName: String, value: String) {
        val updated = _state.value.dynamicFieldValues.toMutableMap()
        updated[fieldName] = value
        _state.value = _state.value.copy(dynamicFieldValues = updated)
    }

    fun submitLetter() {
        val s = _state.value
        if (s.phone.isBlank()) {
            _state.value = s.copy(errorMessage = "Nomor WhatsApp wajib diisi")
            return
        }
        if (s.selectedTypeName.isBlank()) {
            _state.value = s.copy(errorMessage = "Pilih jenis surat terlebih dahulu")
            return
        }
        viewModelScope.launch {
            _state.value = _state.value.copy(isSubmitting = true, errorMessage = null)
            try {
                val request = LetterSubmitRequest(
                    nik = s.nik,
                    phone = s.phone,
                    email = s.email.ifBlank { null },
                    letter_type = s.selectedTypeName,
                    dynamic_data = s.dynamicFieldValues
                )
                val response = ApiClient.service.submitLetter(request)
                if (response.success) {
                    val ticket = response.ticket_number ?: response.ticket_code ?: ""
                    _state.value = _state.value.copy(
                        isSubmitting = false,
                        submitSuccess = true,
                        ticketNumber = ticket,
                        downloadUrl = response.download_url,
                        downloadDocxUrl = response.download_docx_url
                    )
                } else {
                    _state.value = _state.value.copy(
                        isSubmitting = false,
                        errorMessage = response.message ?: "Gagal mengajukan surat"
                    )
                }
            } catch (e: Exception) {
                _state.value = _state.value.copy(
                    isSubmitting = false,
                    errorMessage = "Gagal terhubung ke server. Coba lagi."
                )
            }
        }
    }

    fun resetForm() {
        _state.value = SuratUiState()
    }

    fun clearError() { _state.value = _state.value.copy(errorMessage = null) }
}
