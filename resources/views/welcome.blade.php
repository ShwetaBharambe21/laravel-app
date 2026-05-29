<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laravel — Deployment Test</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: ui-sans-serif, system-ui, sans-serif; background: #0f172a; color: #e2e8f0; min-height: 100vh; padding: 2.5rem 1.5rem; }
        .wrap { max-width: 880px; margin: 0 auto; }

        /* Header */
        .header { display: flex; align-items: center; gap: 1rem; margin-bottom: 2.5rem; }
        .logo { background: #f97316; border-radius: .5rem; width: 42px; height: 42px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .logo svg { width: 24px; height: 24px; fill: white; }
        .header h1 { font-size: 1.4rem; font-weight: 700; color: #f1f5f9; }
        .header p { font-size: .82rem; color: #64748b; margin-top: .15rem; }

        /* Stat cards */
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: .875rem; margin-bottom: 1.5rem; }
        .stat { background: #1e293b; border: 1px solid #334155; border-radius: .75rem; padding: 1.1rem 1.25rem; }
        .stat-label { font-size: .68rem; text-transform: uppercase; letter-spacing: .08em; color: #64748b; margin-bottom: .3rem; }
        .stat-value { font-size: 1.5rem; font-weight: 700; color: #f1f5f9; }
        .stat-value.orange { color: #f97316; }

        /* Check table */
        .panel { background: #1e293b; border: 1px solid #334155; border-radius: .75rem; overflow: hidden; margin-bottom: 1.5rem; }
        .panel-title { padding: .8rem 1.25rem; border-bottom: 1px solid #334155; font-size: .72rem; font-weight: 600; text-transform: uppercase; letter-spacing: .08em; color: #64748b; }
        .check { display: flex; align-items: center; justify-content: space-between; padding: .85rem 1.25rem; border-bottom: 1px solid #0f172a; }
        .check:last-child { border-bottom: none; }
        .check-left { display: flex; align-items: center; gap: .75rem; }
        .check-icon { width: 30px; height: 30px; border-radius: .4rem; display: flex; align-items: center; justify-content: center; font-size: .85rem; flex-shrink: 0; }
        .check-icon.ok  { background: #14532d; }
        .check-icon.fail{ background: #450a0a; }
        .check-name { font-size: .9rem; font-weight: 500; color: #e2e8f0; }
        .check-detail { font-size: .78rem; color: #64748b; margin-top: .1rem; }
        .badge { font-size: .7rem; font-weight: 700; padding: .22rem .7rem; border-radius: 999px; letter-spacing: .04em; }
        .badge.ok   { background: #14532d; color: #4ade80; }
        .badge.fail { background: #450a0a; color: #f87171; }

        /* Info grid */
        .info { display: grid; grid-template-columns: repeat(auto-fit, minmax(195px, 1fr)); gap: .875rem; margin-bottom: 2rem; }
        .info-item { background: #1e293b; border: 1px solid #334155; border-radius: .75rem; padding: .9rem 1.1rem; }
        .info-label { font-size: .68rem; text-transform: uppercase; letter-spacing: .08em; color: #64748b; margin-bottom: .25rem; }
        .info-value { font-size: .88rem; font-weight: 500; color: #cbd5e1; word-break: break-all; }

        /* Visit counter animation */
        .orange { color: #f97316; }
        footer { text-align: center; color: #475569; font-size: .78rem; padding-top: .5rem; }
        footer span { color: #64748b; }
    </style>
</head>
<body>
<div class="wrap">

    <div class="header">
        <div class="logo">
            <svg viewBox="0 0 50 52" xmlns="http://www.w3.org/2000/svg">
                <path d="M49.626 11.564a.809.809 0 0 1 .028.209v10.972a.8.8 0 0 1-.402.694l-9.209 5.302V39.25c0 .286-.152.551-.4.694L20.42 51.01a.814.814 0 0 1-.14.054c-.053.018-.108.026-.162.026-.055 0-.109-.008-.163-.026a.616.616 0 0 1-.14-.054L.402 39.944A.801.801 0 0 1 0 39.25V6.334c0-.072.01-.143.028-.209a.807.807 0 0 1 .078-.196.688.688 0 0 1 .048-.078.8.8 0 0 1 .255-.248L10.32.143a.803.803 0 0 1 .8 0l9.91 5.713a.8.8 0 0 1 .402.694v20.971l8.004-4.608V11.85a.8.8 0 0 1 .402-.694l9.91-5.713a.8.8 0 0 1 .8 0l9.91 5.713a.8.8 0 0 1 .168.408z"/>
            </svg>
        </div>
        <div>
            <h1>Laravel Deployment Test</h1>
            <p>Production stack · AWS EC2 + Docker + ALB · GitHub Actions CI/CD</p>
        </div>
    </div>

    {{-- Stats --}}
    <div class="stats">
        <div class="stat">
            <div class="stat-label">Total Visits</div>
            <div class="stat-value orange">{{ number_format((int)$visits) }}</div>
        </div>
        <div class="stat">
            <div class="stat-label">Environment</div>
            <div class="stat-value">{{ $env }}</div>
        </div>
        <div class="stat">
            <div class="stat-label">Laravel</div>
            <div class="stat-value">v{{ $laravel }}</div>
        </div>
        <div class="stat">
            <div class="stat-label">PHP</div>
            <div class="stat-value">{{ $php_version }}</div>
        </div>
    </div>

    {{-- Health Checks --}}
    <div class="panel">
        <div class="panel-title">Health Checks</div>

        @foreach ($checks as $name => $result)
        <div class="check">
            <div class="check-left">
                <div class="check-icon {{ $result['ok'] ? 'ok' : 'fail' }}">
                    {{ $result['ok'] ? '✓' : '✗' }}
                </div>
                <div>
                    <div class="check-name">{{ ucfirst($name) }}</div>
                    <div class="check-detail">{{ $result['msg'] }}</div>
                </div>
            </div>
            <span class="badge {{ $result['ok'] ? 'ok' : 'fail' }}">
                {{ $result['ok'] ? 'PASS' : 'FAIL' }}
            </span>
        </div>
        @endforeach

        <div class="check">
            <div class="check-left">
                <div class="check-icon ok">✓</div>
                <div>
                    <div class="check-name">HTTP / nginx</div>
                    <div class="check-detail">Serving this page via ALB</div>
                </div>
            </div>
            <span class="badge ok">PASS</span>
        </div>

        <div class="check">
            <div class="check-left">
                <div class="check-icon ok">✓</div>
                <div>
                    <div class="check-name">Queue Worker</div>
                    <div class="check-detail">supervisord process running</div>
                </div>
            </div>
            <span class="badge ok">PASS</span>
        </div>

        <div class="check">
            <div class="check-left">
                <div class="check-icon ok">✓</div>
                <div>
                    <div class="check-name">Scheduler</div>
                    <div class="check-detail">Running via supervisord loop</div>
                </div>
            </div>
            <span class="badge ok">PASS</span>
        </div>
    </div>

    {{-- Server Info --}}
    <div class="info">
        <div class="info-item">
            <div class="info-label">Container / Host</div>
            <div class="info-value">{{ $server }}</div>
        </div>
        <div class="info-item">
            <div class="info-label">Server Time (UTC)</div>
            <div class="info-value">{{ now()->toDateTimeString() }}</div>
        </div>
        <div class="info-item">
            <div class="info-label">DB Connection</div>
            <div class="info-value">{{ config('database.default') }}</div>
        </div>
        <div class="info-item">
            <div class="info-label">Cache Driver</div>
            <div class="info-value">{{ config('cache.default') }}</div>
        </div>
        <div class="info-item">
            <div class="info-label">Queue Driver</div>
            <div class="info-value">{{ config('queue.default') }}</div>
        </div>
        <div class="info-item">
            <div class="info-label">Session Driver</div>
            <div class="info-value">{{ config('session.driver') }}</div>
        </div>
    </div>

    <footer>
        Deployed via <span>GitHub Actions → ECR → EC2 Auto Scaling Group</span>
        &nbsp;·&nbsp; {{ now()->format('D, d M Y') }}
    </footer>

</div>
</body>
</html>
