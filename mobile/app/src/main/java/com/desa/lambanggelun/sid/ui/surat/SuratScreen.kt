package com.desa.lambanggelun.sid.ui.surat

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.CheckCircle
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun SuratScreen(onNavigateBack: () -> Unit) {
    var nik by remember { mutableStateOf("") }
    var isNikVerified by remember { mutableStateOf(false) }
    var fullName by remember { mutableStateOf("") }
    
    // Form fields
    var phone by remember { mutableStateOf("") }
    var email by remember { mutableStateOf("") }
    var selectedLetterType by remember { mutableStateOf("") }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Surat Online") },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.Default.ArrowBack, contentDescription = "Back")
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = MaterialTheme.colorScheme.primary,
                    titleContentColor = MaterialTheme.colorScheme.onPrimary,
                    navigationIconContentColor = MaterialTheme.colorScheme.onPrimary
                )
            )
        }
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
            // Step 1: NIK Verification
            Card(
                colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                shape = RoundedCornerShape(12.dp)
            ) {
                Column(modifier = Modifier.padding(16.dp)) {
                    Text("1. Verifikasi NIK", fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.primary)
                    Spacer(modifier = Modifier.height(8.dp))
                    OutlinedTextField(
                        value = nik,
                        onValueChange = { nik = it },
                        label = { Text("Masukkan NIK (16 Digit)") },
                        modifier = Modifier.fillMaxWidth(),
                        singleLine = true,
                        trailingIcon = {
                            if (isNikVerified) Icon(Icons.Default.CheckCircle, contentDescription = null, tint = Color(0xFF10B981))
                        }
                    )
                    Spacer(modifier = Modifier.height(8.dp))
                    val coroutineScope = rememberCoroutineScope()
                    var isLoading by remember { mutableStateOf(false) }
                    
                    Button(
                        onClick = { 
                            if (nik.length >= 16) {
                                isLoading = true
                                coroutineScope.launch {
                                    try {
                                        val response = com.desa.lambanggelun.sid.data.api.ApiClient.service.checkNik(nik)
                                        if (response.success) {
                                            isNikVerified = true
                                            fullName = response.full_name ?: "Pemohon"
                                        } else {
                                            isNikVerified = false
                                            // Show error (using a simple toast or snackbar in reality, but just resetting for now)
                                        }
                                    } catch (e: Exception) {
                                        isNikVerified = false
                                    } finally {
                                        isLoading = false
                                    }
                                }
                            }
                        },
                        modifier = Modifier.fillMaxWidth(),
                        enabled = !isLoading
                    ) {
                        Text(if (isLoading) "Memeriksa..." else "Cek NIK")
                    }
                }
            }

            if (isNikVerified) {
                // Step 2: Data Pemohon
                Card(
                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                    shape = RoundedCornerShape(12.dp)
                ) {
                    Column(modifier = Modifier.padding(16.dp)) {
                        Text("2. Data Pemohon", fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.primary)
                        Spacer(modifier = Modifier.height(8.dp))
                        OutlinedTextField(value = fullName, onValueChange = {}, label = { Text("Nama Lengkap") }, readOnly = true, modifier = Modifier.fillMaxWidth())
                        Spacer(modifier = Modifier.height(8.dp))
                        OutlinedTextField(value = phone, onValueChange = { phone = it }, label = { Text("No WhatsApp") }, modifier = Modifier.fillMaxWidth())
                        Spacer(modifier = Modifier.height(8.dp))
                        OutlinedTextField(value = email, onValueChange = { email = it }, label = { Text("Email (Opsional)") }, modifier = Modifier.fillMaxWidth())
                    }
                }

                // Step 3: Detail Surat
                Card(
                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                    shape = RoundedCornerShape(12.dp)
                ) {
                    Column(modifier = Modifier.padding(16.dp)) {
                        Text("3. Pilih Jenis Surat", fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.primary)
                        Spacer(modifier = Modifier.height(8.dp))
                        OutlinedTextField(
                            value = selectedLetterType,
                            onValueChange = { selectedLetterType = it },
                            label = { Text("Jenis Surat (Contoh: SKTM)") },
                            modifier = Modifier.fillMaxWidth()
                        )
                        Spacer(modifier = Modifier.height(16.dp))
                        Button(
                            onClick = { /* Submit to API */ },
                            modifier = Modifier.fillMaxWidth()
                        ) {
                            Text("Ajukan Surat")
                        }
                    }
                }
            }
        }
    }
}
