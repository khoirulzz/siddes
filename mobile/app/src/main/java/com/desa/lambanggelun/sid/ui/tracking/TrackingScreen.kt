package com.desa.lambanggelun.sid.ui.tracking

import android.content.Intent
import android.net.Uri
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.desa.lambanggelun.sid.data.api.ApiClient
import kotlinx.coroutines.launch

enum class TrackCategory { Surat, PBB, Pengaduan }

data class TrackResult(
    val ticketCode: String,
    val status: String,
    val label1: String, val value1: String,
    val label2: String?, val value2: String?,
    val downloadUrl: String? = null
)

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun TrackingScreen(onNavigateBack: () -> Unit) {
    var ticketNumber by remember { mutableStateOf("") }
    var selectedCategory by remember { mutableStateOf(TrackCategory.Surat) }
    var isLoading by remember { mutableStateOf(false) }
    var result by remember { mutableStateOf<TrackResult?>(null) }
    var errorMsg by remember { mutableStateOf<String?>(null) }
    val scope = rememberCoroutineScope()
    val context = LocalContext.current

    fun search() {
        if (ticketNumber.isBlank()) return
        scope.launch {
            isLoading = true; result = null; errorMsg = null
            try {
                when (selectedCategory) {
                    TrackCategory.Surat -> {
                        val r = ApiClient.service.searchLetterByTicket(ticketNumber)
                        if (r.success) {
                            result = TrackResult(
                                ticketCode = r.ticket_number ?: ticketNumber,
                                status     = r.status ?: "-",
                                label1 = "Jenis Surat", value1 = r.letter_type ?: "-",
                                label2 = "No. Surat",   value2 = r.official_number ?: "-",
                                downloadUrl = r.download_url
                            )
                        } else errorMsg = r.message ?: "Tiket tidak ditemukan"
                    }
                    TrackCategory.PBB -> {
                        val r = ApiClient.service.searchPbbByTicket(ticketNumber)
                        if (r.success) {
                            result = TrackResult(
                                ticketCode = r.ticket_code ?: ticketNumber,
                                status     = r.status ?: "-",
                                label1 = "Pemohon",    value1 = r.applicant_name ?: "-",
                                label2 = "Total",      value2 = r.total_amount?.let { "Rp ${it.toLong()}" } ?: "-"
                            )
                        } else errorMsg = r.message ?: "Tiket tidak ditemukan"
                    }
                    TrackCategory.Pengaduan -> {
                        val r = ApiClient.service.searchComplaintByTicket(ticketNumber)
                        if (r.success) {
                            result = TrackResult(
                                ticketCode = r.ticket_code ?: ticketNumber,
                                status     = r.status ?: "-",
                                label1 = "Judul",    value1 = r.subject ?: "-",
                                label2 = "Kategori", value2 = r.category ?: "-"
                            )
                        } else errorMsg = r.message ?: "Tiket tidak ditemukan"
                    }
                }
            } catch (e: Exception) {
                errorMsg = "Gagal terhubung ke server. Periksa koneksi Anda."
            } finally {
                isLoading = false
            }
        }
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        androidx.compose.foundation.Image(
                            painter = androidx.compose.ui.res.painterResource(id = com.desa.lambanggelun.sid.R.drawable.loog_pekalongan),
                            contentDescription = "Logo",
                            modifier = Modifier.size(32.dp).clip(androidx.compose.foundation.shape.CircleShape)
                        )
                        Spacer(modifier = Modifier.width(12.dp))
                        Column {
                            Text("Lacak Tiket", fontWeight = FontWeight.Bold, fontSize = 16.sp)
                            Text("SID Mobile Desa", fontSize = 11.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                        }
                    }
                },
                navigationIcon = { IconButton(onClick = onNavigateBack) { Icon(Icons.Default.ArrowBack, "Back") } },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = MaterialTheme.colorScheme.surface,
                    titleContentColor = MaterialTheme.colorScheme.onSurface,
                    navigationIconContentColor = MaterialTheme.colorScheme.onSurface
                )
            )
        }
    ) { pad ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .background(MaterialTheme.colorScheme.background)
                .padding(pad)
                .verticalScroll(rememberScrollState())
                .padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(16.dp)
        ) {
            // Category selector
            Card(modifier = Modifier.fillMaxWidth(), shape = RoundedCornerShape(16.dp),
                colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface)) {
                Column(modifier = Modifier.padding(16.dp)) {
                    Text("Pilih Kategori Layanan", fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.onSurface)
                    Spacer(Modifier.height(10.dp))
                    Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                        TrackCategory.entries.forEach { cat ->
                            FilterChip(
                                selected = selectedCategory == cat,
                                onClick = { selectedCategory = cat; result = null; errorMsg = null },
                                label = { Text(cat.name) },
                                colors = FilterChipDefaults.filterChipColors(
                                    selectedContainerColor = MaterialTheme.colorScheme.primary,
                                    selectedLabelColor = Color.White
                                )
                            )
                        }
                    }
                }
            }

            // Input
            Card(modifier = Modifier.fillMaxWidth(), shape = RoundedCornerShape(16.dp),
                colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface)) {
                Column(modifier = Modifier.padding(16.dp)) {
                    Text("Nomor Tiket", fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.onSurface)
                    Spacer(Modifier.height(8.dp))
                    OutlinedTextField(
                        value = ticketNumber,
                        onValueChange = { ticketNumber = it; result = null; errorMsg = null },
                        placeholder = { Text(when (selectedCategory) {
                            TrackCategory.Surat     -> "Contoh: SRT-2026-XXXX"
                            TrackCategory.PBB       -> "Contoh: PBB-2026-XXXX"
                            TrackCategory.Pengaduan -> "Contoh: ADU-2026-XXXX"
                        }) },
                        modifier = Modifier.fillMaxWidth(),
                        singleLine = true,
                        shape = RoundedCornerShape(12.dp),
                        trailingIcon = {
                            if (ticketNumber.isNotBlank()) {
                                IconButton(onClick = { ticketNumber = ""; result = null; errorMsg = null }) {
                                    Icon(Icons.Default.Close, null)
                                }
                            }
                        }
                    )
                    Spacer(Modifier.height(12.dp))
                    Button(
                        onClick = { search() },
                        modifier = Modifier.fillMaxWidth(),
                        enabled = ticketNumber.isNotBlank() && !isLoading,
                        shape = RoundedCornerShape(12.dp)
                    ) {
                        if (isLoading) { CircularProgressIndicator(modifier = Modifier.size(18.dp), strokeWidth = 2.dp, color = Color.White); Spacer(Modifier.width(6.dp)) }
                        else Icon(Icons.Default.Search, null)
                        Spacer(Modifier.width(6.dp))
                        Text(if (isLoading) "Mencari..." else "Cari Tiket")
                    }
                }
            }

            // Error
            errorMsg?.let {
                Card(modifier = Modifier.fillMaxWidth(), shape = RoundedCornerShape(12.dp),
                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.error.copy(alpha = 0.1f))) {
                    Row(modifier = Modifier.padding(14.dp), verticalAlignment = Alignment.CenterVertically) {
                        Icon(Icons.Default.Error, null, tint = MaterialTheme.colorScheme.error)
                        Spacer(Modifier.width(8.dp))
                        Text(it, color = MaterialTheme.colorScheme.error, fontSize = 13.sp)
                    }
                }
            }

            // Result
            result?.let { r ->
                Card(modifier = Modifier.fillMaxWidth(), shape = RoundedCornerShape(16.dp),
                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface)) {
                    Column(modifier = Modifier.padding(16.dp)) {
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Icon(Icons.Default.ConfirmationNumber, null, tint = MaterialTheme.colorScheme.primary)
                            Spacer(Modifier.width(8.dp))
                            Text("Hasil Pencarian", fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.onSurface, fontSize = 15.sp)
                        }
                        Spacer(Modifier.height(12.dp))
                        TrackRow("Nomor Tiket", r.ticketCode)
                        TrackRow("Status", r.status, statusColor(r.status))
                        TrackRow(r.label1, r.value1)
                        r.label2?.let { TrackRow(it, r.value2 ?: "-") }
                        if (r.downloadUrl != null) {
                            Spacer(Modifier.height(12.dp))
                            Button(onClick = {
                                context.startActivity(Intent(Intent.ACTION_VIEW, Uri.parse(r.downloadUrl)))
                            }, modifier = Modifier.fillMaxWidth()) {
                                Icon(Icons.Default.Download, null)
                                Spacer(Modifier.width(6.dp))
                                Text("Download Surat")
                            }
                        }
                    }
                }
            }
        }
    }
}

@Composable
fun TrackRow(label: String, value: String, valueColor: Color? = null) {
    Row(modifier = Modifier.fillMaxWidth().padding(vertical = 4.dp), horizontalArrangement = Arrangement.SpaceBetween) {
        Text(label, fontSize = 13.sp, color = MaterialTheme.colorScheme.onSurfaceVariant, modifier = Modifier.weight(1f))
        Text(value, fontSize = 13.sp, fontWeight = FontWeight.SemiBold, color = valueColor ?: MaterialTheme.colorScheme.onSurface, modifier = Modifier.weight(1f))
    }
}

@Composable
fun statusColor(status: String): Color {
    return when (status.lowercase()) {
        "selesai", "completed", "done" -> Color(0xFF10B981)
        "ditolak", "rejected"         -> Color(0xFFEF4444)
        "diproses", "processing"       -> Color(0xFFF59E0B)
        else -> MaterialTheme.colorScheme.onSurface
    }
}
