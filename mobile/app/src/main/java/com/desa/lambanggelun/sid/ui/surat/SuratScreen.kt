package com.desa.lambanggelun.sid.ui.surat

import android.app.DatePickerDialog
import android.app.TimePickerDialog
import android.content.Intent
import android.net.Uri
import androidx.compose.foundation.Image
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalClipboardManager
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.AnnotatedString
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.viewmodel.compose.viewModel
import com.desa.lambanggelun.sid.data.api.LetterField
import java.util.*

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun SuratScreen(
    onNavigateBack: () -> Unit,
    vm: SuratViewModel = viewModel()
) {
    val state by vm.state.collectAsState()
    val clipboard = LocalClipboardManager.current

    // Error snackbar
    val snackbarHostState = remember { SnackbarHostState() }
    LaunchedEffect(state.errorMessage) {
        state.errorMessage?.let {
            snackbarHostState.showSnackbar(it)
            vm.clearError()
        }
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Image(
                            painter = androidx.compose.ui.res.painterResource(id = com.desa.lambanggelun.sid.R.drawable.loog_pekalongan),
                            contentDescription = "Logo",
                            modifier = Modifier.size(32.dp).clip(androidx.compose.foundation.shape.CircleShape)
                        )
                        Spacer(modifier = Modifier.width(12.dp))
                        Column {
                            Text("Surat Online", fontWeight = FontWeight.Bold, fontSize = 16.sp)
                            Text("SID Mobile Desa", fontSize = 11.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                        }
                    }
                },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.Default.ArrowBack, contentDescription = "Back")
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = MaterialTheme.colorScheme.surface,
                    titleContentColor = MaterialTheme.colorScheme.onSurface,
                    navigationIconContentColor = MaterialTheme.colorScheme.onSurface
                )
            )
        },
        snackbarHost = { SnackbarHost(snackbarHostState) }
    ) { paddingValues ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .background(MaterialTheme.colorScheme.background)
                .padding(paddingValues)
                .verticalScroll(rememberScrollState())
                .padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(16.dp)
        ) {
            // ── SUCCESS SCREEN ────────────────────────────────────────────────
            if (state.submitSuccess) {
                SuccessCard(
                    ticketNumber = state.ticketNumber,
                    downloadUrl = state.downloadUrl,
                    downloadDocxUrl = state.downloadDocxUrl,
                    onCopy = { clipboard.setText(AnnotatedString(state.ticketNumber)) },
                    onReset = { vm.resetForm() }
                )
                return@Column
            }

            // ── STEP 1: NIK VERIFICATION ──────────────────────────────────────
            StepCard(step = "1", title = "Verifikasi NIK") {
                OutlinedTextField(
                    value = state.nik,
                    onValueChange = { if (it.length <= 16) vm.onNikChange(it) },
                    label = { Text("NIK (16 Digit)") },
                    modifier = Modifier.fillMaxWidth(),
                    singleLine = true,
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                    isError = state.nikError != null,
                    supportingText = { state.nikError?.let { Text(it, color = MaterialTheme.colorScheme.error) } },
                    trailingIcon = {
                        when {
                            state.isNikLoading -> CircularProgressIndicator(modifier = Modifier.size(20.dp), strokeWidth = 2.dp)
                            state.isNikVerified -> Icon(Icons.Default.CheckCircle, null, tint = Color(0xFF10B981))
                        }
                    }
                )
                Spacer(modifier = Modifier.height(8.dp))
                Button(
                    onClick = { vm.checkNik() },
                    modifier = Modifier.fillMaxWidth(),
                    enabled = state.nik.length == 16 && !state.isNikLoading && !state.isNikVerified
                ) {
                    Text(if (state.isNikLoading) "Memeriksa..." else if (state.isNikVerified) "✓ NIK Terverifikasi" else "Cek NIK")
                }
            }

            // ── STEP 2: DATA PEMOHON (after NIK verified) ─────────────────────
            if (state.isNikVerified) {
                StepCard(step = "2", title = "Data Pemohon") {
                    OutlinedTextField(
                        value = state.fullName,
                        onValueChange = {},
                        label = { Text("Nama Lengkap") },
                        readOnly = true,
                        modifier = Modifier.fillMaxWidth(),
                        trailingIcon = { Icon(Icons.Default.Person, null) }
                    )
                    Spacer(modifier = Modifier.height(8.dp))
                    OutlinedTextField(
                        value = state.phone,
                        onValueChange = { vm.onPhoneChange(it) },
                        label = { Text("No. WhatsApp Aktif *") },
                        modifier = Modifier.fillMaxWidth(),
                        singleLine = true,
                        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Phone)
                    )
                    Spacer(modifier = Modifier.height(8.dp))
                    OutlinedTextField(
                        value = state.email,
                        onValueChange = { vm.onEmailChange(it) },
                        label = { Text("Email (Opsional)") },
                        modifier = Modifier.fillMaxWidth(),
                        singleLine = true,
                        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Email)
                    )
                }

                // ── STEP 3: PILIH JENIS SURAT ─────────────────────────────────
                StepCard(step = "3", title = "Pilih Jenis Surat") {
                    if (state.isLetterTypesLoading) {
                        Box(modifier = Modifier.fillMaxWidth(), contentAlignment = Alignment.Center) {
                            CircularProgressIndicator()
                        }
                    } else {
                        var expanded by remember { mutableStateOf(false) }
                        ExposedDropdownMenuBox(
                            expanded = expanded,
                            onExpandedChange = { expanded = !expanded }
                        ) {
                            OutlinedTextField(
                                value = state.selectedTypeName.ifBlank { "-- Pilih Jenis Surat --" },
                                onValueChange = {},
                                readOnly = true,
                                label = { Text("Jenis Surat") },
                                trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = expanded) },
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .menuAnchor()
                            )
                            ExposedDropdownMenu(expanded = expanded, onDismissRequest = { expanded = false }) {
                                state.letterTypes.keys.forEach { typeName ->
                                    DropdownMenuItem(
                                        text = { Text(typeName) },
                                        onClick = {
                                            vm.selectLetterType(typeName)
                                            expanded = false
                                        }
                                    )
                                }
                            }
                        }
                    }
                }

                // ── STEP 4: DYNAMIC FIELDS ────────────────────────────────────
                if (state.selectedTypeInfo != null) {
                    val info = state.selectedTypeInfo!!
                    StepCard(step = "4", title = "Detail ${state.selectedTypeName}") {
                        info.fields.forEach { field ->
                            DynamicFieldInput(
                                field = field,
                                value = state.dynamicFieldValues[field.name] ?: "",
                                onValueChange = { vm.onDynamicFieldChange(field.name, it) }
                            )
                            Spacer(modifier = Modifier.height(8.dp))
                        }
                        Spacer(modifier = Modifier.height(8.dp))
                        Button(
                            onClick = { vm.submitLetter() },
                            modifier = Modifier.fillMaxWidth(),
                            enabled = !state.isSubmitting
                        ) {
                            if (state.isSubmitting) {
                                CircularProgressIndicator(modifier = Modifier.size(20.dp), strokeWidth = 2.dp, color = Color.White)
                                Spacer(modifier = Modifier.width(8.dp))
                            }
                            Text(if (state.isSubmitting) "Mengajukan..." else "Ajukan Surat")
                        }
                    }
                }
            }
        }
    }
}

