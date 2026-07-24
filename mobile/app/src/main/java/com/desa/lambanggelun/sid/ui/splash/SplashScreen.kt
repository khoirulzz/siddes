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
import androidx.compose.ui.geometry.Offset
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

    // ─── Silhouette Image Animation (Scale + Fade + Slide) ────────────
    val imageScale by animateFloatAsState(
        targetValue = if (phase >= 1) 1f else 0.85f,
        animationSpec = spring(
            dampingRatio = Spring.DampingRatioMediumBouncy,
            stiffness = Spring.StiffnessLow
        ),
        label = "imageScale"
    )
    val imageAlpha by animateFloatAsState(
        targetValue = if (phase >= 1) 1f else 0f,
        animationSpec = tween(600),
        label = "imageAlpha"
    )
    val imageOffset by animateFloatAsState(
        targetValue = if (phase >= 1) 0f else 30f,
        animationSpec = tween(600, easing = FastOutSlowInEasing),
        label = "imageOffset"
    )

    // ─── Title & Subtitle Animation ───────────────────────────────────
    val textAlpha by animateFloatAsState(
        targetValue = if (phase >= 2) 1f else 0f,
        animationSpec = tween(500),
        label = "textAlpha"
    )
    val textOffset by animateFloatAsState(
        targetValue = if (phase >= 2) 0f else 20f,
        animationSpec = tween(500, easing = FastOutSlowInEasing),
        label = "textOffset"
    )

    // ─── Decorative Line Animation ─────────────────────────────────────
    val lineWidth by animateFloatAsState(
        targetValue = if (phase >= 2) 1f else 0f,
        animationSpec = tween(600, delayMillis = 200, easing = FastOutSlowInEasing),
        label = "lineWidth"
    )

    // ─── Radial Glow Pulsation ─────────────────────────────────────────
    val infiniteTransition = rememberInfiniteTransition(label = "glow")
    val glowScale by infiniteTransition.animateFloat(
        initialValue = 0.9f,
        targetValue = 1.1f,
        animationSpec = infiniteRepeatable(
            animation = tween(1800, easing = FastOutSlowInEasing),
            repeatMode = RepeatMode.Reverse
        ),
        label = "glowScale"
    )
    val glowAlpha by infiniteTransition.animateFloat(
        initialValue = 0.25f,
        targetValue = 0.08f,
        animationSpec = infiniteRepeatable(
            animation = tween(1800, easing = FastOutSlowInEasing),
            repeatMode = RepeatMode.Reverse
        ),
        label = "glowAlpha"
    )

    // ─── Light Sweep / Shimmer Glare Animation ────────────────────────
    val shimmerTranslateAnim by infiniteTransition.animateFloat(
        initialValue = -200f,
        targetValue = 800f,
        animationSpec = infiniteRepeatable(
            animation = tween(durationMillis = 2200, easing = LinearEasing, delayMillis = 500),
            repeatMode = RepeatMode.Restart
        ),
        label = "shimmerTranslate"
    )

    // ─── Footer Animation ──────────────────────────────────────────────
    val footerAlpha by animateFloatAsState(
        targetValue = if (phase >= 2) 0.6f else 0f,
        animationSpec = tween(500, delayMillis = 400),
        label = "footerAlpha"
    )

    // ─── Timeline ──────────────────────────────────────────────────────
    LaunchedEffect(Unit) {
        delay(150)
        phase = 1     // Silhouette image appears
        delay(500)
        phase = 2     // Caption text appears
        delay(2200)
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
                        MaterialTheme.colorScheme.primary.copy(alpha = 0.05f),
                        MaterialTheme.colorScheme.background
                    )
                )
            ),
        contentAlignment = Alignment.Center
    ) {
        Column(
            horizontalAlignment = Alignment.CenterHorizontally,
            verticalArrangement = Arrangement.Center,
            modifier = Modifier
                .fillMaxWidth()
                .padding(horizontal = 24.dp)
        ) {
            // ── Glow + Silhouette Image + Light Glare ──────────────────
            Box(
                contentAlignment = Alignment.Center,
                modifier = Modifier.padding(bottom = 16.dp)
            ) {
                // Background radial glow behind person
                if (phase >= 1) {
                    Box(
                        modifier = Modifier
                            .size(280.dp)
                            .scale(glowScale)
                            .alpha(glowAlpha)
                            .clip(CircleShape)
                            .background(
                                Brush.radialGradient(
                                    colors = listOf(
                                        MaterialTheme.colorScheme.primary.copy(alpha = 0.45f),
                                        Color.Transparent
                                    )
                                )
                            )
                    )
                }

                // Silhouette Illustration Image
                Image(
                    painter = painterResource(id = R.drawable.splash_siluet),
                    contentDescription = "SID Mobile Persona",
                    contentScale = ContentScale.Fit,
                    modifier = Modifier
                        .height(300.dp)
                        .scale(imageScale)
                        .alpha(imageAlpha)
                        .graphicsLayer { translationY = imageOffset }
                )

                // Light Sweep / Shimmer Glare (Silauan Cahaya)
                if (phase >= 1) {
                    Box(
                        modifier = Modifier
                            .height(300.dp)
                            .width(220.dp)
                            .scale(imageScale)
                            .alpha(imageAlpha * 0.7f)
                            .graphicsLayer { translationY = imageOffset }
                            .background(
                                Brush.linearGradient(
                                    colors = listOf(
                                        Color.Transparent,
                                        Color.White.copy(alpha = 0.05f),
                                        Color.White.copy(alpha = 0.35f),
                                        Color.White.copy(alpha = 0.05f),
                                        Color.Transparent
                                    ),
                                    start = Offset(shimmerTranslateAnim - 150f, shimmerTranslateAnim - 150f),
                                    end = Offset(shimmerTranslateAnim + 150f, shimmerTranslateAnim + 150f)
                                )
                            )
                    )
                }
            }

            Spacer(modifier = Modifier.height(12.dp))

            // ── Captions (SID Mobile & Desa Lambanggelun) ─────────────
            Column(
                horizontalAlignment = Alignment.CenterHorizontally,
                modifier = Modifier
                    .alpha(textAlpha)
                    .graphicsLayer { translationY = textOffset }
            ) {
                Text(
                    text = "SID Mobile",
                    fontSize = 32.sp,
                    fontWeight = FontWeight.Bold,
                    color = MaterialTheme.colorScheme.primary,
                    letterSpacing = 1.5.sp
                )

                Spacer(modifier = Modifier.height(4.dp))

                // Decorative Line Accent
                Box(
                    modifier = Modifier
                        .width((80 * lineWidth).dp)
                        .height(3.dp)
                        .clip(RoundedCornerShape(1.5.dp))
                        .background(
                            Brush.horizontalGradient(
                                colors = listOf(
                                    Color.Transparent,
                                    MaterialTheme.colorScheme.primary,
                                    Color.Transparent
                                )
                            )
                        )
                )

                Spacer(modifier = Modifier.height(8.dp))

                Text(
                    text = "Desa Lambanggelun",
                    fontSize = 17.sp,
                    fontWeight = FontWeight.SemiBold,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                    letterSpacing = 0.5.sp
                )

                Spacer(modifier = Modifier.height(4.dp))

                Text(
                    text = "Layanan Digital Resmi Warga Desa",
                    fontSize = 13.sp,
                    color = MaterialTheme.colorScheme.onSurfaceVariant.copy(alpha = 0.7f)
                )
            }
        }

        // ── Footer Version ─────────────────────────────────────────────
        Box(
            modifier = Modifier
                .fillMaxSize()
                .padding(bottom = 32.dp),
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


