package com.desa.lambanggelun.sid.ui.splash

import androidx.compose.animation.core.*
import androidx.compose.foundation.Image
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.alpha
import androidx.compose.ui.draw.clip
import androidx.compose.ui.draw.scale
import androidx.compose.ui.draw.shadow
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.graphicsLayer
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.platform.LocalContext
import coil3.compose.AsyncImage
import coil3.request.ImageRequest
import coil3.svg.SvgDecoder
import com.desa.lambanggelun.sid.R
import kotlinx.coroutines.delay

@Composable
fun SplashScreen(
    onSplashFinished: () -> Unit
) {
    // ─── Phase states ──────────────────────────────────────────────────
    var phase by remember { mutableIntStateOf(0) }
    // phase 0 = initial (nothing visible)
    // phase 1 = logo appears (scale + fade)
    // phase 2 = text appears (slide up + fade)
    // phase 3 = glow ring pulse

    // ─── Logo animation ────────────────────────────────────────────────
    val logoScale by animateFloatAsState(
        targetValue = when (phase) {
            0 -> 0.3f
            else -> 1f
        },
        animationSpec = spring(
            dampingRatio = Spring.DampingRatioMediumBouncy,
            stiffness = Spring.StiffnessLow
        ),
        label = "logoScale"
    )

    val logoAlpha by animateFloatAsState(
        targetValue = if (phase >= 1) 1f else 0f,
        animationSpec = tween(600),
        label = "logoAlpha"
    )

    // ─── Text animation ────────────────────────────────────────────────
    val textAlpha by animateFloatAsState(
        targetValue = if (phase >= 2) 1f else 0f,
        animationSpec = tween(500),
        label = "textAlpha"
    )

    val textOffset by animateFloatAsState(
        targetValue = if (phase >= 2) 0f else 30f,
        animationSpec = tween(500, easing = FastOutSlowInEasing),
        label = "textOffset"
    )

    // ─── Subtitle animation ────────────────────────────────────────────
    val subtitleAlpha by animateFloatAsState(
        targetValue = if (phase >= 2) 1f else 0f,
        animationSpec = tween(500, delayMillis = 200),
        label = "subtitleAlpha"
    )

    val subtitleOffset by animateFloatAsState(
        targetValue = if (phase >= 2) 0f else 20f,
        animationSpec = tween(500, delayMillis = 200, easing = FastOutSlowInEasing),
        label = "subtitleOffset"
    )

    // ─── Glow ring pulsation ───────────────────────────────────────────
    val infiniteTransition = rememberInfiniteTransition(label = "glow")
    val glowScale by infiniteTransition.animateFloat(
        initialValue = 0.85f,
        targetValue = 1.15f,
        animationSpec = infiniteRepeatable(
            animation = tween(1200, easing = FastOutSlowInEasing),
            repeatMode = RepeatMode.Reverse
        ),
        label = "glowScale"
    )
    val glowAlpha by infiniteTransition.animateFloat(
        initialValue = 0.35f,
        targetValue = 0.08f,
        animationSpec = infiniteRepeatable(
            animation = tween(1200, easing = FastOutSlowInEasing),
            repeatMode = RepeatMode.Reverse
        ),
        label = "glowAlpha"
    )

    // ─── Footer ────────────────────────────────────────────────────────
    val footerAlpha by animateFloatAsState(
        targetValue = if (phase >= 2) 0.6f else 0f,
        animationSpec = tween(600, delayMillis = 400),
        label = "footerAlpha"
    )

    // ─── Timeline ──────────────────────────────────────────────────────
    LaunchedEffect(Unit) {
        delay(150)
        phase = 1          // logo appears
        delay(600)
        phase = 2          // text + subtitle
        delay(400)
        phase = 3          // glow starts (already running via infinite)
        delay(1500)
        onSplashFinished()
    }

    // ─── UI ────────────────────────────────────────────────────────────
    Box(
        modifier = Modifier
            .fillMaxSize()
            .background(
                Brush.verticalGradient(
                    colors = listOf(
                        MaterialTheme.colorScheme.background,
                        MaterialTheme.colorScheme.primary.copy(alpha = 0.06f),
                        MaterialTheme.colorScheme.background
                    )
                )
            ),
        contentAlignment = Alignment.Center
    ) {
        Column(
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            // ── Glow ring behind logo ──────────────────────────────────
            Box(contentAlignment = Alignment.Center) {
                // Pulsating glow circle
                if (phase >= 1) {
                    Box(
                        modifier = Modifier
                            .size(160.dp)
                            .scale(glowScale)
                            .alpha(glowAlpha)
                            .clip(CircleShape)
                            .background(
                                Brush.radialGradient(
                                    colors = listOf(
                                        MaterialTheme.colorScheme.primary.copy(alpha = 0.5f),
                                        Color.Transparent
                                    )
                                )
                            )
                    )
                }

                // Logo
                val context = LocalContext.current
                AsyncImage(
                    model = ImageRequest.Builder(context)
                        .data("android.resource://${context.packageName}/${R.raw.logo_pekalongan}")
                        .decoderFactory(SvgDecoder.Factory())
                        .build(),
                    contentDescription = "Logo Kabupaten Pekalongan",
                    modifier = Modifier
                        .size(120.dp)
                        .scale(logoScale)
                        .alpha(logoAlpha)
                )
            }

            Spacer(modifier = Modifier.height(28.dp))

            // ── Title ──────────────────────────────────────────────────
            Text(
                text = "SID Mobile",
                fontSize = 30.sp,
                fontWeight = FontWeight.ExtraBold,
                color = MaterialTheme.colorScheme.primary,
                letterSpacing = 2.sp,
                modifier = Modifier
                    .alpha(textAlpha)
                    .graphicsLayer { translationY = textOffset }
            )

            Spacer(modifier = Modifier.height(6.dp))

            // ── Subtitle ───────────────────────────────────────────────
            Text(
                text = "Desa Lambanggelun",
                fontSize = 16.sp,
                fontWeight = FontWeight.Medium,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
                letterSpacing = 0.5.sp,
                modifier = Modifier
                    .alpha(subtitleAlpha)
                    .graphicsLayer { translationY = subtitleOffset }
            )
        }

        // ── Footer version ─────────────────────────────────────────────
        Box(
            modifier = Modifier
                .fillMaxSize()
                .padding(bottom = 36.dp),
            contentAlignment = Alignment.BottomCenter
        ) {
            Text(
                text = "Versi 1.0.0",
                fontSize = 12.sp,
                color = MaterialTheme.colorScheme.onSurfaceVariant.copy(alpha = footerAlpha)
            )
        }
    }
}
