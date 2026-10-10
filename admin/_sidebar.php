<?php
$activePage = $activePage ?? 'overview';
?>
<!-- Backdrop Overlay untuk Mobile (Tutup saat area luar diklik) -->
<div id="sidebar-backdrop" onclick="closeSidebar()" class="fixed inset-0 z-40 hidden bg-slate-950/60 backdrop-blur-sm lg:hidden"></div>

<!-- Tombol Floating untuk Membuka Sidebar (Tampil saat sidebar tersembunyi) -->
<button id="sidebar-open-btn" onclick="openSidebar()" type="button" class="fixed bottom-5 left-5 z-40 hidden items-center gap-2 rounded-2xl border border-cyan-500/30 bg-slate-900/90 px-4 py-3 text-xs font-bold text-cyan-300 shadow-2xl backdrop-blur-xl transition-all hover:bg-slate-800 hover:text-white focus:outline-none">
    <span>➡️</span>
    <span>Buka Sidebar</span>
</button>

<!-- Sidebar Panel -->
<aside id="sidebar-container" class="fixed inset-y-0 left-0 z-50 flex h-full w-64 shrink-0 flex-col border-r border-white/10 bg-slate-900/95 px-6 py-8 text-white backdrop-blur-xl transition-all duration-300 ease-in-out lg:static lg:z-30">
    <!-- Header Brand & Mobile Logout -->
    <div class="flex items-center justify-between">
        <div>
            <div class="inline-flex items-center gap-1.5 rounded-full border border-cyan-500/30 bg-cyan-500/10 px-2.5 py-0.5 text-[10px] font-extrabold tracking-[0.2em] text-cyan-300">
                <span class="h-1.5 w-1.5 rounded-full bg-cyan-400 animate-pulse"></span>
                E-VOTING OSIS
            </div>
            <h1 class="mt-2 font-display text-2xl font-bold tracking-tight text-white lg:text-3xl">Panel Admin</h1>
        </div>

        <!-- Mobile Logout -->
        <a class="rounded-xl border border-red-500/20 bg-red-500/10 px-3 py-1.5 text-xs font-bold text-red-400 hover:bg-red-500/20 lg:hidden" href="../logout.php">
            Keluar
        </a>
    </div>

    <!-- Desktop Logout -->
    <a class="group hidden rounded-2xl border border-red-500/20 bg-red-500/10 px-4 py-2.5 text-xs font-bold text-red-400 transition-all hover:border-red-500/50 hover:bg-red-600 hover:text-white lg:mt-6 lg:flex lg:items-center lg:justify-center lg:gap-2" href="../logout.php">
        <span>Keluar</span>
        <span class="transition-transform group-hover:translate-x-0.5">→</span>
    </a>

    <!-- Navigasi Menu -->
    <nav class="mt-6 space-y-2 overflow-y-auto border-t border-white/10 pt-4" aria-label="Navigasi admin">
        <?php foreach (['overview' => ['Overview', 'dashboard.php'], 'students' => ['Siswa', 'students.php'], 'candidates' => ['Paslon', 'candidates.php'], 'recap' => ['Rekap suara', 'recap.php'], 'logos' => ['Logo', 'logos.php'], 'copyright' => ['Copyright', 'copyright.php']] as $page => [$label, $url]): ?>
            <a class="flex items-center rounded-2xl px-4 py-3 text-xs font-bold transition-all duration-200 <?= $activePage === $page ? 'border border-cyan-400/30 bg-gradient-to-r from-cyan-500/20 via-blue-600/20 to-transparent text-white shadow-lg shadow-cyan-500/10' : 'text-slate-400 border border-transparent hover:bg-white/5 hover:text-slate-200' ?>" href="<?= $url ?>">
                <span class="h-1.5 w-1.5 rounded-full mr-2.5 <?= $activePage === $page ? 'bg-cyan-400 shadow-[0_0_8px_#22d3ee]' : 'bg-slate-600' ?>"></span>
                <?= $label ?>
            </a>
        <?php endforeach; ?>

        <div class="my-3 h-px bg-white/10"></div>

        <a class="flex items-center rounded-2xl border border-fuchsia-500/30 bg-fuchsia-500/10 px-4 py-3 text-xs font-bold text-fuchsia-300 transition-all duration-200 hover:border-fuchsia-500/60 hover:bg-fuchsia-500/20 hover:text-fuchsia-200 <?= $activePage === 'new-candidate' ? 'ring-2 ring-fuchsia-400/40 bg-fuchsia-500/20 text-white' : '' ?>" href="candidate_new.php">
            <span class="mr-2 text-sm font-black">+</span>
            Tambah Paslon
        </a>
    </nav>

    <!-- Tombol Sembunyikan Sidebar (Paling Bawah) -->
    <div class="mt-auto pt-4 border-t border-white/10">
        <button type="button" onclick="closeSidebar()" class="flex w-full items-center justify-center gap-2 rounded-2xl border border-white/10 bg-white/5 px-4 py-2.5 text-xs font-bold text-slate-300 transition-all hover:bg-white/10 hover:text-white focus:outline-none">
            <span>⬅️</span>
            <span>Sembunyikan Sidebar</span>
        </button>
    </div>
</aside>

<script>
    const sidebar = document.getElementById('sidebar-container');
    const openBtn = document.getElementById('sidebar-open-btn');
    const backdrop = document.getElementById('sidebar-backdrop');

    function closeSidebar() {
        // Efek geser ke kiri (-translate-x-full) & penyusutan margin (-ml-64)
        sidebar.classList.add('-translate-x-full', '-ml-64', 'opacity-0', 'pointer-events-none');
        sidebar.classList.remove('translate-x-0', 'ml-0', 'opacity-100');

        openBtn.classList.remove('hidden');
        openBtn.classList.add('flex');
        if (backdrop) backdrop.classList.add('hidden');
    }

    function openSidebar() {
        // Efek geser ke kanan kembali ke posisi semula
        sidebar.classList.remove('-translate-x-full', '-ml-64', 'opacity-0', 'pointer-events-none');
        sidebar.classList.add('translate-x-0', 'ml-0', 'opacity-100');

        openBtn.classList.add('hidden');
        openBtn.classList.remove('flex');
        
        if (backdrop && window.innerWidth < 1024) {
            backdrop.classList.remove('hidden');
        }
    }

    // Default status: tertutup di HP, terbuka di Desktop saat awal muat halaman
    window.addEventListener('DOMContentLoaded', () => {
        if (window.innerWidth < 1024) {
            closeSidebar();
        }
    });
</script>