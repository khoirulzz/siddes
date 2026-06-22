package com.desa.lambanggelun.sid

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.rememberNavController
import com.desa.lambanggelun.sid.theme.SIDMobileTheme
import com.desa.lambanggelun.sid.ui.home.HomeScreen
import com.desa.lambanggelun.sid.ui.splash.SplashScreen

class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContent {
            SIDMobileTheme {
                Surface(
                    modifier = Modifier.fillMaxSize(),
                    color = MaterialTheme.colorScheme.background
                ) {
                    SIDAppNavigation()
                }
            }
        }
    }
}

@Composable
fun SIDAppNavigation() {
    val navController = rememberNavController()

    NavHost(navController = navController, startDestination = "splash") {
        composable("splash") {
            SplashScreen(
                onSplashFinished = {
                    navController.navigate("home") {
                        popUpTo("splash") { inclusive = true }
                    }
                }
            )
        }
        composable("home") {
            HomeScreen(
                onNavigateToSurat = { navController.navigate("surat") },
                onNavigateToPbb = { navController.navigate("pbb") },
                onNavigateToPengaduan = { navController.navigate("pengaduan") }
            )
        }
        composable("surat") {
            com.desa.lambanggelun.sid.ui.surat.SuratScreen(
                onNavigateBack = { navController.popBackStack() }
            )
        }
        composable("pbb") {
            com.desa.lambanggelun.sid.ui.pbb.PbbScreen(
                onNavigateBack = { navController.popBackStack() }
            )
        }
        composable("pengaduan") {
            com.desa.lambanggelun.sid.ui.pengaduan.PengaduanScreen(
                onNavigateBack = { navController.popBackStack() }
            )
        }
    }
}
