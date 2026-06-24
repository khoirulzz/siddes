package com.desa.lambanggelun.sid.ui.ai

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.desa.lambanggelun.sid.data.GroqRepository
import com.desa.lambanggelun.sid.data.api.GroqMessage
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch

data class ChatMessage(
    val id: Long = System.currentTimeMillis(),
    val role: String,  // "user" | "assistant" | "error"
    val content: String,
    val isLoading: Boolean = false
)

data class AiUiState(
    val messages: List<ChatMessage> = emptyList(),
    val inputText: String = "",
    val isLoading: Boolean = false
)

class AiViewModel : ViewModel() {

    private val _state = MutableStateFlow(AiUiState())
    val state: StateFlow<AiUiState> = _state

    fun onInputChange(v: String) { _state.value = _state.value.copy(inputText = v) }

    fun sendMessage(question: String = _state.value.inputText.trim()) {
        if (question.isBlank() || _state.value.isLoading) return

        // Add user message
        val userMsg = ChatMessage(role = "user", content = question)
        val loadingMsg = ChatMessage(role = "assistant", content = "...", isLoading = true)
        _state.value = _state.value.copy(
            messages = _state.value.messages + userMsg + loadingMsg,
            inputText = "",
            isLoading = true
        )

        viewModelScope.launch {
            // Build conversation history (exclude loading message)
            val history = _state.value.messages
                .filter { !it.isLoading && it.role in listOf("user", "assistant") }
                .dropLast(1) // exclude current user message (already in question param)
                .map { GroqMessage(role = it.role, content = it.content) }

            val result = GroqRepository.ask(question, history)

            // Remove loading indicator, add actual response
            val msgs = _state.value.messages.toMutableList()
            msgs.removeLastOrNull() // remove loading

            result.fold(
                onSuccess = { answer ->
                    msgs.add(ChatMessage(role = "assistant", content = answer))
                },
                onFailure = { err ->
                    msgs.add(ChatMessage(role = "error", content = "Maaf, terjadi kesalahan: ${err.message ?: "Coba lagi."}"))
                }
            )
            _state.value = _state.value.copy(messages = msgs, isLoading = false)
        }
    }

    fun clearChat() {
        _state.value = AiUiState()
        GroqRepository.clearCache()
    }
}
