package com.desa.lambanggelun.sid.ui.news

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.CalendarToday
import androidx.compose.material.icons.filled.Article
import androidx.compose.material.icons.filled.Notifications
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import coil3.compose.AsyncImage
import com.desa.lambanggelun.sid.theme.BadgeBerita
import com.desa.lambanggelun.sid.theme.BadgePengumuman

data class NewsItem(
    val title: String,
    val description: String,
    val date: String,
    val imageUrl: String,
    val category: String // "BERITA" or "PENGUMUMAN"
)

private val sampleNews = listOf(
    NewsItem(
        title = "Musyawarah Desa Bahas RKPDesa Tahun 2025",
        description = "Pemerintah Desa Lambanggelun melaksanakan musyawarah desa...",
        date = "23 Juni 2026",
        imageUrl = "https://images.unsplash.com/photo-1577962917302-cd874c4e31d2?w=400",
        category = "BERITA"
    ),
    NewsItem(
        title = "Pembangunan Jalan Desa Tahap 2 Dimulai",
        description = "Pemerintah desa memulai pembangunan jalan desa...",
        date = "20 Juni 2026",
        imageUrl = "https://images.unsplash.com/photo-1590496793929-36417d3117de?w=400",
        category = "BERITA"
    ),
    NewsItem(
        title = "Pelatihan Digital Marketing untuk UMKM Desa",
        description = "Dalam upaya meningkatkan kapasitas pelaku UMKM...",
        date = "18 Juni 2026",
        imageUrl = "https://images.unsplash.com/photo-1552664730-d307ca884978?w=400",
        category = "BERITA"
    ),
    NewsItem(
        title = "Libur Pelayanan Hari Raya Idul Adha 2026",
        description = "Sehubungan dengan hari raya Idul Adha, pelayanan kantor desa...",
        date = "15 Juni 2026",
        imageUrl = "https://images.unsplash.com/photo-1564769625905-50e93615e769?w=400",
        category = "PENGUMUMAN"
    )
)

@Composable
fun NewsScreen() {
    var selectedTab by remember { mutableIntStateOf(0) }

    val filteredNews = if (selectedTab == 0) {
        sampleNews.filter { it.category == "BERITA" }
    } else {
        sampleNews.filter { it.category == "PENGUMUMAN" }
    }

    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(MaterialTheme.colorScheme.background)
    ) {
        // Header
        Column(
            modifier = Modifier.padding(horizontal = 20.dp, vertical = 8.dp)
        ) {
            Text(
                text = "Pengumuman Desa",
                fontSize = 22.sp,
                fontWeight = FontWeight.Bold,
                color = MaterialTheme.colorScheme.onBackground
            )
            Spacer(modifier = Modifier.height(4.dp))
            Text(
                text = "Informasi terbaru dari Desa Lambanggelun",
                fontSize = 13.sp,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
        }

        Spacer(modifier = Modifier.height(12.dp))

        // Tab Bar
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(horizontal = 20.dp),
            horizontalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            // Berita Tab
            TabButton(
                text = "Berita",
                icon = Icons.Default.Article,
                isSelected = selectedTab == 0,
                onClick = { selectedTab = 0 }
            )
            // Pengumuman Tab
            TabButton(
                text = "Pengumuman",
                icon = Icons.Default.Notifications,
                isSelected = selectedTab == 1,
                onClick = { selectedTab = 1 }
            )
        }

        Spacer(modifier = Modifier.height(16.dp))

        // News List
        LazyColumn(
            contentPadding = PaddingValues(horizontal = 16.dp, vertical = 4.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp),
            modifier = Modifier.fillMaxSize()
        ) {
            items(filteredNews) { news ->
                NewsCard(news = news)
            }
            item {
                Spacer(modifier = Modifier.height(8.dp))
            }
        }
    }
}

@Composable
fun TabButton(
    text: String,
    icon: androidx.compose.ui.graphics.vector.ImageVector,
    isSelected: Boolean,
    onClick: () -> Unit
) {
    val bgColor = if (isSelected) MaterialTheme.colorScheme.primary else Color.Transparent
    val textColor = if (isSelected) Color.White else MaterialTheme.colorScheme.onSurfaceVariant
    val borderColor = if (isSelected) Color.Transparent else MaterialTheme.colorScheme.onSurfaceVariant.copy(alpha = 0.3f)

    Surface(
        modifier = Modifier
            .clip(RoundedCornerShape(12.dp))
            .clickable { onClick() },
        color = bgColor,
        shape = RoundedCornerShape(12.dp),
        border = if (!isSelected) androidx.compose.foundation.BorderStroke(1.dp, borderColor) else null
    ) {
        Row(
            modifier = Modifier.padding(horizontal = 16.dp, vertical = 10.dp),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.spacedBy(6.dp)
        ) {
            Icon(
                imageVector = icon,
                contentDescription = text,
                tint = textColor,
                modifier = Modifier.size(18.dp)
            )
            Text(
                text = text,
                color = textColor,
                fontSize = 13.sp,
                fontWeight = FontWeight.SemiBold
            )
        }
    }
}

@Composable
fun NewsCard(news: NewsItem) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(16.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 0.dp)
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(12.dp)
        ) {
            // Thumbnail image
            AsyncImage(
                model = news.imageUrl,
                contentDescription = news.title,
                contentScale = ContentScale.Crop,
                modifier = Modifier
                    .size(100.dp)
                    .clip(RoundedCornerShape(12.dp))
                    .background(MaterialTheme.colorScheme.surfaceVariant)
            )
            Spacer(modifier = Modifier.width(12.dp))
            // Content
            Column(
                modifier = Modifier.weight(1f)
            ) {
                // Badge
                val badgeColor = if (news.category == "BERITA") BadgeBerita else BadgePengumuman
                Box(
                    modifier = Modifier
                        .clip(RoundedCornerShape(4.dp))
                        .background(badgeColor)
                        .padding(horizontal = 8.dp, vertical = 2.dp)
                ) {
                    Text(
                        text = news.category,
                        color = Color.White,
                        fontSize = 10.sp,
                        fontWeight = FontWeight.Bold
                    )
                }
                Spacer(modifier = Modifier.height(6.dp))
                // Title
                Text(
                    text = news.title,
                    fontSize = 14.sp,
                    fontWeight = FontWeight.Bold,
                    color = MaterialTheme.colorScheme.onSurface,
                    maxLines = 2,
                    overflow = TextOverflow.Ellipsis
                )
                Spacer(modifier = Modifier.height(4.dp))
                // Description
                Text(
                    text = news.description,
                    fontSize = 12.sp,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                    maxLines = 2,
                    overflow = TextOverflow.Ellipsis,
                    lineHeight = 16.sp
                )
                Spacer(modifier = Modifier.height(8.dp))
                // Date
                Row(
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Icon(
                        imageVector = Icons.Default.CalendarToday,
                        contentDescription = "Tanggal",
                        tint = MaterialTheme.colorScheme.primary,
                        modifier = Modifier.size(14.dp)
                    )
                    Spacer(modifier = Modifier.width(4.dp))
                    Text(
                        text = news.date,
                        fontSize = 11.sp,
                        color = MaterialTheme.colorScheme.primary,
                        fontWeight = FontWeight.Medium
                    )
                }
            }
        }
    }
}
