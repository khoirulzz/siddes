package com.desa.lambanggelun.sid.ui.main

import androidx.compose.animation.animateColorAsState
import androidx.compose.foundation.Image
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.interaction.MutableInteractionSource
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Home
import androidx.compose.material.icons.filled.Info
import androidx.compose.material.icons.filled.Settings
import androidx.compose.material.icons.filled.SmartToy
import androidx.compose.material.icons.outlined.Home
import androidx.compose.material.icons.outlined.Info
import androidx.compose.material.icons.outlined.SmartToy
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.currentBackStackEntryAsState
import androidx.navigation.compose.rememberNavController
import com.desa.lambanggelun.sid.R
import com.desa.lambanggelun.sid.ui.home.HomeScreen
import com.desa.lambanggelun.sid.ui.pbb.PbbScreen
import com.desa.lambanggelun.sid.ui.pengaduan.PengaduanScreen
import com.desa.lambanggelun.sid.ui.surat.SuratScreen
import com.desa.lambanggelun.sid.ui.tracking.TrackingScreen
import com.desa.lambanggelun.sid.ui.news.NewsScreen
import com.desa.lambanggelun.sid.ui.ai.AiAssistantScreen
import com.desa.lambanggelun.sid.ui.settings.SettingsScreen

sealed class BottomNavItem(
    val route: String,
    val title: String,
    val selectedIcon: ImageVector,
    val unselectedIcon: ImageVector
) {
    object Layanan    : BottomNavItem("layanan",     "Layanan",     Icons.Filled.Home,     Icons.Outlined.Home)
    object Pengumuman : BottomNavItem("pengumuman",  "Pengumuman",  Icons.Filled.Info,     Icons.Outlined.Info)
    object BantuanAi  : BottomNavItem("bantuan_ai",  "Bantuan AI",  Icons.Filled.SmartToy, Icons.Outlined.SmartToy)
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun MainLayout(
    isDarkTheme: Boolean = true,
    onToggleTheme: (Boolean) -> Unit = {}
) {
    val navController = rememberNavController()
    val navBackStackEntry by navController.currentBackStackEntryAsState()
    val currentRoute = navBackStackEntry?.destination?.route

    val isMainScreen = currentRoute in listOf(
        BottomNavItem.Layanan.route,
        BottomNavItem.Pengumuman.route,
        BottomNavItem.BantuanAi.route
    )

    Scaffold(
        topBar = {
            if (isMainScreen) {
                Surface(
                    modifier = Modifier.fillMaxWidth(),
                    color = MaterialTheme.colorScheme.background
                ) {
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .statusBarsPadding()
                            .padding(horizontal = 16.dp, vertical = 12.dp),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Image(
                            painter = painterResource(id = R.drawable.loog_pekalongan),
                            contentDescription = "Logo Pekalongan",
                            modifier = Modifier
                                .size(40.dp)
                                .clip(CircleShape)
                        )
                        Spacer(modifier = Modifier.width(12.dp))
                        Column(modifier = Modifier.weight(1f)) {
                            Text(
                                text = "SID Mobile",
                                fontWeight = FontWeight.Bold,
                                color = MaterialTheme.colorScheme.onBackground,
                                fontSize = 18.sp
                            )
                            Text(
                                text = "Desa Lambanggelun",
                                color = MaterialTheme.colorScheme.onSurfaceVariant,
                                fontSize = 12.sp
                            )
                        }
                        IconButton(onClick = { navController.navigate("settings") }) {
                            Icon(
                                imageVector = Icons.Default.Settings,
                                contentDescription = "Pengaturan",
                                tint = MaterialTheme.colorScheme.onSurfaceVariant
                            )
                        }
                    }
                }
            }
        },
        bottomBar = {
            if (isMainScreen) {
                SidBottomNavigationBar(
                    currentRoute = currentRoute,
                    onItemClick = { item ->
                        navController.navigate(item.route) {
                            popUpTo(navController.graph.startDestinationId) { saveState = true }
                            launchSingleTop = true
                            restoreState = true
                        }
                    }
                )
            }
        },
        containerColor = MaterialTheme.colorScheme.background
    ) { innerPadding ->
        NavHost(
            navController = navController,
            startDestination = "splash",
            modifier = Modifier.padding(innerPadding)
        ) {
            composable("splash") {
                com.desa.lambanggelun.sid.ui.splash.SplashScreen(
                    onSplashFinished = {
                        navController.navigate(BottomNavItem.Layanan.route) {
                            popUpTo("splash") { inclusive = true }
                        }
                    }
                )
            }
            composable(BottomNavItem.Layanan.route) {
                HomeScreen(
                    onNavigateToSurat    = { navController.navigate("surat") },
                    onNavigateToPbb      = { navController.navigate("pbb") },
                    onNavigateToPengaduan = { navController.navigate("pengaduan") },
                    onNavigateToTracking  = { navController.navigate("tracking") }
                )
            }
            composable(BottomNavItem.Pengumuman.route) { NewsScreen() }
            composable(BottomNavItem.BantuanAi.route)  { AiAssistantScreen() }
            composable("surat")    { SuratScreen(onNavigateBack = { navController.popBackStack() }) }
            composable("pbb")      { PbbScreen(onNavigateBack = { navController.popBackStack() }) }
            composable("pengaduan"){ PengaduanScreen(onNavigateBack = { navController.popBackStack() }) }
            composable("tracking") { TrackingScreen(onNavigateBack = { navController.popBackStack() }) }
            composable("settings") {
                SettingsScreen(
                    onNavigateBack = { navController.popBackStack() },
                    isDarkTheme    = isDarkTheme,
                    onToggleTheme  = onToggleTheme
                )
            }
        }
    }
}

