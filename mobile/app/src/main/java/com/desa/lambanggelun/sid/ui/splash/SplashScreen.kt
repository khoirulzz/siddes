package com.desa.lambanggelun.sid.ui.splash

import androidx.compose.animation.core.*
import androidx.compose.foundation.Image
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.alpha
import androidx.compose.ui.draw.clip
import androidx.compose.ui.draw.scale
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.graphicsLayer
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.desa.lambanggelun.sid.R
import kotlinx.coroutines.delay

@Composable
fun SplashScreen(
    onSplashFinished: () -> Unit
) {
    // ─── Phase states ──────────────────────────────────────────────────
    var phase by remember { mutableIntStateOf(0) }

    // ─── Logo: gentle scale-in ─────────────────────────────────────────
    val logoScale by animateFloatAsState(
        targetValue = if (phase >= 1) 1f else 0.6f,
        animationSpec = tween(700, easing = FastOutSlowInEasing),
        label = "logoScale"
    )
    val logoAlpha by animateFloatAsState(
        targetValue = if (phase >= 1) 1f else 0f,
        animationSpec = tween(500),
        label = "logoAlpha"
    )

    // ─── Title: slide up ───────────────────────────────────────────────
    val titleAlpha by animateFloatAsState(
        targetValue = if (phase >= 2) 1f else 0f,
        animationSpec = tween(450),
        label = "titleAlpha"
    )
    val titleOffset by animateFloatAsState(
        targetValue = if (phase >= 2) 0f else 24f,
        animationSpec = tween(450, easing = FastOutSlowInEasing),
        label = "titleOffset"
    )

    // ─── Subtitle: staggered slide up ──────────────────────────────────
    val subtitleAlpha by animateFloatAsState(
        targetValue = if (phase >= 2) 1f else 0f,
        animationSpec = tween(450, delayMillis = 150),
        label = "subtitleAlpha"
    )
    val subtitleOffset by animateFloatAsState(
        targetValue = if (phase >= 2) 0f else 16f,
        animationSpec = tween(450, delayMillis = 150, easing = FastOutSlowInEasing),
        label = "subtitleOffset"
    )

    // ─── Decorative line: width expand ─────────────────────────────────
    val lineWidth by animateFloatAsState(
        targetValue = if (phase >= 2) 1f else 0f,
        animationSpec = tween(600, delayMillis = 300, easing = FastOutSlowInEasing),
        label = "lineWidth"
    )

    // ─── Subtle glow behind logo ───────────────────────────────────────
    val infiniteTransition = rememberInfiniteTransition(label = "glow")
    val glowAlpha by infiniteTransition.animateFloat(
        initialValue = 0.12f,
        targetValue = 0.04f,
        animationSpec = infiniteRepeatable(
            animation = tween(2000, easing = FastOutSlowInEasing),
            repeatMode = RepeatMode.Reverse
        ),
        label = "glowAlpha"
    )

    // ─── Footer ────────────────────────────────────────────────────────
    val footerAlpha by animateFloatAsState(
        targetValue = if (phase >= 2) 0.5f else 0f,
        animationSpec = tween(500, delayMillis = 500),
        label = "footerAlpha"
    )

    // ─── Timeline ──────────────────────────────────────────────────────
    LaunchedEffect(Unit) {
        delay(200)
        phase = 1     // logo fades in
        delay(600)
        phase = 2     // text appears
        delay(1800)
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
                        MaterialTheme.colorScheme.primary.copy(alpha = 0.03f),
                        MaterialTheme.colorScheme.background
                    )
                )
            ),
        contentAlignment = Alignment.Center
    ) {
        Column(
            horizontalAlignment = Alignment.CenterHorizontally,
            modifier = Modifier.padding(horizontal = 32.dp)
        ) {
            // ── Glow + Logo ────────────────────────────────────────────
            Box(contentAlignment = Alignment.Center) {
                // Soft glow circle
                if (phase >= 1) {
                    Box(
                        modifier = Modifier
                            .size(180.dp)
                            .alpha(glowAlpha)
                            .clip(CircleShape)
                            .background(
                                Brush.radialGradient(
                                    colors = listOf(
                                        MaterialTheme.colorScheme.primary.copy(alpha = 0.4f),
                                        Color.Transparent
                                    )
                                )
                            )
                    )
                }

                // Logo — uses the new PNG with ContentScale.Fit (no stretch)
                Image(
                    painter = painterResource(id = R.drawable.logo_sid),
                    contentDescription = "Logo Kabupaten Pekalongan",
                    contentScale = ContentScale.Fit,
                    modifier = Modifier
                        .size(130.dp)
                        .scale(logoScale)
                        .alpha(logoAlpha)
                )
            }

            Spacer(modifier = Modifier.height(28.dp))

            // ── Title ──────────────────────────────────────────────────
            Text(
                text = "SID Mobile",
                fontSize = 28.sp,
                fontWeight = FontWeight.Bold,
                color = MaterialTheme.colorScheme.primary,
                letterSpacing = 1.5.sp,
                modifier = Modifier
                    .alpha(titleAlpha)
                    .graphicsLayer { translationY = titleOffset }
            )

            Spacer(modifier = Modifier.height(6.dp))

            // ── Decorative line ────────────────────────────────────────
            Box(
                modifier = Modifier
                    .width((60 * lineWidth).dp)
                    .height(2.dp)
                    .clip(RoundedCornerShape(1.dp))
                    .background(
                        Brush.horizontalGradient(
                            colors = listOf(
                                Color.Transparent,
                                MaterialTheme.colorScheme.primary.copy(alpha = 0.5f),
                                Color.Transparent
                            )
                        )
                    )
            )

            Spacer(modifier = Modifier.height(10.dp))

            // ── Subtitle ───────────────────────────────────────────────
            Text(
                text = "Desa Lambanggelun",
                fontSize = 15.sp,
                fontWeight = FontWeight.Normal,
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
