<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WebGIS — Pilih Project</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:      #f5f0eb;
            --surface: #fafaf9;
            --border:  #ddd8d2;
            --text:    #201515;
            --muted:   #7a7067;
            --accent:  #0d7490;
            --accent-h:#0a5f7a;
            --shadow:  0 4px 16px rgba(32,21,21,.08);
            --font:    'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        html, body {
            height: 100%;
            font-family: var(--font);
            background: var(--bg);
            color: var(--text);
        }

        body {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 24px 16px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
        }

        .logo-icon {
            width: 40px;
            height: 40px;
            background: var(--accent);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
        }

        .logo-icon .lucide { width: 22px; height: 22px; }

        .logo-name {
            font-size: 20px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .tagline {
            font-size: 13px;
            color: var(--muted);
            margin-bottom: 40px;
            text-align: center;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            width: 100%;
            max-width: 640px;
        }

        @media (max-width: 520px) {
            .cards { grid-template-columns: 1fr; }
        }

        .card {
            background: var(--surface);
            border: 1.5px solid var(--border);
            border-radius: 16px;
            padding: 28px 24px;
            text-decoration: none;
            color: var(--text);
            display: flex;
            flex-direction: column;
            gap: 14px;
            transition: border-color .15s, box-shadow .15s, transform .15s;
            cursor: pointer;
        }

        .card:hover {
            border-color: var(--accent);
            box-shadow: 0 6px 24px rgba(13,116,144,.12);
            transform: translateY(-2px);
        }

        .card-icon {
            width: 48px;
            height: 48px;
            background: #e8f4f7;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent);
        }

        .card-icon .lucide { width: 24px; height: 24px; }

        .card-label {
            font-size: 11px;
            font-weight: 600;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .6px;
        }

        .card-title {
            font-size: 16px;
            font-weight: 700;
            letter-spacing: -0.3px;
            line-height: 1.3;
        }

        .card-desc {
            font-size: 12px;
            color: var(--muted);
            line-height: 1.6;
            flex: 1;
        }

        .card-cta {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 600;
            color: var(--accent);
            margin-top: 4px;
        }

        .card-cta .lucide { width: 14px; height: 14px; }

        footer {
            margin-top: 40px;
            font-size: 12px;
            color: var(--muted);
        }
    </style>
</head>
<body>

    <div class="logo">
        <div class="logo-icon"><i data-lucide="map"></i></div>
        <span class="logo-name">WebGIS</span>
    </div>
    <p class="tagline">Pilih project yang ingin dibuka</p>

    <div class="cards">

        <a class="card" href="01/index.php">
            <div class="card-icon"><i data-lucide="map-pin"></i></div>
            <div>
                <div class="card-label">Project 01</div>
                <div class="card-title">WebGIS Pontianak</div>
            </div>
            <div class="card-desc">Pemetaan Point of Interest, data jalan, dan parsil tanah wilayah Pontianak.</div>
            <div class="card-cta">Buka <i data-lucide="arrow-right"></i></div>
        </a>

        <a class="card" href="WebgisPovertyMapping/index.php">
            <div class="card-icon"><i data-lucide="users"></i></div>
            <div>
                <div class="card-label">Aplikasi Final</div>
                <div class="card-title">WebGIS Poverty Mapping</div>
            </div>
            <div class="card-desc">Sistem pemetaan kemiskinan berbasis rumah ibadah dengan manajemen kebutuhan warga.</div>
            <div class="card-cta">Buka <i data-lucide="arrow-right"></i></div>
        </a>

    </div>

    <footer>WebGIS · Sistem Informasi Geografis</footer>

    <script>lucide.createIcons();</script>
</body>
</html>