@Composable
fun SidBottomNavigationBar(
    currentRoute: String?,
    onItemClick: (BottomNavItem) -> Unit
) {
    val items = listOf(
        BottomNavItem.Layanan,
        BottomNavItem.Pengumuman,
        BottomNavItem.BantuanAi
    )

    Surface(
        modifier = Modifier.fillMaxWidth(),
        color = MaterialTheme.colorScheme.surface,
        shadowElevation = 16.dp
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .navigationBarsPadding()
                .padding(horizontal = 8.dp, vertical = 8.dp),
            horizontalArrangement = Arrangement.SpaceEvenly,
            verticalAlignment = Alignment.CenterVertically
        ) {
            items.forEach { item ->
                val isSelected = currentRoute == item.route
                val iconColor by animateColorAsState(
                    if (isSelected) Color.White else MaterialTheme.colorScheme.onSurfaceVariant,
                    label = "iconColor"
                )
                val textColor by animateColorAsState(
                    if (isSelected) MaterialTheme.colorScheme.primary else MaterialTheme.colorScheme.onSurfaceVariant,
                    label = "textColor"
                )
                Column(
                    modifier = Modifier
                        .clickable(
                            interactionSource = remember { MutableInteractionSource() },
                            indication = null
                        ) { onItemClick(item) }
                        .padding(horizontal = 12.dp, vertical = 4.dp),
                    horizontalAlignment = Alignment.CenterHorizontally
                ) {
                    Box(
                        modifier = Modifier
                            .size(if (isSelected) 48.dp else 40.dp)
                            .clip(RoundedCornerShape(14.dp))
                            .background(
                                if (isSelected) MaterialTheme.colorScheme.primary
                                else Color.Transparent
                            ),
                        contentAlignment = Alignment.Center
                    ) {
                        Icon(
                            imageVector = if (isSelected) item.selectedIcon else item.unselectedIcon,
                            contentDescription = item.title,
                            tint = iconColor,
                            modifier = Modifier.size(22.dp)
                        )
                    }
                    Spacer(modifier = Modifier.height(4.dp))
                    Text(
                        text = item.title,
                        fontSize = 11.sp,
                        fontWeight = if (isSelected) FontWeight.SemiBold else FontWeight.Normal,
                        color = textColor
                    )
                }
            }
        }
    }
}
