package com.desa.lambanggelun.sid.theme

import android.os.Build
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.darkColorScheme
import androidx.compose.material3.dynamicDarkColorScheme
import androidx.compose.material3.dynamicLightColorScheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.runtime.Composable
import androidx.compose.ui.platform.LocalContext

private val DarkColorScheme = darkColorScheme(
    primary = SidDarkPrimary,
    onPrimary = SidDarkInk,
    primaryContainer = SidDarkPrimaryStrong,
    onPrimaryContainer = SidDarkInk,
    secondary = SidDarkAccent,
    onSecondary = SidDarkSurface,
    background = SidDarkBackground,
    onBackground = SidDarkInk,
    surface = SidDarkSurface,
    onSurface = SidDarkInk,
    surfaceVariant = SidDarkSurfaceVariant,
    onSurfaceVariant = SidDarkMuted,
    error = SidDarkDanger,
    onError = SidDarkSurface,
    outline = SidDarkLine
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
    darkTheme: Boolean = true, // Default dark; can be overridden from MainActivity via DataStore
    dynamicColor: Boolean = false,
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
