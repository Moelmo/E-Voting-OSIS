<?php
declare(strict_types=1); 
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') { 
    header('Location: ../index.php'); 
    exit; 
}

require __DIR__ . '/../config/database.php';

$count = (int) $pdo->query('SELECT COUNT(*) FROM kandidat')->fetchColumn();
$error = isset($_GET['error']) ? trim((string) $_GET['error']) : '';
?>
<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Paslon | Admin</title>
    <script>tailwind = { theme: { extend: { fontFamily: { display: ['Plus Jakarta Sans', 'Inter', 'sans-serif'] } } } };</script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 antialiased selection:bg-cyan-500 selection:text-slate-950">
    <main class="flex min-h-screen w-full flex-col lg:h-screen lg:flex-row">
        <?php $activePage = 'new-candidate'; require __DIR__ . '/_sidebar.php'; ?>
        
        <div class="min-w-0 flex-1 overflow-y-auto">
            <article class="mx-auto w-full max-w-4xl px-5 py-8 sm:px-8 lg:px-10 lg:py-10">
                <!-- Header Banner -->
                <header class="relative overflow-hidden rounded-3xl border border-white/10 bg-gradient-to-r from-slate-900 via-indigo-950/80 to-slate-900 p-7 sm:p-9 shadow-2xl backdrop-blur-xl">
                    <div class="absolute -right-10 -top-10 h-48 w-48 rounded-full bg-cyan-500/10 blur-3xl"></div>
                    <div class="relative z-10">
                        <div class="inline-flex items-center gap-2 rounded-full border border-violet-500/30 bg-violet-500/10 px-3 py-1 text-[11px] font-extrabold tracking-[0.2em] text-violet-300">
                            PASLON BARU
                        </div>
                        <h1 class="mt-3 font-display text-3xl font-extrabold tracking-tight text-white sm:text-4xl">Tambah Pasangan Calon</h1>
                        <p class="mt-2 text-sm text-slate-400">Isi data paslon untuk menambah pilihan pada e-voting OSIS.</p>
                    </div>
                </header>

                <!-- Alert Error -->
                <?php if ($error !== ''): ?>
                    <div class="mt-6 rounded-2xl border border-red-500/30 bg-red-500/10 px-5 py-4 text-xs font-medium text-red-300 backdrop-blur-md" role="alert">
                        ⚠️ <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <!-- Form Card -->
                <section class="mt-6 rounded-3xl border border-white/10 bg-slate-900/60 p-6 shadow-xl backdrop-blur-xl sm:p-8">
                    <div class="mb-6 flex flex-wrap items-center justify-between gap-4 border-b border-white/10 pb-6">
                        <div>
                            <p class="text-[10px] font-extrabold tracking-[0.2em] text-cyan-400">KAPASITAS PASLON</p>
                            <h2 class="mt-1 font-display text-2xl font-bold text-white">Slot <?= $count ?>/4</h2>
                        </div>
                        <span class="rounded-full border border-cyan-500/30 bg-cyan-500/10 px-4 py-1.5 text-xs font-bold text-cyan-300">
                            <?= max(0, 4 - $count) ?> slot tersisa
                        </span>
                    </div>

                    <?php if ($count < 4): ?>
                        <form class="grid gap-6 md:grid-cols-2" action="candidate_add.php" method="post" enctype="multipart/form-data">
                            <div>
                                <label class="mb-2 block text-xs font-bold text-slate-300" for="nama_ketua">Nama Ketua</label>
                                <input id="nama_ketua" class="w-full rounded-xl border border-white/10 bg-slate-950 px-4 py-3 text-sm text-white placeholder-slate-500 outline-none transition-all focus:border-cyan-500/50 focus:ring-1 focus:ring-cyan-500/50" name="nama_ketua" placeholder="Masukkan nama ketua..." required>
                            </div>

                            <div>
                                <label class="mb-2 block text-xs font-bold text-slate-300" for="nama_wakil">Nama Wakil</label>
                                <input id="nama_wakil" class="w-full rounded-xl border border-white/10 bg-slate-950 px-4 py-3 text-sm text-white placeholder-slate-500 outline-none transition-all focus:border-cyan-500/50 focus:ring-1 focus:ring-cyan-500/50" name="nama_wakil" placeholder="Masukkan nama wakil..." required>
                            </div>

                            <div class="md:col-span-2">
                                <label class="mb-2 block text-xs font-bold text-slate-300" for="foto">Foto Paslon</label>
                                <input id="foto" class="block w-full cursor-pointer rounded-xl border border-white/10 bg-slate-950 text-xs text-slate-400 file:mr-4 file:cursor-pointer file:border-0 file:bg-cyan-500/20 file:px-4 file:py-3 file:text-xs file:font-bold file:text-cyan-300 hover:file:bg-cyan-500/30 focus:outline-none" name="foto" type="file" accept="image/jpeg,image/png,image/webp">
                                <p class="mt-1.5 text-[11px] text-slate-500">Format gambar yang didukung: JPG, PNG, WEBP.</p>
                            </div>

                            <div class="md:col-span-2">
                                <label class="mb-2 block text-xs font-bold text-slate-300" for="visi_misi">Visi dan Misi</label>
                                <textarea id="visi_misi" class="min-h-36 w-full rounded-xl border border-white/10 bg-slate-950 px-4 py-3 text-sm text-white placeholder-slate-500 outline-none transition-all focus:border-cyan-500/50 focus:ring-1 focus:ring-cyan-500/50" name="visi_misi" placeholder="Tuliskan visi dan misi paslon secara lengkap..." required></textarea>
                            </div>

                            <div class="md:col-span-2 pt-2">
                                <button class="w-full rounded-xl bg-cyan-500 px-5 py-3.5 text-xs font-bold text-slate-950 shadow-lg shadow-cyan-500/20 transition-all hover:bg-cyan-400 hover:shadow-cyan-500/30" type="submit">
                                    ✨ Simpan Paslon
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="rounded-2xl border border-amber-500/30 bg-amber-500/10 p-5 text-xs font-semibold text-amber-300 backdrop-blur-md">
                            ⚠️ Maksimal 4 paslon sudah tercapai. Silakan edit atau hapus paslon yang tersedia melalui menu Paslon.
                        </div>
                    <?php endif; ?>
                </section>
            </article>
        </div>
    </main>
</body>
</html>