package com.desa.lambanggelun.sid.ui.ai

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.lazy.rememberLazyListState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.viewmodel.compose.viewModel
import com.desa.lambanggelun.sid.theme.AiPurple
import kotlinx.coroutines.launch

private val POPULAR_QUESTIONS = listOf(
    "Bagaimana cara mengajukan surat keterangan domisili?",
    "Syarat dan prosedur pembuatan KTP di desa?",
    "Bagaimana cara membayar PBB secara online?",
    "Kapan jadwal pelayanan kantor desa?",
    "Bagaimana cara membuat pengaduan?"
)

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AiAssistantScreen(vm: AiViewModel = viewModel()) {
    val state by vm.state.collectAsState()
    val listState = rememberLazyListState()
    val scope = rememberCoroutineScope()
    val isInChat = state.messages.isNotEmpty()

    // Auto scroll to bottom on new message
    LaunchedEffect(state.messages.size) {
        if (state.messages.isNotEmpty()) {
            listState.animateScrollToItem(state.messages.size - 1)
        }
    }

    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(MaterialTheme.colorScheme.background)
    ) {
        // ── HEADER ────────────────────────────────────────────────────────────
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(horizontal = 20.dp, vertical = 8.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            Column(modifier = Modifier.weight(1f)) {
                Text("Bantuan AI", fontSize = 22.sp, fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.onBackground)
                Text("Asisten virtual siap membantu Anda", fontSize = 13.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
            }
            // Clear chat button (only show when in chat)
            if (isInChat) {
                IconButton(onClick = { vm.clearChat() }) {
                    Icon(Icons.Default.Delete, "Hapus Riwayat", tint = MaterialTheme.colorScheme.onSurfaceVariant)
                }
            }
            Box(
                modifier = Modifier.size(48.dp).clip(CircleShape).background(MaterialTheme.colorScheme.primary.copy(alpha = 0.15f)),
                contentAlignment = Alignment.Center
            ) {
                Icon(Icons.Default.SmartToy, "AI", tint = MaterialTheme.colorScheme.primary, modifier = Modifier.size(28.dp))
            }
        }

        // ── MAIN CONTENT AREA ─────────────────────────────────────────────────
        if (!isInChat) {
            // ── IDLE: Show popular questions ──────────────────────────────────
            LazyColumn(
                modifier = Modifier.weight(1f),
                contentPadding = PaddingValues(horizontal = 16.dp, vertical = 4.dp),
                verticalArrangement = Arrangement.spacedBy(12.dp)
            ) {
                item {
                    // Popular questions card
                    Card(modifier = Modifier.fillMaxWidth(), shape = RoundedCornerShape(16.dp),
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface)) {
                        Column(modifier = Modifier.padding(vertical = 8.dp)) {
                            Text("Pertanyaan Populer", fontSize = 15.sp, fontWeight = FontWeight.Bold,
                                color = MaterialTheme.colorScheme.onSurface, modifier = Modifier.padding(horizontal = 16.dp, vertical = 8.dp))
                            POPULAR_QUESTIONS.forEachIndexed { idx, q ->
                                if (idx > 0) HorizontalDivider(modifier = Modifier.padding(horizontal = 16.dp), color = MaterialTheme.colorScheme.outline.copy(alpha = 0.2f), thickness = 0.5.dp)
                                Row(
                                    modifier = Modifier.fillMaxWidth().clickable { vm.sendMessage(q) }.padding(horizontal = 16.dp, vertical = 12.dp),
                                    verticalAlignment = Alignment.CenterVertically
                                ) {
                                    Box(modifier = Modifier.size(34.dp).clip(CircleShape).background(AiPurple.copy(alpha = 0.15f)), contentAlignment = Alignment.Center) {
                                        Icon(Icons.Default.QuestionMark, null, tint = AiPurple, modifier = Modifier.size(17.dp))
                                    }
                                    Spacer(Modifier.width(10.dp))
                                    Text(q, fontSize = 13.sp, color = MaterialTheme.colorScheme.onSurface, modifier = Modifier.weight(1f), lineHeight = 18.sp)
                                    Spacer(Modifier.width(6.dp))
                                    Icon(Icons.Default.ChevronRight, null, tint = MaterialTheme.colorScheme.onSurfaceVariant, modifier = Modifier.size(18.dp))
                                }
                            }
                        }
                    }
                }
                item {
                    // AI Status card
                    Card(modifier = Modifier.fillMaxWidth(), shape = RoundedCornerShape(16.dp),
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface)) {
                        Column(modifier = Modifier.padding(16.dp)) {
                            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
                                Text("Tanya AI Assistant", fontSize = 15.sp, fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.onSurface)
                                Row(verticalAlignment = Alignment.CenterVertically) {
                                    Box(modifier = Modifier.size(8.dp).clip(CircleShape).background(Color(0xFF22C55E)))
                                    Spacer(Modifier.width(4.dp))
                                    Text("Online", fontSize = 12.sp, color = Color(0xFF22C55E), fontWeight = FontWeight.Medium)
                                }
                            }
                            Spacer(Modifier.height(4.dp))
                            Text("Ketik pertanyaan Anda tentang layanan desa di bawah", fontSize = 12.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                        }
                    }
                }
                item {
                    Text("AI dapat membuat kesalahan. Informasi penting harap konfirmasi ke kantor desa.",
                        fontSize = 11.sp, color = MaterialTheme.colorScheme.onSurfaceVariant.copy(alpha = 0.6f),
                        textAlign = TextAlign.Center, lineHeight = 16.sp, modifier = Modifier.fillMaxWidth().padding(horizontal = 20.dp))
                }
                item { Spacer(Modifier.height(4.dp)) }
            }
        } else {
            // ── CHAT HISTORY ──────────────────────────────────────────────────
            LazyColumn(
                state = listState,
                modifier = Modifier.weight(1f),
                contentPadding = PaddingValues(horizontal = 16.dp, vertical = 8.dp),
                verticalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                items(state.messages) { msg ->
                    ChatBubble(msg)
                }
            }
        }

        // ── INPUT BAR ─────────────────────────────────────────────────────────
        Surface(modifier = Modifier.fillMaxWidth(), color = MaterialTheme.colorScheme.surface, shadowElevation = 8.dp) {
            Row(modifier = Modifier.padding(12.dp).fillMaxWidth().navigationBarsPadding(), verticalAlignment = Alignment.CenterVertically) {
                OutlinedTextField(
                    value = state.inputText,
                    onValueChange = { vm.onInputChange(it) },
                    placeholder = { Text("Tanyakan sesuatu...", fontSize = 13.sp) },
                    modifier = Modifier.weight(1f),
                    shape = RoundedCornerShape(14.dp),
                    maxLines = 3,
                    colors = OutlinedTextFieldDefaults.colors(
                        focusedBorderColor = MaterialTheme.colorScheme.primary,
                        unfocusedBorderColor = MaterialTheme.colorScheme.outline.copy(alpha = 0.3f),
                        focusedContainerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.3f),
                        unfocusedContainerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.3f)
                    ),
                    textStyle = androidx.compose.ui.text.TextStyle(fontSize = 13.sp, color = MaterialTheme.colorScheme.onSurface)
                )
                Spacer(Modifier.width(8.dp))
                IconButton(
                    onClick = { vm.sendMessage() },
                    enabled = state.inputText.isNotBlank() && !state.isLoading,
                    modifier = Modifier.size(48.dp).clip(RoundedCornerShape(14.dp)).background(
                        if (state.inputText.isNotBlank() && !state.isLoading) MaterialTheme.colorScheme.primary
                        else MaterialTheme.colorScheme.surfaceVariant
                    )
                ) {
                    if (state.isLoading) CircularProgressIndicator(modifier = Modifier.size(20.dp), strokeWidth = 2.dp, color = Color.White)
                    else Icon(Icons.Default.Send, "Kirim", tint = Color.White, modifier = Modifier.size(20.dp))
                }
            }
        }
    }
}

