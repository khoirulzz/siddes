package com.desa.lambanggelun.sid.ui.main

import androidx.compose.foundation.Image
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Home
import androidx.compose.material.icons.filled.Info
import androidx.compose.material.icons.filled.Settings
import androidx.compose.material.icons.filled.SmartToy
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.navigation.NavHostController
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

sealed class BottomNavItem(val route: String, val title: String, val icon: ImageVector) {
    object Layanan : BottomNavItem("layanan", "Layanan", Icons.Default.Home)
    object Pengumuman : BottomNavItem("pengumuman", "Pengumuman", Icons.Default.Info)
    object BantuanAi : BottomNavItem("bantuan_ai", "Bantuan AI", Icons.Default.SmartToy)
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun MainLayout() {
    val navController = rememberNavController()
    val navBackStackEntry by navController.currentBackStackEntryAsState()
    val currentRoute = navBackStackEntry?.destination?.route

    // Define routes where bottom bar and top bar should be visible
    val isMainScreen = currentRoute in listOf(
        BottomNavItem.Layanan.route,
        BottomNavItem.Pengumuman.route,
        BottomNavItem.BantuanAi.route
    )

    Scaffold(
        topBar = {
            if (isMainScreen) {
                TopAppBar(
                    title = {
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Image(
                                painter = painterResource(id = R.drawable.loog_pekalongan),
                                contentDescription = "Logo Pekalongan",
                                modifier = Modifier.size(36.dp)
                            )
                            Spacer(modifier = Modifier.width(12.dp))
                            Text(
                                text = "SID Mobile",
                                fontWeight = FontWeight.Bold,
                                color = MaterialTheme.colorScheme.onSurface,
                                fontSize = 18.sp
                            )
                        }
                    },
                    actions = {
                        IconButton(onClick = { navController.navigate("settings") }) {
                            Icon(
                                imageVector = Icons.Default.Settings,
                                contentDescription = "Pengaturan",
                                tint = MaterialTheme.colorScheme.onSurface
                            )
                        }
                    },
                    colors = TopAppBarDefaults.topAppBarColors(
                        containerColor = MaterialTheme.colorScheme.surface,
                    )
                )
            }
        },
        bottomBar = {
            if (isMainScreen) {
                NavigationBar(
                    containerColor = MaterialTheme.colorScheme.surface,
                    tonalElevation = 8.dp
                ) {
                    val items = listOf(
                        BottomNavItem.Layanan,
                        BottomNavItem.Pengumuman,
                        BottomNavItem.BantuanAi
                    )
                    items.forEach { item ->
                        NavigationBarItem(
                            icon = { Icon(item.icon, contentDescription = item.title) },
                            label = { Text(item.title, fontWeight = FontWeight.Medium) },
                            selected = currentRoute == item.route,
                            onClick = {
                                navController.navigate(item.route) {
                                    popUpTo(navController.graph.startDestinationId) { saveState = true }
                                    launchSingleTop = true
                                    restoreState = true
                                }
                            },
                            colors = NavigationBarItemDefaults.colors(
                                selectedIconColor = MaterialTheme.colorScheme.primary,
                                unselectedIconColor = MaterialTheme.colorScheme.muted,
                                selectedTextColor = MaterialTheme.colorScheme.primary,
                                unselectedTextColor = MaterialTheme.colorScheme.muted,
                                indicatorColor = MaterialTheme.colorScheme.primary.copy(alpha = 0.15f)
                            )
                        )
                    }
                }
            }
        }
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
                    onNavigateToSurat = { navController.navigate("surat") },
                    onNavigateToPbb = { navController.navigate("pbb") },
                    onNavigateToPengaduan = { navController.navigate("pengaduan") },
                    onNavigateToTracking = { navController.navigate("tracking") }
                )
            }
            composable(BottomNavItem.Pengumuman.route) {
                NewsScreen()
            }
            composable(BottomNavItem.BantuanAi.route) {
                AiAssistantScreen()
            }
            composable("surat") {
                SuratScreen(onNavigateBack = { navController.popBackStack() })
            }
            composable("pbb") {
                PbbScreen(onNavigateBack = { navController.popBackStack() })
            }
            composable("pengaduan") {
                PengaduanScreen(onNavigateBack = { navController.popBackStack() })
            }
            composable("tracking") {
                TrackingScreen(onNavigateBack = { navController.popBackStack() })
            }
            composable("settings") {
                SettingsScreen(onNavigateBack = { navController.popBackStack() })
            }
        }
    }
}
