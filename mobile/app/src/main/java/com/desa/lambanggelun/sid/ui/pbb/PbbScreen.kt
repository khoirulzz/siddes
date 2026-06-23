package com.desa.lambanggelun.sid.ui.pbb

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.Search
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import kotlinx.coroutines.launch

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun PbbScreen(onNavigateBack: () -> Unit) {
    var nop by remember { mutableStateOf("") }
    var taxName by remember { mutableStateOf("") }
    var amountDue by remember { mutableStateOf("") }
    var isNopFound by remember { mutableStateOf(false) }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Pajak Bumi Bangunan (PBB)") },
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
            Card(
                colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                shape = RoundedCornerShape(12.dp)
            ) {
                Column(modifier = Modifier.padding(16.dp)) {
                    Text("Cari Data Objek Pajak", fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.primary)
                    Spacer(modifier = Modifier.height(8.dp))
                    val coroutineScope = rememberCoroutineScope()
                    var isLoading by remember { mutableStateOf(false) }

                    OutlinedTextField(
                        value = nop,
                        onValueChange = { nop = it },
                        label = { Text("Masukkan NOP") },
                        modifier = Modifier.fillMaxWidth(),
                        singleLine = true,
                        trailingIcon = {
                            IconButton(
                                onClick = {
                                    if (nop.isNotEmpty()) {
                                        isLoading = true
                                        coroutineScope.launch {
                                            try {
                                                val response = com.desa.lambanggelun.sid.data.api.ApiClient.service.searchNop(nop)
                                                if (response.success && response.data != null) {
                                                    isNopFound = true
                                                    taxName = response.data.tax_name
                                                    amountDue = "Rp " + response.data.amount_due.toLong()
                                                } else {
                                                    isNopFound = false
                                                }
                                            } catch (e: Exception) {
                                                isNopFound = false
                                            } finally {
                                                isLoading = false
                                            }
                                        }
                                    }
                                },
                                enabled = !isLoading
                            ) {
                                Icon(Icons.Default.Search, contentDescription = "Cari")
                            }
                        }
                    )
                }
            }

            if (isLoading) {
                LinearProgressIndicator(modifier = Modifier.fillMaxWidth())
            }

            if (isNopFound) {
                Card(
                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                    shape = RoundedCornerShape(12.dp)
                ) {
                    Column(modifier = Modifier.padding(16.dp)) {
                        Text("Detail PBB Anda", fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.primary)
                        Spacer(modifier = Modifier.height(16.dp))
                        Text("Nama Wajib Pajak: $taxName", style = MaterialTheme.typography.bodyLarge)
                        Spacer(modifier = Modifier.height(4.dp))
                        Text("Tagihan: $amountDue", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.error)
                        
                        Spacer(modifier = Modifier.height(16.dp))
                        Button(
                            onClick = { /* Submit API */ },
                            modifier = Modifier.fillMaxWidth()
                        ) {
                            Text("Ajukan Permohonan Bayar Kolektif")
                        }
                    }
                }
            }
        }
    }
}
