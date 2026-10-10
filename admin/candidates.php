<?php
declare(strict_types=1);

session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

require __DIR__ . '/../config/database.php';
$candidates = $pdo->query('SELECT id, nomor_urut, nama_ketua, nama_wakil, foto, visi_misi FROM kandidat ORDER BY nomor_urut ASC')->fetchAll();
$error = isset($_GET['error']) ? trim((string) $_GET['error']) : '';
$success = isset($_GET['success']) ? trim((string) $_GET['success']) : '';
?>
<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paslon | Admin</title>
    <script>tailwind = { theme: { extend: { fontFamily: { display: ['Plus Jakarta Sans', 'Inter', 'sans-serif'] } } } };</script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 antialiased selection:bg-cyan-500 selection:text-slate-950">
    <main class="flex min-h-screen w-full flex-col lg:h-screen lg:flex-row">
        <?php $activePage = 'candidates'; require __DIR__ . '/_sidebar.php'; ?>
        
        <div class="min-w-0 flex-1 overflow-y-auto">
            <article class="mx-auto w-full max-w-7xl px-5 py-8 sm:px-8 lg:px-10 lg:py-10">
                <!-- Header Banner -->
                <header class="relative overflow-hidden rounded-3xl border border-white/10 bg-gradient-to-r from-slate-900 via-indigo-950/80 to-slate-900 p-7 sm:p-9 shadow-2xl backdrop-blur-xl">
                    <div class="absolute -right-10 -top-10 h-48 w-48 rounded-full bg-cyan-500/10 blur-3xl"></div>
                    <div class="relative z-10">
                        <div class="inline-flex items-center gap-2 rounded-full border border-fuchsia-500/30 bg-fuchsia-500/10 px-3 py-1 text-[11px] font-extrabold tracking-[0.2em] text-fuchsia-300">
                            KONTEN PEMILIHAN
                        </div>
                        <h1 class="mt-3 font-display text-3xl font-extrabold tracking-tight text-white sm:text-4xl">Kelola Pasangan Calon</h1>
                        <p class="mt-2 text-sm text-slate-400">Edit, perbarui visi misi, atau hapus pasangan calon. Maksimal 4 paslon tersedia.</p>
                    </div>
                </header>

                <!-- Alerts -->
                <?php if ($error !== ''): ?>
                    <div class="mt-6 rounded-2xl border border-red-500/30 bg-red-500/10 px-5 py-4 text-xs font-medium text-red-300 backdrop-blur-md" role="alert">
                        ⚠️ <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success !== ''): ?>
                    <div class="mt-6 rounded-2xl border border-emerald-500/30 bg-emerald-500/10 px-5 py-4 text-xs font-medium text-emerald-300 backdrop-blur-md" role="status">
                        ✓ <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <!-- Action Button -->
                <div class="my-6 flex justify-end">
                    <a class="inline-flex items-center gap-2 rounded-xl border border-fuchsia-500/30 bg-fuchsia-500/20 px-4 py-2.5 text-xs font-bold text-fuchsia-200 transition-all hover:border-fuchsia-500/60 hover:bg-fuchsia-500/30 hover:text-white" href="candidate_new.php">
                        <span class="text-sm">+</span> Tambah Paslon Baru
                    </a>
                </div>

                <!-- Grid Paslon -->
                <div class="grid gap-6 xl:grid-cols-2">
                    <?php foreach ($candidates as $candidate): ?>
                        <div class="flex flex-col rounded-3xl border border-white/10 bg-slate-900/60 p-6 shadow-xl backdrop-blur-xl transition-all hover:border-white/20">
                            <form action="candidate_update.php" method="post" enctype="multipart/form-data" class="flex-1">
                                <input type="hidden" name="candidate_id" value="<?= (int) $candidate['id'] ?>">
                                
                                <!-- Header Card Paslon -->
                                <div class="mb-6 flex items-center gap-4 border-b border-white/10 pb-5">
                                    <?php if (!empty($candidate['foto']) && $candidate['foto'] !== 'default.jpg'): ?>
                                        <img class="h-20 w-20 rounded-2xl border border-white/10 object-cover shadow-lg" src="../assets/<?= htmlspecialchars($candidate['foto'], ENT_QUOTES, 'UTF-8') ?>" alt="Foto paslon nomor <?= (int) $candidate['nomor_urut'] ?>">
                                    <?php else: ?>
                                        <div class="grid h-20 w-20 place-items-center rounded-2xl border border-fuchsia-500/30 bg-fuchsia-500/10 text-sm font-extrabold text-fuchsia-300 shadow-inner">
                                            No. <?= (int) $candidate['nomor_urut'] ?>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <span class="inline-block rounded-md border border-fuchsia-500/30 bg-fuchsia-500/10 px-2 py-0.5 text-[10px] font-black tracking-widest text-fuchsia-300">
                                            PASLON <?= (int) $candidate['nomor_urut'] ?>
                                        </span>
                                        <h2 class="mt-1 font-display text-xl font-bold text-white">
                                            <?= htmlspecialchars($candidate['nama_ketua'], ENT_QUOTES, 'UTF-8') ?>
                                            <span class="text-slate-400 font-normal">&amp;</span>
                                            <?= htmlspecialchars($candidate['nama_wakil'], ENT_QUOTES, 'UTF-8') ?>
                                        </h2>
                                    </div>
                                </div>

                                <!-- Form Inputs -->
                                <div class="space-y-4">
                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <div>
                                            <label class="mb-1.5 block text-xs font-bold text-slate-300" for="ketua-<?= (int) $candidate['id'] ?>">Nama Ketua</label>
                                            <input id="ketua-<?= (int) $candidate['id'] ?>" class="w-full rounded-xl border border-white/10 bg-slate-950/80 px-4 py-2.5 text-xs text-white placeholder-slate-500 transition-all focus:border-cyan-500 focus:outline-none focus:ring-1 focus:ring-cyan-500" name="nama_ketua" value="<?= htmlspecialchars($candidate['nama_ketua'], ENT_QUOTES, 'UTF-8') ?>" required>
                                        </div>
                                        <div>
                                            <label class="mb-1.5 block text-xs font-bold text-slate-300" for="wakil-<?= (int) $candidate['id'] ?>">Nama Wakil</label>
                                            <input id="wakil-<?= (int) $candidate['id'] ?>" class="w-full rounded-xl border border-white/10 bg-slate-950/80 px-4 py-2.5 text-xs text-white placeholder-slate-500 transition-all focus:border-cyan-500 focus:outline-none focus:ring-1 focus:ring-cyan-500" name="nama_wakil" value="<?= htmlspecialchars($candidate['nama_wakil'], ENT_QUOTES, 'UTF-8') ?>" required>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="mb-1.5 block text-xs font-bold text-slate-300" for="foto-<?= (int) $candidate['id'] ?>">Ganti Foto Paslon</label>
                                        <input id="foto-<?= (int) $candidate['id'] ?>" class="block w-full rounded-xl border border-white/10 bg-slate-950/80 text-xs text-slate-400 file:mr-3 file:border-0 file:bg-white/10 file:px-4 file:py-2.5 file:text-xs file:font-bold file:text-slate-200 hover:file:bg-white/20 focus:outline-none" type="file" name="foto" accept="image/jpeg,image/png,image/webp">
                                    </div>

                                    <div>
                                        <label class="mb-1.5 block text-xs font-bold text-slate-300" for="visi-<?= (int) $candidate['id'] ?>">Visi &amp; Misi</label>
                                        <textarea id="visi-<?= (int) $candidate['id'] ?>" class="min-h-28 w-full rounded-xl border border-white/10 bg-slate-950/80 px-4 py-2.5 text-xs text-white placeholder-slate-500 transition-all focus:border-cyan-500 focus:outline-none focus:ring-1 focus:ring-cyan-500" name="visi_misi" required><?= htmlspecialchars($candidate['visi_misi'], ENT_QUOTES, 'UTF-8') ?></textarea>
                                    </div>

                                    <button class="w-full rounded-xl border border-cyan-500/30 bg-gradient-to-r from-cyan-500/20 via-blue-600/20 to-cyan-500/20 px-4 py-3 text-xs font-bold text-cyan-300 shadow-lg transition-all hover:border-cyan-400/50 hover:bg-cyan-500/30 hover:text-white" type="submit">
                                        Simpan Perubahan
                                    </button>
                                </div>
                            </form>

                            <!-- Form Hapus -->
                            <form class="mt-4 border-t border-white/10 pt-4" action="candidate_delete.php" method="post" onsubmit="return confirm('Hapus paslon ini dan seluruh suaranya?');">
                                <input type="hidden" name="candidate_id" value="<?= (int) $candidate['id'] ?>">
                                <button class="w-full rounded-xl border border-red-500/20 bg-red-500/10 px-4 py-2.5 text-xs font-bold text-red-400 transition-all hover:border-red-500/40 hover:bg-red-600 hover:text-white" type="submit">
                                    Hapus Paslon
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </article>
        </div>
    </main>
</body>
</html>