@Composable
fun StepCard(step: String, title: String, content: @Composable ColumnScope.() -> Unit) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(16.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface)
    ) {
        Column(modifier = Modifier.padding(16.dp)) {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Box(
                    modifier = Modifier
                        .size(28.dp)
                        .clip(RoundedCornerShape(8.dp))
                        .background(MaterialTheme.colorScheme.primary),
                    contentAlignment = Alignment.Center
                ) {
                    Text(step, color = Color.White, fontSize = 13.sp, fontWeight = FontWeight.Bold)
                }
                Spacer(modifier = Modifier.width(10.dp))
                Text(title, fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.onSurface, fontSize = 15.sp)
            }
            Spacer(modifier = Modifier.height(12.dp))
            content()
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun DynamicFieldInput(field: LetterField, value: String, onValueChange: (String) -> Unit) {
    val context = LocalContext.current

    when (field.type) {
        "select" -> {
            var expanded by remember { mutableStateOf(false) }
            ExposedDropdownMenuBox(expanded = expanded, onExpandedChange = { expanded = !expanded }) {
                OutlinedTextField(
                    value = value.ifBlank { "-- Pilih --" },
                    onValueChange = {},
                    readOnly = true,
                    label = { Text(field.label) },
                    trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = expanded) },
                    modifier = Modifier.fillMaxWidth().menuAnchor(),
                    isError = field.required && value.isBlank()
                )
                ExposedDropdownMenu(expanded = expanded, onDismissRequest = { expanded = false }) {
                    field.options?.forEach { opt ->
                        DropdownMenuItem(
                            text = { Text(opt) },
                            onClick = { onValueChange(opt); expanded = false }
                        )
                    }
                }
            }
        }
        "date" -> {
            val cal = Calendar.getInstance()
            OutlinedTextField(
                value = value,
                onValueChange = {},
                readOnly = true,
                label = { Text(field.label) },
                placeholder = { Text("Ketuk untuk memilih tanggal") },
                modifier = Modifier
                    .fillMaxWidth()
                    .clickable {
                        DatePickerDialog(context, { _, y, m, d ->
                            onValueChange("$y-${(m + 1).toString().padStart(2, '0')}-${d.toString().padStart(2, '0')}")
                        }, cal.get(Calendar.YEAR), cal.get(Calendar.MONTH), cal.get(Calendar.DAY_OF_MONTH)).show()
                    },
                trailingIcon = { Icon(Icons.Default.CalendarToday, null) },
                isError = field.required && value.isBlank()
            )
        }
        "time" -> {
            val cal = Calendar.getInstance()
            OutlinedTextField(
                value = value,
                onValueChange = {},
                readOnly = true,
                label = { Text(field.label) },
                placeholder = { Text("Ketuk untuk memilih waktu") },
                modifier = Modifier
                    .fillMaxWidth()
                    .clickable {
                        TimePickerDialog(context, { _, h, m ->
                            onValueChange("${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}")
                        }, cal.get(Calendar.HOUR_OF_DAY), cal.get(Calendar.MINUTE), true).show()
                    },
                trailingIcon = { Icon(Icons.Default.AccessTime, null) },
                isError = field.required && value.isBlank()
            )
        }
        else -> {
            OutlinedTextField(
                value = value,
                onValueChange = { if (field.max == null || it.length <= field.max) onValueChange(it) },
                label = { Text(field.label + if (field.required) " *" else "") },
                placeholder = { field.placeholder?.let { Text(it) } },
                modifier = Modifier.fillMaxWidth(),
                isError = field.required && value.isBlank()
            )
        }
    }
}