@Composable
fun ChatBubble(msg: ChatMessage) {
    val isUser = msg.role == "user"
    val isError = msg.role == "error"

    Row(
        modifier = Modifier.fillMaxWidth(),
        horizontalArrangement = if (isUser) Arrangement.End else Arrangement.Start
    ) {
        if (!isUser) {
            Box(
                modifier = Modifier.size(30.dp).clip(CircleShape).background(MaterialTheme.colorScheme.primary.copy(alpha = 0.15f)).align(Alignment.Bottom),
                contentAlignment = Alignment.Center
            ) {
                Icon(Icons.Default.SmartToy, null, tint = MaterialTheme.colorScheme.primary, modifier = Modifier.size(17.dp))
            }
            Spacer(Modifier.width(6.dp))
        }

        Box(
            modifier = Modifier
                .widthIn(max = 280.dp)
                .clip(RoundedCornerShape(
                    topStart = if (isUser) 16.dp else 4.dp,
                    topEnd = if (isUser) 4.dp else 16.dp,
                    bottomStart = 16.dp, bottomEnd = 16.dp
                ))
                .background(when {
                    isUser  -> MaterialTheme.colorScheme.primary
                    isError -> MaterialTheme.colorScheme.error.copy(alpha = 0.15f)
                    else    -> MaterialTheme.colorScheme.surface
                })
                .padding(10.dp, 8.dp)
        ) {
            if (msg.isLoading) {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    repeat(3) {
                        Box(modifier = Modifier.size(6.dp).clip(CircleShape).background(MaterialTheme.colorScheme.onSurfaceVariant))
                        if (it < 2) Spacer(Modifier.width(4.dp))
                    }
                }
            } else {
                Text(
                    text = msg.content,
                    color = when {
                        isUser  -> Color.White
                        isError -> MaterialTheme.colorScheme.error
                        else    -> MaterialTheme.colorScheme.onSurface
                    },
                    fontSize = 13.sp,
                    lineHeight = 19.sp
                )
            }
        }
    }
}
