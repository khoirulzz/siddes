<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - {{ config('village.name', 'Sistem Desa') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2b6cb0;
            --primary-dark: #2c5282;
            --bg-gradient: linear-gradient(135deg, #e2e8f0 0%, #edf2f7 100%);
            --text-dark: #1a202c;
            --text-muted: #718096;
            --white: #ffffff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg-gradient);
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            overflow-x: hidden;
        }

        .error-container {
            text-align: center;
            max-width: 600px;
            width: 100%;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 1.5rem;
            padding: 3rem 2rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.05);
            animation: fadeUp 0.6s ease-out forwards;
        }

        .error-image-wrapper {
            margin-bottom: 2rem;
            position: relative;
        }

        .error-image {
            max-width: 280px;
            width: 100%;
            height: auto;
            animation: float 4s ease-in-out infinite;
            filter: drop-shadow(0 10px 15px rgba(0,0,0,0.1));
        }

        .error-code {
            font-size: 5rem;
            font-weight: 700;
            line-height: 1;
            margin-bottom: 0.5rem;
            color: var(--primary);
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
            letter-spacing: -2px;
        }

        .error-title {
            font-size: 1.75rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--text-dark);
        }

        .error-message {
            font-size: 1rem;
            color: var(--text-muted);
            margin-bottom: 2.5rem;
            line-height: 1.6;
            max-width: 90%;
            margin-left: auto;
            margin-right: auto;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            background: var(--primary);
            color: var(--white);
            text-decoration: none;
            padding: 0.75rem 1.75rem;
            border-radius: 99px;
            font-weight: 500;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(43, 108, 176, 0.2);
            border: none;
            cursor: pointer;
        }

        .btn-back:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(43, 108, 176, 0.3);
        }

        .btn-back svg {
            width: 20px;
            height: 20px;
            transition: transform 0.3s ease;
        }

        .btn-back:hover svg {
            transform: translateX(-4px);
        }

        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
            100% { transform: translateY(0px); }
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 480px) {
            .error-code { font-size: 4rem; }
            .error-title { font-size: 1.5rem; }
            .error-container { padding: 2rem 1.5rem; }
            .error-image { max-width: 200px; }
        }
    </style>
</head>
<body>

    <div class="error-container">
        <div class="error-image-wrapper">
            <!-- Using the apologizing person image -->
            <img src="{{ asset('assets/images/eror-pict.webp') }}" alt="Error Illustration" class="error-image">
        </div>

        <div class="error-code">@yield('code')</div>
        <h1 class="error-title">@yield('title')</h1>
        
        <p class="error-message">
            @yield('message')
        </p>

        <a href="{{ url('/') }}" class="btn-back">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
            Kembali ke Beranda
        </a>
    </div>

</body>
</html>
