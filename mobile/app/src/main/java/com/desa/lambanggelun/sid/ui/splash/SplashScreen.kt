package com.desa.lambanggelun.sid.ui.splash

import androidx.compose.animation.core.FastOutSlowInEasing
import androidx.compose.animation.core.Spring
import androidx.compose.animation.core.animateFloatAsState
import androidx.compose.animation.core.spring
import androidx.compose.animation.core.tween
import androidx.compose.foundation.Image
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxHeight
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.offset
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableIntStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberUpdatedState
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.alpha
import androidx.compose.ui.draw.clip
import androidx.compose.ui.geometry.Offset
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.graphicsLayer
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.desa.lambanggelun.sid.R
import kotlinx.coroutines.delay

private val SplashNavy = Color(0xFF071426)
private val SplashGold = Color(0xFFE7BC72)
private val SplashIvory = Color(0xFFF8F3E8)
private val SplashMuted = Color(0xFFB8C2CF)

@Composable
fun SplashScreen(
    onSplashFinished: () -> Unit
) {
    var phase by remember { mutableIntStateOf(0) }
    val latestOnSplashFinished by rememberUpdatedState(onSplashFinished)

    val imageScale by animateFloatAsState(
        targetValue = if (phase >= 1) 1f else 1.08f,
        animationSpec = tween(
            durationMillis = 1500,
            easing = FastOutSlowInEasing
        ),
        label = "heroImageScale"
    )
    val imageAlpha by animateFloatAsState(
        targetValue = if (phase >= 1) 1f else 0f,
        animationSpec = tween(durationMillis = 650),
        label = "heroImageAlpha"
    )
    val imageOffset by animateFloatAsState(
        targetValue = if (phase >= 1) 0f else 24f,
        animationSpec = tween(
            durationMillis = 1200,
            easing = FastOutSlowInEasing
        ),
        label = "heroImageOffset"
    )

    val identityAlpha by animateFloatAsState(
        targetValue = if (phase >= 2) 1f else 0f,
        animationSpec = tween(durationMillis = 500),
        label = "identityAlpha"
    )
    val identityOffset by animateFloatAsState(
        targetValue = if (phase >= 2) 0f else -14f,
        animationSpec = spring(
            dampingRatio = Spring.DampingRatioNoBouncy,
            stiffness = Spring.StiffnessLow
        ),
        label = "identityOffset"
    )

    val copyAlpha by animateFloatAsState(
        targetValue = if (phase >= 3) 1f else 0f,
        animationSpec = tween(durationMillis = 650),
        label = "copyAlpha"
    )
    val copyOffset by animateFloatAsState(
        targetValue = if (phase >= 3) 0f else 28f,
        animationSpec = spring(
            dampingRatio = Spring.DampingRatioNoBouncy,
            stiffness = Spring.StiffnessLow
        ),
        label = "copyOffset"
    )
    val accentWidth by animateFloatAsState(
        targetValue = if (phase >= 3) 1f else 0f,
        animationSpec = tween(
            durationMillis = 700,
            delayMillis = 140,
            easing = FastOutSlowInEasing
        ),
        label = "accentWidth"
    )
    val progress by animateFloatAsState(
        targetValue = if (phase >= 3) 1f else 0f,
        animationSpec = tween(
            durationMillis = 1350,
            delayMillis = 260,
            easing = FastOutSlowInEasing
        ),
        label = "splashProgress"
    )

    val screenAlpha by animateFloatAsState(
        targetValue = if (phase >= 4) 0f else 1f,
        animationSpec = tween(durationMillis = 320),
        label = "screenExitAlpha"
    )
    val screenScale by animateFloatAsState(
        targetValue = if (phase >= 4) 1.015f else 1f,
        animationSpec = tween(
            durationMillis = 320,
            easing = FastOutSlowInEasing
        ),
        label = "screenExitScale"
    )

    LaunchedEffect(Unit) {
        delay(80)
        phase = 1
        delay(180)
        phase = 2
        delay(260)
        phase = 3
        delay(1740)
        phase = 4
        delay(330)
        latestOnSplashFinished()
    }

    Box(
        modifier = Modifier
            .fillMaxSize()
            .background(SplashNavy)
            .graphicsLayer {
                alpha = screenAlpha
                scaleX = screenScale
                scaleY = screenScale
            }
    ) {
        Image(
            painter = painterResource(id = R.drawable.splash_siluet),
            contentDescription = null,
            contentScale = ContentScale.Crop,
            alignment = Alignment.TopEnd,
            modifier = Modifier
                .fillMaxSize()
                .graphicsLayer {
                    alpha = imageAlpha
                    scaleX = imageScale
                    scaleY = imageScale
                    translationY = imageOffset
                }
        )

        // A soft cinematic veil keeps the portrait visible while preserving text contrast.
        Box(
            modifier = Modifier
                .fillMaxSize()
                .background(
                    Brush.verticalGradient(
                        colorStops = arrayOf(
                            0f to SplashNavy.copy(alpha = 0.34f),
                            0.36f to Color.Transparent,
                            0.61f to SplashNavy.copy(alpha = 0.68f),
                            0.78f to SplashNavy.copy(alpha = 0.96f),
                            1f to SplashNavy
                        )
                    )
                )
        )
        Box(
            modifier = Modifier
                .fillMaxHeight(0.58f)
                .fillMaxWidth()
                .align(Alignment.TopCenter)
                .background(
                    Brush.horizontalGradient(
                        colorStops = arrayOf(
                            0f to SplashNavy.copy(alpha = 0.30f),
                            0.48f to Color.Transparent,
                            1f to SplashNavy.copy(alpha = 0.14f)
                        )
                    )
                )
        )

        // Subtle warm light that echoes the gold in the village crest.
        Box(
            modifier = Modifier
                .align(Alignment.TopEnd)
                .offset(x = 118.dp, y = (-76).dp)
                .size(300.dp)
                .clip(CircleShape)
                .background(
                    Brush.radialGradient(
                        colors = listOf(
                            SplashGold.copy(alpha = 0.18f),
                            Color.Transparent
                        ),
                        center = Offset(150f, 150f)
                    )
                )
        )

        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(horizontal = 24.dp, vertical = 26.dp),
            verticalArrangement = Arrangement.SpaceBetween
        ) {
            VillageIdentity(
                alpha = identityAlpha,
                offset = identityOffset
            )

            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .alpha(copyAlpha)
                    .graphicsLayer { translationY = copyOffset }
            ) {
                Row(
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Box(
                        modifier = Modifier
                            .width((32 * accentWidth).dp)
                            .height(1.dp)
                            .background(SplashGold)
                    )
                    Spacer(modifier = Modifier.width(10.dp))
                    Text(
                        text = "RUANG DIGITAL WARGA",
                        color = SplashGold,
                        fontSize = 10.sp,
                        fontWeight = FontWeight.SemiBold,
                        letterSpacing = 2.sp
                    )
                }

                Spacer(modifier = Modifier.height(14.dp))

                Text(
                    text = "Lambanggelun",
                    color = SplashIvory,
                    fontSize = 38.sp,
                    fontWeight = FontWeight.Bold,
                    letterSpacing = (-0.8).sp,
                    lineHeight = 42.sp
                )

                Spacer(modifier = Modifier.height(8.dp))

                Text(
                    text = "Informasi dan layanan desa,\ndalam satu genggaman.",
                    color = SplashMuted,
                    fontSize = 15.sp,
                    fontWeight = FontWeight.Normal,
                    lineHeight = 22.sp
                )

                Spacer(modifier = Modifier.height(26.dp))

                Box(
                    modifier = Modifier
                        .fillMaxWidth()
                        .height(2.dp)
                        .clip(RoundedCornerShape(1.dp))
                        .background(Color.White.copy(alpha = 0.13f))
                ) {
                    Box(
                        modifier = Modifier
                            .fillMaxWidth(progress)
                            .height(2.dp)
                            .clip(RoundedCornerShape(1.dp))
                            .background(
                                Brush.horizontalGradient(
                                    colors = listOf(
                                        SplashGold.copy(alpha = 0.35f),
                                        SplashGold
                                    )
                                )
                            )
                    )
                }

                Spacer(modifier = Modifier.height(12.dp))

                Text(
                    text = "SISTEM INFORMASI DESA  •  KABUPATEN PEKALONGAN",
                    color = SplashMuted.copy(alpha = 0.72f),
                    fontSize = 9.sp,
                    fontWeight = FontWeight.Medium,
                    letterSpacing = 1.sp,
                    textAlign = TextAlign.Start
                )
            }
        }
    }
}

