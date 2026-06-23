package com.desa.lambanggelun.sid.ui.home

import androidx.compose.foundation.Image
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.desa.lambanggelun.sid.R
import com.desa.lambanggelun.sid.theme.SidAccent
import com.desa.lambanggelun.sid.theme.SidDanger
import com.desa.lambanggelun.sid.theme.SidDarkPrimary
import com.desa.lambanggelun.sid.theme.SidWarning

@Composable
fun HomeScreen(
    onNavigateToSurat: () -> Unit,
    onNavigateToPbb: () -> Unit,
    onNavigateToPengaduan: () -> Unit,
    onNavigateToTracking: () -> Unit
) {
    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(MaterialTheme.colorScheme.background)
            .verticalScroll(rememberScrollState())
    ) {
        // Hero Banner with Image
        Box(
            modifier = Modifier
                .fillMaxWidth()
                .padding(horizontal = 16.dp)
                .height(220.dp)
                .clip(RoundedCornerShape(20.dp))
        ) {
            Image(
                painter = painterResource(id = R.drawable.image_dashboard),
                contentDescription = "Dashboard Banner",
                contentScale = ContentScale.Crop,
                modifier = Modifier.fillMaxSize()
            )
            // Gradient overlay from bottom
            Box(
                modifier = Modifier
                    .fillMaxSize()
                    .background(
                        Brush.verticalGradient(
                            colors = listOf(
                                Color.Transparent,
                                Color.Black.copy(alpha = 0.3f),
                                Color.Black.copy(alpha = 0.7f)
                            ),
                            startY = 0f,
                            endY = Float.POSITIVE_INFINITY
                        )
                    )
            )
            // Text overlay
            Column(
                modifier = Modifier
                    .align(Alignment.BottomStart)
                    .padding(20.dp)
            ) {
                Text(
                    text = "Selamat Datang,",
                    color = Color.White.copy(alpha = 0.85f),
                    fontSize = 14.sp,
                    fontWeight = FontWeight.Normal
                )
                Spacer(modifier = Modifier.height(2.dp))
                Text(
                    text = "Warga Desa Lambanggelun",
                    color = Color.White,
                    fontSize = 20.sp,
                    fontWeight = FontWeight.Bold
                )
                Spacer(modifier = Modifier.height(4.dp))
                Text(
                    text = "Akses layanan desa lebih mudah dalam genggaman",
                    color = Color.White.copy(alpha = 0.75f),
                    fontSize = 12.sp,
                    fontWeight = FontWeight.Normal
                )
            }
        }

        Spacer(modifier = Modifier.height(24.dp))

        // Section Title
        Column(
            modifier = Modifier.padding(horizontal = 20.dp)
        ) {
            Text(
                text = "Layanan Digital Desa",
                fontSize = 18.sp,
                fontWeight = FontWeight.Bold,
                color = MaterialTheme.colorScheme.onBackground
            )
            Spacer(modifier = Modifier.height(4.dp))
            Text(
                text = "Pilih layanan yang Anda butuhkan",
                fontSize = 13.sp,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
        }

        Spacer(modifier = Modifier.height(16.dp))

        // Service Cards – 2x2 Grid
        Column(
            modifier = Modifier.padding(horizontal = 16.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(12.dp)
            ) {
                ServiceCard(
                    modifier = Modifier.weight(1f),
                    title = "Surat Online",
                    description = "Ajukan surat keterangan secara online",
                    icon = Icons.Default.Description,
                    color = SidDarkPrimary,
                    onClick = onNavigateToSurat
                )
                ServiceCard(
                    modifier = Modifier.weight(1f),
                    title = "Bayar PBB",
                    description = "Cek & bayar Pajak Bumi dan Bangunan",
                    icon = Icons.Default.Home,
                    color = SidAccent,
                    onClick = onNavigateToPbb
                )
            }
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(12.dp)
            ) {
                ServiceCard(
                    modifier = Modifier.weight(1f),
                    title = "Pengaduan",
                    description = "Sampaikan keluhan atau laporan kepada desa",
                    icon = Icons.Default.Campaign,
                    color = SidDanger,
                    onClick = onNavigateToPengaduan
                )
                ServiceCard(
                    modifier = Modifier.weight(1f),
                    title = "Lacak Tiket",
                    description = "Lihat status pengajuan tiket Anda",
                    icon = Icons.Default.TrackChanges,
                    color = SidWarning,
                    onClick = onNavigateToTracking
                )
            }
        }

        Spacer(modifier = Modifier.height(24.dp))
    }
}

@Composable
fun ServiceCard(
    modifier: Modifier = Modifier,
    title: String,
    description: String,
    icon: ImageVector,
    color: Color,
    onClick: () -> Unit
) {
    Card(
        modifier = modifier
            .clickable { onClick() },
        shape = RoundedCornerShape(16.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 0.dp)
    ) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(16.dp)
        ) {
            // Icon in circle
            Box(
                modifier = Modifier
                    .size(48.dp)
                    .clip(CircleShape)
                    .background(color.copy(alpha = 0.15f)),
                contentAlignment = Alignment.Center
            ) {
                Icon(
                    imageVector = icon,
                    contentDescription = title,
                    tint = color,
                    modifier = Modifier.size(24.dp)
                )
            }
            Spacer(modifier = Modifier.height(12.dp))
            Text(
                text = title,
                fontSize = 14.sp,
                fontWeight = FontWeight.Bold,
                color = MaterialTheme.colorScheme.onSurface
            )
            Spacer(modifier = Modifier.height(4.dp))
            Text(
                text = description,
                fontSize = 11.sp,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
                lineHeight = 15.sp
            )
        }
    }
}
