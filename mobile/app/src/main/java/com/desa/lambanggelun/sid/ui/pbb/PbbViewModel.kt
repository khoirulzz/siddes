package com.desa.lambanggelun.sid.ui.pbb

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.desa.lambanggelun.sid.data.api.ApiClient
import com.desa.lambanggelun.sid.data.api.PbbSubmitRequest
import com.desa.lambanggelun.sid.data.api.TaxObject
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch

data class PbbUiState(
    val applicantName: String = "",
    val phone: String = "",
    val email: String = "",
    val nopInput: String = "",
    val isSearchingNop: Boolean = false,
    val nopError: String? = null,
    val nopList: List<TaxObject> = emptyList(),
    val isSubmitting: Boolean = false,
    val submitSuccess: Boolean = false,
    val ticketCode: String = "",
    val errorMessage: String? = null
)

class PbbViewModel : ViewModel() {
    private val _state = MutableStateFlow(PbbUiState())
    val state: StateFlow<PbbUiState> = _state

    fun onNameChange(v: String) { _state.value = _state.value.copy(applicantName = v) }
    fun onPhoneChange(v: String) { _state.value = _state.value.copy(phone = v) }
    fun onEmailChange(v: String) { _state.value = _state.value.copy(email = v) }
    fun onNopInputChange(v: String) { _state.value = _state.value.copy(nopInput = v, nopError = null) }

    fun searchNop() {
        val nop = _state.value.nopInput.trim()
        if (nop.isBlank()) return
        viewModelScope.launch {
            _state.value = _state.value.copy(isSearchingNop = true, nopError = null)
            try {
                val response = ApiClient.service.searchNop(nop)
                if (response.success) {
                    val taxObj = response.toTaxObject()
                    val existing = _state.value.nopList.any { it.nop == taxObj.nop }
                    if (existing) {
                        _state.value = _state.value.copy(isSearchingNop = false, nopError = "NOP sudah ditambahkan")
                    } else {
                        _state.value = _state.value.copy(
                            isSearchingNop = false,
                            nopList = _state.value.nopList + taxObj,
                            nopInput = ""
                        )
                    }
                } else {
                    _state.value = _state.value.copy(isSearchingNop = false, nopError = response.message ?: "NOP tidak ditemukan")
                }
            } catch (e: Exception) {
                var errorMsg = "Gagal terhubung ke server"
                if (e is retrofit2.HttpException) {
                    try {
                        val body = e.response()?.errorBody()?.string()
                        if (body?.contains("message") == true) {
                            val json = org.json.JSONObject(body)
                            if (json.has("message")) errorMsg = json.getString("message")
                        } else {
                            errorMsg = "NOP tidak ditemukan"
                        }
                    } catch (ex: Exception) { errorMsg = "NOP tidak ditemukan" }
                }
                _state.value = _state.value.copy(isSearchingNop = false, nopError = errorMsg)
            }
        }
    }

    fun removeNop(nop: String) {
        _state.value = _state.value.copy(nopList = _state.value.nopList.filter { it.nop != nop })
    }

    fun submit() {
        val s = _state.value
        if (s.applicantName.isBlank() || s.phone.isBlank()) {
            _state.value = s.copy(errorMessage = "Nama dan nomor WA wajib diisi")
            return
        }
        if (s.nopList.isEmpty()) {
            _state.value = s.copy(errorMessage = "Tambahkan minimal 1 NOP")
            return
        }
        viewModelScope.launch {
            _state.value = _state.value.copy(isSubmitting = true, errorMessage = null)
            try {
                val request = PbbSubmitRequest(
                    applicant_name = s.applicantName,
                    phone = s.phone,
                    email = s.email.ifBlank { null },
                    nops = s.nopList.map { it.nop }
                )
                val response = ApiClient.service.submitPbb(request)
                if (response.success) {
                    _state.value = _state.value.copy(
                        isSubmitting = false,
                        submitSuccess = true,
                        ticketCode = response.ticket_code ?: response.ticket_number ?: ""
                    )
                } else {
                    _state.value = _state.value.copy(isSubmitting = false, errorMessage = response.message ?: "Gagal mengajukan")
                }
            } catch (e: Exception) {
                _state.value = _state.value.copy(isSubmitting = false, errorMessage = "Gagal terhubung ke server")
            }
        }
    }

    fun reset() { _state.value = PbbUiState() }
    fun clearError() { _state.value = _state.value.copy(errorMessage = null) }
}