@Composable
private fun VillageIdentity(
    alpha: Float,
    offset: Float
) {
    Row(
        verticalAlignment = Alignment.CenterVertically,
        modifier = Modifier
            .alpha(alpha)
            .graphicsLayer { translationY = offset }
    ) {
        Box(
            contentAlignment = Alignment.Center,
            modifier = Modifier
                .size(50.dp)
                .clip(CircleShape)
                .background(SplashNavy.copy(alpha = 0.74f))
                .border(
                    width = 1.dp,
                    color = SplashGold.copy(alpha = 0.42f),
                    shape = CircleShape
                )
        ) {
            Image(
                painter = painterResource(id = R.drawable.logo_sid),
                contentDescription = "Lambang Kabupaten Pekalongan",
                contentScale = ContentScale.Fit,
                modifier = Modifier.size(34.dp)
            )
        }

        Spacer(modifier = Modifier.width(12.dp))

        Column {
            Text(
                text = "PEMERINTAH DESA",
                color = SplashGold,
                fontSize = 9.sp,
                fontWeight = FontWeight.SemiBold,
                letterSpacing = 1.8.sp
            )
            Spacer(modifier = Modifier.height(3.dp))
            Text(
                text = "Lambanggelun",
                color = SplashIvory,
                fontSize = 15.sp,
                fontWeight = FontWeight.SemiBold,
                letterSpacing = 0.2.sp
            )
        }
    }
}
