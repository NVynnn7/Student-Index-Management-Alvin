<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIMDEX | Student Index Management</title>
    <meta name="theme-color" content="#2563EB">
    <script>
        (() => {
            const savedTheme = localStorage.getItem('simdex-theme') || localStorage.getItem('studex-theme') || localStorage.getItem('dm-theme');
            const theme = savedTheme || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.dataset.theme = theme;
            if (/^\/students\/(create|\d+\/edit)$/.test(window.location.pathname)) {
                document.documentElement.classList.add('student-form-route');
            }
        })();
    </script>
    <style>
        html, body { min-height: 100%; margin: 0; background: #F8FAFF; color: #0F172A; font-family: "Segoe UI", Arial, sans-serif; }
        html[data-theme="dark"], html[data-theme="dark"] body { background: #0F172A; color: #F8FAFF; }
        html.student-form-route, html.student-form-route body { background: #F8FAFF; }
        html.student-form-route[data-theme="dark"], html.student-form-route[data-theme="dark"] body { background: #0F172A; }
    </style>
    @viteReactRefresh
    @vite(['resources/js/app.js'])
</head>
<body class="@yield('body_class')">
    @auth
        <header class="app-nav">
            <a class="brand" href="{{ route('students.index') }}">
                <x-simdex-logo variant="full" />
            </a>
            <nav class="nav-actions">
                <a class="nav-link active" href="{{ route('students.index') }}">Dashboard</a>
                <button class="theme-toggle-global" type="button" data-theme-toggle aria-label="Switch color theme">
                    <span class="theme-light-label">Light</span>
                    <span class="theme-dark-label">Dark</span>
                </button>
                <span class="muted">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="button ghost" type="submit">Logout</button>
                </form>
            </nav>
        </header>
    @else
        <button class="theme-toggle-global auth-theme-toggle" type="button" data-theme-toggle aria-label="Switch color theme">
            <span class="theme-light-label">Light</span>
            <span class="theme-dark-label">Dark</span>
        </button>
    @endauth

    <main class="shell @yield('shell_class')">
        @yield('content')
    </main>

    <script>
        document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
            button.addEventListener('click', () => {
                const theme = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
                document.documentElement.dataset.theme = theme;
                localStorage.setItem('simdex-theme', theme);
                window.dispatchEvent(new CustomEvent('simdex-theme-change', { detail: theme }));
            });
        });
    </script>
</body>
</html>
