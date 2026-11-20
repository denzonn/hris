@php($saya = auth()->user())

<div class="relative shrink-0">

    <button id="profileButton" class="flex items-center gap-3 rounded-lg px-2 py-1.5 transition-colors hover:bg-slate-100">

        @if ($saya->foto)
            <img src="{{ asset('storage/'.$saya->foto) }}" alt="" class="h-9 w-9 rounded-full border border-primary/20 object-cover">
        @else
            <div class="flex h-9 w-9 items-center justify-center rounded-full border border-primary/20 bg-red-100 text-sm font-bold text-red-800">
                {{ collect(explode(' ', trim($saya->name)))->take(2)->map(fn ($k) => mb_strtoupper(mb_substr($k, 0, 1)))->join('') }}
            </div>
        @endif

        <div class="hidden text-left sm:block">
            <p class="text-xs font-semibold leading-tight text-slate-800">{{ $saya->name }}</p>
            <p class="text-[11px] text-slate-500">{{ $saya->otoritas?->nama }}</p>
        </div>

        <i class="fa-solid fa-chevron-down text-[10px] text-slate-500"></i>

    </button>

    <div id="profileDropdown"
        class="absolute right-0 mt-2 hidden w-56 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">

        <a href="{{ route('profile') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50">
            <i class="fa-solid fa-user w-5 text-slate-500"></i>
            Profil
        </a>

        <form action="{{ route('logout') }}" method="POST" class="border-t border-slate-100">
            @csrf
            <button class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-red-600 hover:bg-red-50">
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
