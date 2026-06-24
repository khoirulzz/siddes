package com.desa.lambanggelun.sid.ui.pengaduan

import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.background
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
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalClipboardManager
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.AnnotatedString
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.viewmodel.compose.viewModel
import com.desa.lambanggelun.sid.ui.surat.StepCard

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun PengaduanScreen(
    onNavigateBack: () -> Unit,
    vm: PengaduanViewModel = viewModel()
) {
    val state by vm.state.collectAsState()
    val context = LocalContext.current
    val clipboard = LocalClipboardManager.current
    val snackbarHostState = remember { SnackbarHostState() }

    val fileLauncher = rememberLauncherForActivityResult(ActivityResultContracts.GetContent()) { uri ->
        val fileName = uri?.lastPathSegment ?: "bukti"
        vm.onEvidenceSelected(uri, fileName)
    }

    LaunchedEffect(state.errorMessage) {
        state.errorMessage?.let { snackbarHostState.showSnackbar(it); vm.clearError() }
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
                            Text("Layanan Pengaduan", fontWeight = FontWeight.Bold, fontSize = 16.sp)
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
        },
        snackbarHost = { SnackbarHost(snackbarHostState) }
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
            if (state.submitSuccess) {
                Card(modifier = Modifier.fillMaxWidth(), shape = RoundedCornerShape(20.dp),
                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface)) {
                    Column(modifier = Modifier.padding(24.dp), horizontalAlignment = Alignment.CenterHorizontally) {
                        Icon(Icons.Default.CheckCircle, null, tint = Color(0xFF10B981), modifier = Modifier.size(64.dp))
                        Spacer(Modifier.height(12.dp))
                        Text("Laporan Terkirim!", fontWeight = FontWeight.Bold, fontSize = 18.sp, color = MaterialTheme.colorScheme.onSurface)
                        Spacer(Modifier.height(8.dp))
                        Text("Kode Tiket Pengaduan:", fontSize = 13.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                        Spacer(Modifier.height(4.dp))
                        Text(state.ticketCode, fontWeight = FontWeight.Bold, fontSize = 18.sp, color = MaterialTheme.colorScheme.primary)
                        Spacer(Modifier.height(12.dp))
                        OutlinedButton(onClick = { clipboard.setText(AnnotatedString(state.ticketCode)) }, modifier = Modifier.fillMaxWidth()) {
                            Icon(Icons.Default.ContentCopy, null, modifier = Modifier.size(16.dp))
                            Spacer(Modifier.width(6.dp))
                            Text("Salin Kode Tiket")
                        }
                        Spacer(Modifier.height(8.dp))
                        Text("Gunakan kode tiket untuk melacak status laporan Anda di menu Lacak Tiket.", fontSize = 12.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                        Spacer(Modifier.height(16.dp))
                        Button(onClick = { vm.reset() }, modifier = Modifier.fillMaxWidth()) { Text("Buat Laporan Baru") }
                    }
                }
                return@Column
            }

            // Step 1 – Identitas
            StepCard(step = "1", title = "Identitas Pelapor") {
                OutlinedTextField(value = state.nik, onValueChange = { vm.onNikChange(it) }, label = { Text("NIK *") }, modifier = Modifier.fillMaxWidth(), singleLine = true, keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number))
                Spacer(Modifier.height(8.dp))
                OutlinedTextField(value = state.reporterName, onValueChange = { vm.onReporterNameChange(it) }, label = { Text("Nama Lengkap *") }, modifier = Modifier.fillMaxWidth(), singleLine = true)
                Spacer(Modifier.height(8.dp))
                OutlinedTextField(value = state.phone, onValueChange = { vm.onPhoneChange(it) }, label = { Text("No. WhatsApp *") }, modifier = Modifier.fillMaxWidth(), singleLine = true, keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Phone))
                Spacer(Modifier.height(8.dp))
                OutlinedTextField(value = state.email, onValueChange = { vm.onEmailChange(it) }, label = { Text("Email (Opsional)") }, modifier = Modifier.fillMaxWidth(), singleLine = true, keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Email))
            }

            // Step 2 – Detail
            StepCard(step = "2", title = "Detail Pengaduan") {
                OutlinedTextField(value = state.subject, onValueChange = { vm.onSubjectChange(it) }, label = { Text("Judul Pengaduan *") }, modifier = Modifier.fillMaxWidth(), singleLine = true)
                Spacer(Modifier.height(8.dp))

                // Category dropdown
                var catExpanded by remember { mutableStateOf(false) }
                ExposedDropdownMenuBox(expanded = catExpanded, onExpandedChange = { catExpanded = !catExpanded }) {
                    OutlinedTextField(
                        value = state.category, onValueChange = {}, readOnly = true,
                        label = { Text("Kategori *") },
                        trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(catExpanded) },
                        modifier = Modifier.fillMaxWidth().menuAnchor()
                    )
                    ExposedDropdownMenu(expanded = catExpanded, onDismissRequest = { catExpanded = false }) {
                        COMPLAINT_CATEGORIES.forEach {
                            DropdownMenuItem(text = { Text(it) }, onClick = { vm.onCategoryChange(it); catExpanded = false })
                        }
                    }
                }
                Spacer(Modifier.height(8.dp))
                OutlinedTextField(value = state.location, onValueChange = { vm.onLocationChange(it) }, label = { Text("Lokasi Kejadian (Opsional)") }, modifier = Modifier.fillMaxWidth(), singleLine = true)
                Spacer(Modifier.height(8.dp))
                OutlinedTextField(value = state.description, onValueChange = { vm.onDescriptionChange(it) }, label = { Text("Deskripsi Lengkap *") }, modifier = Modifier.fillMaxWidth().height(120.dp), maxLines = 6)
                Spacer(Modifier.height(12.dp))

                // File upload
                OutlinedButton(onClick = { fileLauncher.launch("image/*") }, modifier = Modifier.fillMaxWidth()) {
                    Icon(if (state.evidenceUri != null) Icons.Default.CheckCircle else Icons.Default.UploadFile, null)
                    Spacer(Modifier.width(8.dp))
                    Text(state.evidenceFileName ?: "Upload Foto Bukti (Opsional)")
                }
            }

            // Submit
            Button(
                onClick = { vm.submit(context.contentResolver) },
                modifier = Modifier.fillMaxWidth().height(52.dp),
                enabled = !state.isSubmitting,
                shape = RoundedCornerShape(14.dp),
                colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.error)
            ) {
                if (state.isSubmitting) { CircularProgressIndicator(modifier = Modifier.size(20.dp), strokeWidth = 2.dp, color = Color.White); Spacer(Modifier.width(8.dp)) }
                Text(if (state.isSubmitting) "Mengirim..." else "Kirim Laporan")
            }
        }
    }
}
