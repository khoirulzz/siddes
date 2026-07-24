package com.desa.lambanggelun.sid.ui.pbb

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
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalClipboardManager
import androidx.compose.ui.text.AnnotatedString
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.viewmodel.compose.viewModel
import com.desa.lambanggelun.sid.data.api.TaxObject
import com.desa.lambanggelun.sid.ui.surat.StepCard
import java.text.NumberFormat
import java.util.Locale

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun PbbScreen(
    onNavigateBack: () -> Unit,
    vm: PbbViewModel = viewModel()
) {
    val state by vm.state.collectAsState()
    val clipboard = LocalClipboardManager.current
    val snackbarHostState = remember { SnackbarHostState() }

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
                            Text("Bayar PBB", fontWeight = FontWeight.Bold, fontSize = 16.sp)
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
                        Spacer(modifier = Modifier.height(12.dp))
                        Text("Permohonan PBB Diterima!", fontWeight = FontWeight.Bold, fontSize = 18.sp, color = MaterialTheme.colorScheme.onSurface)
                        Spacer(modifier = Modifier.height(8.dp))
                        Text("Kode Tiket:", fontSize = 13.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                        Spacer(modifier = Modifier.height(4.dp))
                        Text(state.ticketCode, fontWeight = FontWeight.Bold, fontSize = 18.sp, color = MaterialTheme.colorScheme.primary)
                        Spacer(modifier = Modifier.height(12.dp))
                        OutlinedButton(onClick = { clipboard.setText(AnnotatedString(state.ticketCode)) }, modifier = Modifier.fillMaxWidth()) {
                            Icon(Icons.Default.ContentCopy, null, modifier = Modifier.size(16.dp))
                            Spacer(modifier = Modifier.width(6.dp))
                            Text("Salin Kode Tiket")
                        }
                        Spacer(modifier = Modifier.height(8.dp))
                        Text("Petugas akan menghubungi via WhatsApp dalam 1-3 hari kerja.", fontSize = 12.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                        Spacer(modifier = Modifier.height(16.dp))
                        Button(onClick = { vm.reset() }, modifier = Modifier.fillMaxWidth()) { Text("Ajukan PBB Baru") }
                    }
                }
                return@Column
            }

            // Step 1 – Data pemohon
            StepCard(step = "1", title = "Data Pemohon") {
                OutlinedTextField(value = state.applicantName, onValueChange = { vm.onNameChange(it) }, label = { Text("Nama Lengkap *") }, modifier = Modifier.fillMaxWidth(), singleLine = true)
                Spacer(modifier = Modifier.height(8.dp))
                OutlinedTextField(value = state.phone, onValueChange = { vm.onPhoneChange(it) }, label = { Text("No. WhatsApp *") }, modifier = Modifier.fillMaxWidth(), singleLine = true, keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Phone))
                Spacer(modifier = Modifier.height(8.dp))
                OutlinedTextField(value = state.email, onValueChange = { vm.onEmailChange(it) }, label = { Text("Email (Opsional)") }, modifier = Modifier.fillMaxWidth(), singleLine = true, keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Email))
            }

            // Step 2 – Add NOPs
            StepCard(step = "2", title = "Tambah NOP (Nomor Objek Pajak)") {
                Text("NOP tertera di SPPT PBB Anda", fontSize = 12.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                Spacer(modifier = Modifier.height(8.dp))
                Row(modifier = Modifier.fillMaxWidth(), verticalAlignment = Alignment.CenterVertically) {
                    OutlinedTextField(
                        value = state.nopInput,
                        onValueChange = { vm.onNopInputChange(it) },
                        label = { Text("Masukkan NOP") },
                        modifier = Modifier.weight(1f),
                        singleLine = true,
                        isError = state.nopError != null,
                        supportingText = { state.nopError?.let { Text(it, color = MaterialTheme.colorScheme.error) } }
                    )
                    Spacer(modifier = Modifier.width(8.dp))
                    Button(onClick = { vm.searchNop() }, enabled = state.nopInput.isNotBlank() && !state.isSearchingNop) {
                        if (state.isSearchingNop) CircularProgressIndicator(modifier = Modifier.size(18.dp), strokeWidth = 2.dp, color = Color.White)
                        else Icon(Icons.Default.Search, null)
                    }
                }

                // NOP List
                if (state.nopList.isNotEmpty()) {
                    Spacer(modifier = Modifier.height(8.dp))
                    Text("NOP yang ditambahkan:", fontWeight = FontWeight.SemiBold, fontSize = 13.sp, color = MaterialTheme.colorScheme.onSurface)
                    state.nopList.forEach { tax -> NopItem(tax = tax, onRemove = { vm.removeNop(tax.nop) }) }
                    HorizontalDivider(modifier = Modifier.padding(vertical = 8.dp))
                    val total = state.nopList.sumOf { it.amount_due }
                    Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                        Text("Total Tagihan:", fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.onSurface)
                        Text(formatRupiah(total), fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.error)
                    }
                }
            }

            // Submit
            if (state.nopList.isNotEmpty()) {
                Button(
                    onClick = { vm.submit() },
                    modifier = Modifier.fillMaxWidth().height(52.dp),
                    enabled = !state.isSubmitting,
                    shape = RoundedCornerShape(14.dp)
                ) {
                    if (state.isSubmitting) { CircularProgressIndicator(modifier = Modifier.size(20.dp), strokeWidth = 2.dp, color = Color.White); Spacer(modifier = Modifier.width(8.dp)) }
                    Text(if (state.isSubmitting) "Mengajukan..." else "Ajukan Permohonan PBB")
                }
            }
        }
    }
}

@Composable
fun NopItem(tax: TaxObject, onRemove: () -> Unit) {
    Card(modifier = Modifier.fillMaxWidth().padding(vertical = 4.dp), shape = RoundedCornerShape(10.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f))) {
        Row(modifier = Modifier.padding(12.dp), verticalAlignment = Alignment.CenterVertically) {
            Column(modifier = Modifier.weight(1f)) {
                Text(tax.nop, fontWeight = FontWeight.Bold, fontSize = 13.sp, color = MaterialTheme.colorScheme.onSurface)
                Text(tax.tax_name, fontSize = 12.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                Text(formatRupiah(tax.amount_due), fontSize = 12.sp, color = MaterialTheme.colorScheme.error, fontWeight = FontWeight.SemiBold)
            }
            IconButton(onClick = onRemove) { Icon(Icons.Default.Close, null, tint = MaterialTheme.colorScheme.error) }
        }
    }
}

private fun formatRupiah(amount: Double): String {
    return "Rp " + NumberFormat.getNumberInstance(Locale("id", "ID")).format(amount.toLong())
}
