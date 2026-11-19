<div class="relative hidden md:block">

    <button id="profileButton" class="flex items-center gap-3 rounded-xl px-2 py-2 hover:bg-slate-100 transition">

        <div class="w-10 h-10 rounded-full overflow-hidden bg-slate-100">

            @if (auth()->user()->foto)
                <img src="{{ asset('storage/'.auth()->user()->foto) }}" alt="" class="w-full h-full object-cover">
            @else
                <div class="w-full h-full flex items-center justify-center">

                    <i class="fa-solid fa-user text-slate-500"></i>

                </div>
            @endif

        </div>

        <div class="hidden md:block text-left">

            <h4 class="text-sm font-semibold text-slate-700">
                {{ auth()->user()->name }}
            </h4>

            <p class="text-xs text-slate-400">
                {{ auth()->user()->otoritas?->nama }}
            </p>

        </div>

        <i class="fa-solid fa-chevron-down text-xs text-slate-400"></i>

    </button>

    <div id="profileDropdown"
        class="hidden absolute right-0 mt-3 w-56 bg-white border border-slate-200 rounded-2xl shadow-lg overflow-hidden">

        <a href="{{ route('profile') }}" class="flex items-center gap-3 px-5 py-3 text-slate-700 hover:bg-slate-50">
            <i class="fa-solid fa-user w-5"></i>
            Profil
        </a>

        <form action="{{ route('logout') }}" method="POST">

            @csrf

            <button class="w-full text-left px-5 py-3 text-red-600 hover:bg-red-50 flex items-center gap-3">

                <i class="fa-solid fa-right-from-bracket w-5"></i>

                Logout

            </button>

        </form>

    </div>

</div>

<script>
    const btn = document.getElementById('profileButton');
    const menu = document.getElementById('profileDropdown');

    btn.onclick = () => menu.classList.toggle('hidden');

    document.addEventListener('click', function(e) {
        if (!btn.contains(e.target) && !menu.contains(e.target)) menu.classList.add('hidden');
    });
</script>
