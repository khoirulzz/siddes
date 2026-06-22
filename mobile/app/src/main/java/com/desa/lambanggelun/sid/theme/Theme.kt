package com.desa.lambanggelun.sid.theme

import android.os.Build
import androidx.compose.foundation.isSystemInDarkTheme
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.darkColorScheme
import androidx.compose.material3.dynamicDarkColorScheme
import androidx.compose.material3.dynamicLightColorScheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.runtime.Composable
import androidx.compose.ui.platform.LocalContext

private val DarkColorScheme = darkColorScheme(
    primary = SidDarkPrimary,
    onPrimary = SidDarkSurface,
    primaryContainer = SidDarkPrimaryStrong,
    onPrimaryContainer = SidDarkInk,
    secondary = SidDarkAccent,
    onSecondary = SidDarkSurface,
    background = SidDarkBackground,
    onBackground = SidDarkInk,
    surface = SidDarkSurface,
    onSurface = SidDarkInk,
    surfaceVariant = SidDarkLine,
    onSurfaceVariant = SidDarkMuted,
    error = SidDarkDanger,
    onError = SidDarkSurface
)

private val LightColorScheme = lightColorScheme(
    primary = SidPrimary,
    onPrimary = SidSurface,
    primaryContainer = SidPrimaryStrong,
    onPrimaryContainer = SidSurface,
    secondary = SidAccent,
    onSecondary = SidSurface,
    background = SidBackground,
    onBackground = SidInk,
    surface = SidSurface,
    onSurface = SidInk,
    surfaceVariant = SidLine,
    onSurfaceVariant = SidMuted,
    error = SidDanger,
    onError = SidSurface
)

@Composable
fun SIDMobileTheme(
    darkTheme: Boolean = isSystemInDarkTheme(),
    // Dynamic color is available on Android 12+
    dynamicColor: Boolean = false, // Disabled dynamic color to strictly match web brand
    content: @Composable () -> Unit,
) {
    val colorScheme = when {
        dynamicColor && Build.VERSION.SDK_INT >= Build.VERSION_CODES.S -> {
            val context = LocalContext.current
            if (darkTheme) dynamicDarkColorScheme(context) else dynamicLightColorScheme(context)
        }
        darkTheme -> DarkColorScheme
        else -> LightColorScheme
    }

    MaterialTheme(
        colorScheme = colorScheme,
        typography = Typography,
        content = content
    )
}
