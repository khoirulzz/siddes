package com.desa.lambanggelun.sid.ui.news

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Article
import androidx.compose.material.icons.filled.CalendarToday
import androidx.compose.material.icons.filled.Notifications
import androidx.compose.material.icons.filled.Refresh
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
import com.desa.lambanggelun.sid.data.api.ApiClient
import com.desa.lambanggelun.sid.data.api.ApiNewsItem
import com.desa.lambanggelun.sid.theme.BadgeBerita
import com.desa.lambanggelun.sid.theme.BadgePengumuman
import kotlinx.coroutines.launch

@Composable
fun NewsScreen() {
    var selectedTab by remember { mutableIntStateOf(0) }
    var newsList by remember { mutableStateOf<List<ApiNewsItem>>(emptyList()) }
    var isLoading by remember { mutableStateOf(true) }
    var errorMsg by remember { mutableStateOf<String?>(null) }
    val scope = rememberCoroutineScope()
    val context = androidx.compose.ui.platform.LocalContext.current

    fun loadNews() {
        scope.launch {
            isLoading = true; errorMsg = null
            try {
                val response = ApiClient.service.getNews(limit = 20)
                if (response.success && response.data != null) {
                    newsList = response.data.data
                } else {
                    errorMsg = response.message ?: "Gagal memuat berita"
                }
            } catch (e: Exception) {
                errorMsg = "Gagal terhubung ke server"
            } finally {
                isLoading = false
            }
        }
    }

    LaunchedEffect(Unit) { loadNews() }

    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(MaterialTheme.colorScheme.background)
    ) {
        // Header
        Column(modifier = Modifier.padding(horizontal = 20.dp, vertical = 8.dp)) {
            Text("Pengumuman Desa", fontSize = 22.sp, fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.onBackground)
            Spacer(Modifier.height(4.dp))
            Text("Informasi terbaru dari Desa Lambanggelun", fontSize = 13.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
        }

        Spacer(Modifier.height(8.dp))

        // Tab bar
        Row(modifier = Modifier.fillMaxWidth().padding(horizontal = 20.dp), horizontalArrangement = Arrangement.spacedBy(12.dp)) {
            TabButton(text = "Berita", icon = Icons.Default.Article, isSelected = selectedTab == 0, onClick = { selectedTab = 0 })
            TabButton(text = "Pengumuman", icon = Icons.Default.Notifications, isSelected = selectedTab == 1, onClick = { selectedTab = 1 })
        }

        Spacer(Modifier.height(12.dp))

        when {
            isLoading -> Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                CircularProgressIndicator()
            }
            errorMsg != null -> Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                Column(horizontalAlignment = Alignment.CenterHorizontally) {
                    Text(errorMsg!!, color = MaterialTheme.colorScheme.error, fontSize = 14.sp)
                    Spacer(Modifier.height(12.dp))
                    OutlinedButton(onClick = { loadNews() }) {
                        Icon(Icons.Default.Refresh, null); Spacer(Modifier.width(6.dp)); Text("Coba Lagi")
                    }
                }
            }
            else -> {
                // For now, all API news show in "Berita" tab; "Pengumuman" shows filtered if category field exists
                val filtered = when (selectedTab) {
                    0 -> newsList.filter { it.category?.lowercase() != "pengumuman" }
                    1 -> newsList.filter { it.category?.lowercase() == "pengumuman" }
                    else -> newsList
                }

                if (filtered.isEmpty()) {
                    Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                        Text(
                            if (selectedTab == 1) "Belum ada pengumuman terbaru" else "Belum ada berita terbaru",
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                            fontSize = 14.sp
                        )
                    }
                } else {
                    LazyColumn(
                        contentPadding = PaddingValues(horizontal = 16.dp, vertical = 4.dp),
                        verticalArrangement = Arrangement.spacedBy(10.dp),
                        modifier = Modifier.fillMaxSize()
                    ) {
                        items(filtered) { news ->
                            ApiNewsCard(news = news, isAnnouncement = selectedTab == 1) {
                                val url = if (selectedTab == 1) "https://desalambanggelun.web.id/pengumuman/${news.slug}" else "https://desalambanggelun.web.id/berita/${news.slug}"
                                context.startActivity(android.content.Intent(android.content.Intent.ACTION_VIEW, android.net.Uri.parse(url)))
                            }
                        }
                        item { Spacer(Modifier.height(8.dp)) }
                    }
                }
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
    val borderColor = MaterialTheme.colorScheme.onSurfaceVariant.copy(alpha = 0.3f)

    Surface(
        modifier = Modifier.clip(RoundedCornerShape(12.dp)).clickable { onClick() },
        color = bgColor,
        shape = RoundedCornerShape(12.dp),
        border = if (!isSelected) androidx.compose.foundation.BorderStroke(1.dp, borderColor) else null
    ) {
        Row(
            modifier = Modifier.padding(horizontal = 16.dp, vertical = 10.dp),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.spacedBy(6.dp)
        ) {
            Icon(icon, text, tint = textColor, modifier = Modifier.size(18.dp))
            Text(text, color = textColor, fontSize = 13.sp, fontWeight = FontWeight.SemiBold)
        }
    }
}

@Composable
fun ApiNewsCard(news: ApiNewsItem, isAnnouncement: Boolean, onClick: () -> Unit) {
    val baseUrl = "https://siddes.onrender.com"
    Card(
        modifier = Modifier.fillMaxWidth().clickable { onClick() },
        shape = RoundedCornerShape(14.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 0.dp)
    ) {
        Row(modifier = Modifier.fillMaxWidth().padding(12.dp)) {
            // Thumbnail
            val imageUrl = news.thumbnail?.let { if (it.startsWith("http")) it else "$baseUrl/$it" }
            AsyncImage(
                model = imageUrl,
                contentDescription = news.title,
                contentScale = ContentScale.Crop,
                modifier = Modifier.size(90.dp).clip(RoundedCornerShape(10.dp)).background(MaterialTheme.colorScheme.surfaceVariant)
            )
            Spacer(Modifier.width(12.dp))
            Column(modifier = Modifier.weight(1f)) {
                // Badge
                val badgeColor = if (isAnnouncement) BadgePengumuman else BadgeBerita
                val badgeText = if (isAnnouncement) "PENGUMUMAN" else "BERITA"
                Box(modifier = Modifier.clip(RoundedCornerShape(4.dp)).background(badgeColor).padding(horizontal = 7.dp, vertical = 2.dp)) {
                    Text(badgeText, color = Color.White, fontSize = 10.sp, fontWeight = FontWeight.Bold)
                }
                Spacer(Modifier.height(5.dp))
                Text(news.title, fontSize = 13.sp, fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.onSurface, maxLines = 2, overflow = TextOverflow.Ellipsis)
                Spacer(Modifier.height(3.dp))
                news.excerpt?.let { Text(it, fontSize = 11.sp, color = MaterialTheme.colorScheme.onSurfaceVariant, maxLines = 2, overflow = TextOverflow.Ellipsis, lineHeight = 15.sp) }
                Spacer(Modifier.height(6.dp))
                news.published_at?.let {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Icon(Icons.Default.CalendarToday, null, tint = MaterialTheme.colorScheme.primary, modifier = Modifier.size(12.dp))
                        Spacer(Modifier.width(4.dp))
                        Text(it.take(10), fontSize = 11.sp, color = MaterialTheme.colorScheme.primary, fontWeight = FontWeight.Medium)
                    }
                }
            }
        }
    }
}
