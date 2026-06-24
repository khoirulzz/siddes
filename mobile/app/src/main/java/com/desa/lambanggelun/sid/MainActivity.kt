package com.desa.lambanggelun.sid

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.runtime.*
import androidx.compose.ui.Modifier
import com.desa.lambanggelun.sid.data.ThemePreferences
import com.desa.lambanggelun.sid.theme.SIDMobileTheme
import com.desa.lambanggelun.sid.ui.main.MainLayout
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch

class MainActivity : ComponentActivity() {

    private lateinit var themePreferences: ThemePreferences

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        themePreferences = ThemePreferences(applicationContext)

        setContent {
            // Observe dark theme preference from DataStore
            val isDarkTheme by themePreferences.isDarkTheme.collectAsState(initial = true)

            SIDMobileTheme(darkTheme = isDarkTheme) {
                Surface(
                    modifier = Modifier.fillMaxSize(),
                    color = MaterialTheme.colorScheme.background
                ) {
                    MainLayout(
                        isDarkTheme = isDarkTheme,
                        onToggleTheme = { newValue ->
                            CoroutineScope(Dispatchers.IO).launch {
                                themePreferences.setDarkTheme(newValue)
                            }
                        }
                    )
                }
            }
        }
    }
}
