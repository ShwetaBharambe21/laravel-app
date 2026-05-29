<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Guestbook — Laravel on AWS</title>
    @vite(['resources/js/app.js'])
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: ui-sans-serif, system-ui, -apple-system, sans-serif;
            background: #0f172a;
            color: #e2e8f0;
            min-height: 100vh;
        }

        /* ── Nav ── */
        nav {
            background: #1e293b;
            border-bottom: 1px solid #334155;
            padding: .9rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .nav-brand { display: flex; align-items: center; gap: .65rem; font-weight: 700; font-size: 1rem; color: #f1f5f9; }
        .nav-brand .dot { width: 10px; height: 10px; background: #22c55e; border-radius: 50%; box-shadow: 0 0 6px #22c55e; }
        .nav-meta { font-size: .78rem; color: #64748b; }
        .nav-meta strong { color: #f97316; }

        /* ── Hero ── */
        .hero {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 60%);
            border-bottom: 1px solid #334155;
            padding: 3.5rem 1.5rem 3rem;
            text-align: center;
        }
        .hero h1 { font-size: 2.4rem; font-weight: 800; color: #f1f5f9; line-height: 1.2; margin-bottom: .75rem; }
        .hero h1 span { color: #f97316; }
        .hero p { font-size: 1rem; color: #94a3b8; max-width: 480px; margin: 0 auto 2rem; }
        .stats-row { display: flex; justify-content: center; gap: 2.5rem; flex-wrap: wrap; }
        .stat-pill { text-align: center; }
        .stat-pill .num { font-size: 1.8rem; font-weight: 800; color: #f97316; }
        .stat-pill .lbl { font-size: .72rem; text-transform: uppercase; letter-spacing: .07em; color: #64748b; margin-top: .15rem; }

        /* ── Main layout ── */
        .layout { display: grid; grid-template-columns: 1fr 380px; gap: 2rem; max-width: 1050px; margin: 2.5rem auto; padding: 0 1.5rem 3rem; }
        @media (max-width: 780px) { .layout { grid-template-columns: 1fr; } }

        /* ── Message feed ── */
        .feed-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; }
        .feed-header h2 { font-size: 1rem; font-weight: 700; color: #f1f5f9; }
        .feed-header span { font-size: .78rem; color: #64748b; }

        .empty { background: #1e293b; border: 1px dashed #334155; border-radius: .75rem; padding: 3rem; text-align: center; color: #475569; }

        .message-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: .75rem;
            padding: 1.1rem 1.25rem;
            margin-bottom: .875rem;
            transition: border-color .15s;
        }
        .message-card:hover { border-color: #475569; }
        .card-header { display: flex; align-items: center; gap: .75rem; margin-bottom: .65rem; }
        .avatar {
            width: 36px; height: 36px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: .85rem; color: white;
            flex-shrink: 0; text-transform: uppercase;
        }
        .card-name { font-weight: 600; font-size: .9rem; color: #e2e8f0; }
        .card-time { font-size: .73rem; color: #64748b; margin-top: .1rem; }
        .card-body { font-size: .9rem; color: #94a3b8; line-height: 1.6; }

        /* ── Form ── */
        .form-panel {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: .75rem;
            padding: 1.5rem;
            position: sticky;
            top: 1.5rem;
            height: fit-content;
        }
        .form-panel h2 { font-size: 1rem; font-weight: 700; color: #f1f5f9; margin-bottom: 1.25rem; }
        label { display: block; font-size: .78rem; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: .06em; margin-bottom: .4rem; }
        input[type=text], textarea {
            width: 100%;
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: .5rem;
            color: #e2e8f0;
            font-size: .9rem;
            padding: .65rem .85rem;
            outline: none;
            transition: border-color .15s;
            font-family: inherit;
        }
        input[type=text]:focus, textarea:focus { border-color: #6366f1; }
        textarea { resize: vertical; min-height: 100px; }
        .form-group { margin-bottom: 1rem; }
        .error-msg { font-size: .75rem; color: #f87171; margin-top: .3rem; }
        .btn {
            width: 100%;
            background: #f97316;
            color: white;
            font-weight: 700;
            font-size: .9rem;
            padding: .75rem;
            border: none;
            border-radius: .5rem;
            cursor: pointer;
            margin-top: .25rem;
            transition: background .15s;
        }
        .btn:hover { background: #ea6c0a; }
        .char-count { font-size: .72rem; color: #64748b; text-align: right; margin-top: .3rem; }

        /* ── Toast ── */
        .toast {
            background: #14532d;
            border: 1px solid #16a34a;
            color: #4ade80;
            border-radius: .5rem;
            padding: .7rem 1rem;
            margin-bottom: 1.25rem;
            font-size: .85rem;
            font-weight: 500;
        }

        /* ── Footer ── */
        .footer { text-align: center; color: #334155; font-size: .75rem; padding: 1rem; border-top: 1px solid #1e293b; }
    </style>
</head>
<body>

<nav>
    <div class="nav-brand">
        <div class="dot"></div>
        Guestbook &mdash; Laravel on AWS
    </div>
    <div class="nav-meta">
        PHP {{ PHP_VERSION }} &nbsp;·&nbsp; Laravel <strong>{{ app()->version() }}</strong>
    </div>
</nav>

<div class="hero">
    <h1>Leave a <span>Message</span></h1>
    <p>A live guestboard running on AWS EC2 + Docker + ALB, deployed via GitHub Actions.</p>
    <div class="stats-row">
        <div class="stat-pill">
            <div class="num">{{ number_format($total) }}</div>
            <div class="lbl">Messages</div>
        </div>
        <div class="stat-pill">
            <div class="num">{{ number_format($visits) }}</div>
            <div class="lbl">Page Views</div>
        </div>
        <div class="stat-pill">
            <div class="num">{{ app()->environment() }}</div>
            <div class="lbl">Environment</div>
        </div>
    </div>
</div>

<div class="layout">

    {{-- Feed --}}
    <div>
        <div class="feed-header">
            <h2>Recent Messages</h2>
            <span>{{ $messages->count() }} shown</span>
        </div>

        @if ($messages->isEmpty())
            <div class="empty">
                No messages yet. Be the first to say hello!
            </div>
        @else
            @foreach ($messages as $msg)
            <div class="message-card">
                <div class="card-header">
                    <div class="avatar" style="background: {{ $msg->avatar_color }}">
                        {{ strtoupper(substr($msg->name, 0, 1)) }}
                    </div>
                    <div>
                        <div class="card-name">{{ $msg->name }}</div>
                        <div class="card-time">{{ $msg->created_at->diffForHumans() }}</div>
                    </div>
                </div>
                <div class="card-body">{{ $msg->body }}</div>
            </div>
            @endforeach
        @endif
    </div>

    {{-- Form --}}
    <div>
        <div class="form-panel">
            <h2>Post a Message</h2>

            @if (session('success'))
                <div class="toast">✓ {{ session('success') }}</div>
            @endif

            <form action="/messages" method="POST">
                @csrf
                <div class="form-group">
                    <label for="name">Your Name</label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        maxlength="80"
                        placeholder="e.g. Jane Doe"
                        value="{{ old('name') }}"
                        autocomplete="off"
                    >
                    @error('name')
                        <div class="error-msg">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="body">Message</label>
                    <textarea
                        id="body"
                        name="body"
                        maxlength="500"
                        placeholder="Say something..."
                        oninput="document.getElementById('cc').textContent = this.value.length + '/500'"
                    >{{ old('body') }}</textarea>
                    <div class="char-count"><span id="cc">0/500</span></div>
                    @error('body')
                        <div class="error-msg">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn">Post Message</button>
            </form>
        </div>
    </div>

</div>

<div class="footer">
    Deployed via GitHub Actions &rarr; ECR &rarr; EC2 Auto Scaling Group &nbsp;|&nbsp; {{ now()->format('d M Y') }}
</div>

</body>
</html>