@Composable
fun SuccessCard(
    ticketNumber: String,
    downloadUrl: String?,
    downloadDocxUrl: String?,
    onCopy: () -> Unit,
    onReset: () -> Unit
) {
    val context = LocalContext.current
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(20.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface)
    ) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(24.dp),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            Icon(Icons.Default.CheckCircle, null, tint = Color(0xFF10B981), modifier = Modifier.size(64.dp))
            Spacer(modifier = Modifier.height(16.dp))
            Text("Pengajuan Berhasil!", fontWeight = FontWeight.Bold, fontSize = 20.sp, color = MaterialTheme.colorScheme.onSurface)
            Spacer(modifier = Modifier.height(8.dp))
            Text("Surat Anda sedang diproses. Simpan nomor tiket berikut:", fontSize = 13.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
            Spacer(modifier = Modifier.height(16.dp))
            Row(
                modifier = Modifier
                    .clip(RoundedCornerShape(12.dp))
                    .background(MaterialTheme.colorScheme.primary.copy(alpha = 0.1f))
                    .border(1.dp, MaterialTheme.colorScheme.primary.copy(alpha = 0.3f), RoundedCornerShape(12.dp))
                    .padding(horizontal = 20.dp, vertical = 12.dp)
                    .clickable { onCopy() },
                verticalAlignment = Alignment.CenterVertically
            ) {
                Text(ticketNumber, fontWeight = FontWeight.Bold, fontSize = 16.sp, color = MaterialTheme.colorScheme.primary)
                Spacer(modifier = Modifier.width(8.dp))
                Icon(Icons.Default.ContentCopy, null, tint = MaterialTheme.colorScheme.primary, modifier = Modifier.size(18.dp))
            }
            Spacer(modifier = Modifier.height(8.dp))
            Text("Ketuk untuk menyalin nomor tiket", fontSize = 11.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
            
            if (!downloadUrl.isNullOrBlank() || !downloadDocxUrl.isNullOrBlank()) {
                Spacer(modifier = Modifier.height(16.dp))
                Text("Unduh Dokumen Surat:", fontWeight = FontWeight.SemiBold, fontSize = 14.sp, color = MaterialTheme.colorScheme.onSurface)
                Spacer(modifier = Modifier.height(8.dp))
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    if (!downloadUrl.isNullOrBlank()) {
                        Button(
                            onClick = {
                                try {
                                    context.startActivity(Intent(Intent.ACTION_VIEW, Uri.parse(downloadUrl)))
                                } catch (e: Exception) {
                                    // Handle error
                                }
                            },
                            modifier = Modifier.weight(1f),
                            shape = RoundedCornerShape(12.dp),
                            colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.primary)
                        ) {
                            Icon(Icons.Default.Download, null, modifier = Modifier.size(18.dp))
                            Spacer(modifier = Modifier.width(6.dp))
                            Text("PDF", fontSize = 13.sp, fontWeight = FontWeight.Bold)
                        }
                    }
                    if (!downloadDocxUrl.isNullOrBlank()) {
                        Button(
                            onClick = {
                                try {
                                    context.startActivity(Intent(Intent.ACTION_VIEW, Uri.parse(downloadDocxUrl)))
                                } catch (e: Exception) {
                                    // Handle error
                                }
                            },
                            modifier = Modifier.weight(1f),
                            shape = RoundedCornerShape(12.dp),
                            colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.secondary)
                        ) {
                            Icon(Icons.Default.Download, null, modifier = Modifier.size(18.dp))
                            Spacer(modifier = Modifier.width(6.dp))
                            Text("Word", fontSize = 13.sp, fontWeight = FontWeight.Bold)
                        }
                    }
                }
            }

            Spacer(modifier = Modifier.height(24.dp))
            Text(
                "Surat akan dikirim dalam 1-3 hari kerja. Gunakan nomor tiket di atas untuk melacak status di menu Lacak Tiket.",
                fontSize = 12.sp,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
                lineHeight = 18.sp
            )
            Spacer(modifier = Modifier.height(20.dp))
            OutlinedButton(onClick = onReset, modifier = Modifier.fillMaxWidth()) {
                Text("Ajukan Surat Baru")
            }
        }
    }
}
