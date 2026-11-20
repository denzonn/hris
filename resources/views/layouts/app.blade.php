<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title')</title>

    @include('includes.style')
    @stack('addon-style')

    <!-- Favicon / App Icon -->
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo.png') }}">
</head>

<body class="bg-slate-50 font-poppins overflow-x-hidden">

    <div class="flex min-h-screen">

        <aside id="sidebar"
            class="fixed top-0 left-0
                   w-64
                   h-screen
                   bg-white
                   border-r border-slate-200
                   z-50

                   transition-all duration-300

                   -translate-x-full
                   lg:translate-x-0">

            @include('includes.sidebar')

        </aside>

        <div id="sidebarOverlay" class="hidden fixed inset-0 bg-black/40 z-40 lg:hidden">
        </div>

        <div class="flex-1 min-w-0 lg:ml-64 flex flex-col min-h-screen">

            <header class="sticky top-0 z-30 flex h-16 items-center justify-between gap-4 border-b border-slate-200 bg-white px-4 sm:px-8">

                <div class="flex min-w-0 items-center gap-3">
                    <button id="openSidebar" class="h-10 w-10 shrink-0 rounded-lg text-slate-600 hover:bg-slate-100 lg:hidden" aria-label="Buka menu">
                        <i class="fa-solid fa-bars"></i>
                    </button>

                    <div class="min-w-0">
                        <h1 class="truncate text-lg font-bold text-slate-800">@yield('page_title')</h1>
                        <p class="truncate text-xs text-slate-500">@yield('page_description')</p>
                    </div>
                </div>

                @include('includes.navbar')

            </header>

            <main class="flex-1 min-w-0 p-4 sm:p-6 lg:p-8 overflow-x-hidden">

                @yield('content')

            </main>

            <footer class="flex flex-col gap-1 border-t border-slate-200 bg-white px-8 py-4 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <span class="font-semibold text-slate-800">HUMORA</span> © {{ date('Y') }} All rights reserved.
                </div>
                <div class="flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    Version 1.0.0
                </div>
            </footer>

        </div>

    </div>

    @include('includes.script')
    <script>
        App.submitLoading();
    </script>
    @stack('addon-script')

    <script>
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');

        document.getElementById('openSidebar')?.addEventListener('click', () => {
            sidebar.classList.remove('-translate-x-full');
            overlay.classList.remove('hidden');
        });

        overlay?.addEventListener('click', () => {
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
        });
    </script>

</body>

</html>
