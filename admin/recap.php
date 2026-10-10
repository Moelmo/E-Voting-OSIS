<?php
declare(strict_types=1); 
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') { 
    header('Location: ../index.php'); 
    exit; 
}

require __DIR__ . '/../config/database.php';

$candidates = $pdo->query('SELECT nomor_urut, nama_ketua, nama_wakil FROM kandidat ORDER BY nomor_urut ASC')->fetchAll();
$summary = $pdo->query("SELECT COUNT(*) AS total, COALESCE(SUM(sudah_voting = 1), 0) AS sudah_voting FROM siswa WHERE role = 'siswa'")->fetch() ?: ['total' => 0, 'sudah_voting' => 0];
$success = isset($_GET['success']) ? trim((string) $_GET['success']) : '';
$error = isset($_GET['error']) ? trim((string) $_GET['error']) : '';
$_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
?>
<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Suara | Admin</title>
    <script>tailwind = { theme: { extend: { fontFamily: { display: ['Plus Jakarta Sans', 'Inter', 'sans-serif'] } } } };</script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 antialiased selection:bg-cyan-500 selection:text-slate-950">
    <main class="flex min-h-screen w-full flex-col lg:h-screen lg:flex-row">
        <?php $activePage = 'recap'; require __DIR__ . '/_sidebar.php'; ?>
        
        <div class="min-w-0 flex-1 overflow-y-auto">
            <article class="mx-auto w-full max-w-7xl px-5 py-8 sm:px-8 lg:px-10 lg:py-10">
                <!-- Header Banner -->
                <header class="relative overflow-hidden rounded-3xl border border-white/10 bg-gradient-to-r from-slate-900 via-indigo-950/80 to-slate-900 p-7 sm:p-9 shadow-2xl backdrop-blur-xl">
                    <div class="absolute -right-10 -top-10 h-48 w-48 rounded-full bg-cyan-500/10 blur-3xl"></div>
                    <div class="relative z-10">
                        <div class="inline-flex items-center gap-2 rounded-full border border-violet-500/30 bg-violet-500/10 px-3 py-1 text-[11px] font-extrabold tracking-[0.2em] text-violet-300">
                            HASIL PEMILIHAN
                        </div>
                        <h1 class="mt-3 font-display text-3xl font-extrabold tracking-tight text-white sm:text-4xl">Rekapitulasi Suara</h1>
                        <p class="mt-2 text-sm text-slate-400">Pantau perolehan suara secara real-time dan kelola status pemilihan.</p>
                    </div>
                </header>

                <!-- Alert Success -->
                <?php if ($success !== ''): ?>
                    <div class="mt-6 rounded-2xl border border-emerald-500/30 bg-emerald-500/10 px-5 py-4 text-xs font-medium text-emerald-300 backdrop-blur-md" role="status">
                        ✓ <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>
                <?php if ($error !== ''): ?>
                    <div class="mt-6 rounded-2xl border border-red-500/30 bg-red-500/10 px-5 py-4 text-xs font-medium text-red-300 backdrop-blur-md" role="alert">
                        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <!-- Spoiler Toggle Bar -->
                <div class="my-6 flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-white/10 bg-slate-900/60 p-4 shadow-lg backdrop-blur-xl">
                    <div class="flex items-center gap-3">
                        <span class="relative flex h-3 w-3">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-cyan-400 opacity-75"></span>
                            <span class="relative inline-flex h-3 w-3 rounded-full bg-cyan-500"></span>
                        </span>
                        <div>
                            <p class="text-xs font-bold text-slate-200">Mode Proteksi Hasil Voting</p>
                            <p class="text-[11px] text-slate-400">Hasil disensor secara default dan membutuhkan akses password untuk dibuka.</p>
                        </div>
                    </div>
                    <button id="btn-spoiler" onclick="toggleSpoiler()" class="inline-flex items-center gap-2 rounded-xl border border-cyan-500/30 bg-cyan-500/20 px-4 py-2.5 text-xs font-bold text-cyan-300 transition-all hover:border-cyan-400 hover:bg-cyan-500/30 hover:text-white">
                        <span id="spoiler-icon">🔒</span>
                        <span id="spoiler-text">Buka Spoiler Hasil</span>
                    </button>
                </div>

                <!-- Main Grid Section -->
                <section class="grid gap-6 lg:grid-cols-[1.4fr_0.6fr]">
                    <!-- Realtime Chart -->
                    <div class="relative overflow-hidden rounded-3xl border border-white/10 bg-slate-900/60 p-6 shadow-xl backdrop-blur-xl sm:p-8">
                        <div class="flex items-center justify-between pb-4 border-b border-white/10">
                            <div>
                                <p class="text-[10px] font-extrabold tracking-[0.2em] text-cyan-400">LIVE MONITORING</p>
                                <h2 class="mt-1 font-display text-2xl font-bold text-white">Perolehan Paslon</h2>
                            </div>
                            <span class="rounded-full border border-emerald-500/30 bg-emerald-500/10 px-3 py-1 text-[11px] font-bold text-emerald-300">
                                Realtime
                            </span>
                        </div>

                        <!-- Chart Content dengan Masking '?' -->
                        <div class="relative mt-6 h-80">
                            <div id="chart-overlay" class="absolute inset-0 z-10 flex flex-col items-center justify-center rounded-2xl bg-slate-900/95 backdrop-blur-md transition-all duration-300">
                                <div class="grid h-16 w-16 place-items-center rounded-full border border-cyan-500/30 bg-cyan-500/10 text-3xl font-extrabold text-cyan-400 shadow-inner">
                                    ?
                                </div>
                                <p class="mt-3 text-xs font-semibold text-slate-400">Hasil Perolehan Terkunci</p>
                            </div>

                            <canvas id="recap-chart" aria-label="Grafik rekap suara"></canvas>
                        </div>
                    </div>

                    <!-- Partisipasi Siswa (Selalu Tampil) & Reset -->
                    <div class="flex flex-col justify-between rounded-3xl border border-white/10 bg-slate-900/60 p-6 shadow-xl backdrop-blur-xl sm:p-8">
                        <div>
                            <p class="text-[10px] font-extrabold tracking-[0.2em] text-sky-400">PARTISIPASI SISWA</p>
                            <div>
                                <strong class="mt-3 block font-display text-6xl font-extrabold text-white">
                                    <?= (int) $summary['total'] ? round(((int) $summary['sudah_voting'] / (int) $summary['total']) * 100) : 0 ?>%
                                </strong>
                                <p class="mt-2 text-xs text-slate-400">
                                    <span class="font-bold text-cyan-400"><?= (int) $summary['sudah_voting'] ?></span> dari <span class="font-bold text-slate-200"><?= (int) $summary['total'] ?></span> siswa telah memberikan suara.
                                </p>
                            </div>
                        </div>

                        <form class="mt-8 border-t border-white/10 pt-6" action="reset.php" method="post" onsubmit="return confirm('PERINGATAN! Tindakan ini akan menghapus semua suara masuk dan mengembalikan status siswa menjadi belum memilih. Lanjutkan?');">
                            <button class="w-full rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-xs font-bold text-red-400 transition-all hover:border-red-500/50 hover:bg-red-600 hover:text-white" type="submit">
                                🗑️ Reset Semua Voting
                            </button>
                        </form>
                    </div>
                </section>

                <!-- Detailed List Section -->
                <section class="mt-6 rounded-3xl border border-white/10 bg-slate-900/60 p-6 shadow-xl backdrop-blur-xl sm:p-8">
                    <h3 class="mb-4 text-xs font-bold tracking-wider text-slate-400">Rincian Perolehan Suara</h3>
                    <div class="divide-y divide-white/10">
                        <?php foreach ($candidates as $candidate): ?>
                            <div class="flex items-center gap-4 py-4">
                                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl border border-cyan-500/30 bg-cyan-500/10 font-display text-xs font-extrabold text-cyan-300">
                                    <?= (int) $candidate['nomor_urut'] ?>
                                </span>
                                <div class="flex-1 min-w-0">
                                    <strong class="block truncate text-sm text-white">
                                        <?= htmlspecialchars($candidate['nama_ketua'], ENT_QUOTES, 'UTF-8') ?>
                                    </strong>
                                    <span class="text-xs text-slate-400">
                                        &amp; <?= htmlspecialchars($candidate['nama_wakil'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </div>
                                <div>
                                    <strong class="text-base font-extrabold text-cyan-400">
                                        <span class="spoiler-val hidden" data-candidate-number="<?= (int) $candidate['nomor_urut'] ?>"></span>
                                        <span class="spoiler-mask inline-block w-6 text-center font-black">?</span>
                                        <span class="text-xs font-normal text-slate-400">suara</span>
                                    </strong>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                <section class="mt-6 rounded-3xl border border-white/10 bg-slate-900/60 p-6 shadow-xl backdrop-blur-xl sm:p-8">
                    <h3 class="text-sm font-bold text-white">Ubah Password Spoiler</h3>
                    <p class="mt-1 text-xs text-slate-400">Password awal: <span class="font-bold text-slate-200">admin</span>. Ganti agar hasil rekap hanya dapat dibuka oleh panitia.</p>
                    <form class="mt-5 grid gap-4 sm:grid-cols-2" action="recap_password_update.php" method="post">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                        <label class="text-xs font-semibold text-slate-300">
                            Password saat ini
                            <input class="mt-2 w-full rounded-xl border border-white/10 bg-slate-950 px-4 py-2.5 text-sm text-white outline-none focus:border-cyan-500/50" type="password" name="current_password" autocomplete="current-password" required>
                        </label>
                        <label class="text-xs font-semibold text-slate-300">
                            Password baru
                            <input class="mt-2 w-full rounded-xl border border-white/10 bg-slate-950 px-4 py-2.5 text-sm text-white outline-none focus:border-cyan-500/50" type="password" name="new_password" minlength="8" autocomplete="new-password" required>
                        </label>
                        <label class="text-xs font-semibold text-slate-300 sm:col-span-2">
                            Konfirmasi password baru
                            <input class="mt-2 w-full rounded-xl border border-white/10 bg-slate-950 px-4 py-2.5 text-sm text-white outline-none focus:border-cyan-500/50 sm:max-w-md" type="password" name="confirm_password" minlength="8" autocomplete="new-password" required>
                        </label>
                        <div class="sm:col-span-2">
                            <button class="rounded-xl bg-cyan-500 px-4 py-2.5 text-xs font-bold text-slate-950 transition hover:bg-cyan-400" type="submit">Simpan Password Baru</button>
                        </div>
                    </form>
                </section>
            </article>
        </div>
    </main>

    <!-- Modal Custom Password Admin -->
    <div id="password-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/80 p-4 backdrop-blur-md">
        <div class="w-full max-w-sm rounded-3xl border border-white/10 bg-slate-900 p-6 shadow-2xl transition-all">
            <div class="flex items-center justify-between pb-3">
                <div class="flex items-center gap-2">
                    <span class="text-lg">🔑</span>
                    <h3 class="font-display text-base font-bold text-white">Verifikasi Password</h3>
                </div>
                <button type="button" onclick="closeModal()" class="text-slate-400 hover:text-white">&times;</button>
            </div>
            <p class="text-xs text-slate-400">Masukkan password admin untuk membuka proteksi sensor hasil voting.</p>
            
            <form id="form-password" onsubmit="handlePasswordSubmit(event)" class="mt-4 space-y-4">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                <div>
                    <input type="password" id="input-password" placeholder="Masukkan password..." class="w-full rounded-xl border border-white/10 bg-slate-950 px-4 py-2.5 text-sm text-white placeholder-slate-500 outline-none focus:border-cyan-500/50 focus:ring-1 focus:ring-cyan-500/50" autofocus />
                    <p id="password-error" class="mt-2 hidden text-[11px] font-semibold text-red-400">⚠️ Password salah! Silakan coba lagi.</p>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="closeModal()" class="rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-xs font-semibold text-slate-300 hover:bg-white/10">Batal</button>
                    <button type="submit" class="rounded-xl bg-cyan-500 px-4 py-2 text-xs font-bold text-slate-950 hover:bg-cyan-400">Buka Hasil</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let isRevealed = false;

        const modal = document.getElementById('password-modal');
        const passInput = document.getElementById('input-password');
        const passError = document.getElementById('password-error');

        function toggleSpoiler() {
            if (!isRevealed) {
                openModal();
            } else {
                setSpoilerState(false);
            }
        }

        function openModal() {
            passInput.value = '';
            passError.classList.add('hidden');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            setTimeout(() => passInput.focus(), 100);
        }

        function closeModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        async function handlePasswordSubmit(e) {
            e.preventDefault();

            try {
                const response = await fetch('recap_verify.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        password: passInput.value,
                        csrf_token: document.querySelector('#form-password [name="csrf_token"]').value
                    })
                });
                const result = await response.json();

                if (!response.ok || !result.success) {
                    passError.classList.remove('hidden');
                    passInput.focus();
                    passInput.select();
                    return;
                }

                chart.data.datasets[0].data = result.candidates.map(candidate => candidate.jumlah_suara);
                chart.update();
                document.querySelectorAll('.spoiler-val').forEach(element => {
                    const candidate = result.candidates.find(item => item.nomor_urut === Number(element.dataset.candidateNumber));
                    element.textContent = candidate ? candidate.jumlah_suara : '0';
                });
                closeModal();
                setSpoilerState(true);
            } catch (error) {
                passError.classList.remove('hidden');
            }
        }

        function setSpoilerState(state) {
            isRevealed = state;
            const overlay = document.getElementById('chart-overlay');
            if (isRevealed) {
                overlay.classList.add('hidden');
            } else {
                overlay.classList.remove('hidden');
            }

            document.querySelectorAll('.spoiler-val').forEach(el => el.classList.toggle('hidden', !isRevealed));
            document.querySelectorAll('.spoiler-mask').forEach(el => el.classList.toggle('hidden', isRevealed));

            document.getElementById('spoiler-text').innerText = isRevealed ? 'Sembunyikan Hasil' : 'Buka Spoiler Hasil';
            document.getElementById('spoiler-icon').innerText = isRevealed ? '🙈' : '🔒';
        }

        // Close modal via ESC key
        window.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                closeModal();
            }
        });

        // Chart.js Configuration
        const chart = new Chart(document.querySelector('#recap-chart'), {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_map(static fn (array $candidate): string => 'Paslon No. '.$candidate['nomor_urut'], $candidates)) ?>,
                datasets: [{
                    data: <?= json_encode(array_fill(0, count($candidates), 0)) ?>,
                    backgroundColor: ['#22d3ee', '#a855f7', '#f43f5e', '#fbbf24'],
                    borderWidth: 0,
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#94a3b8',
                            font: {
                                family: 'Plus Jakarta Sans',
                                size: 12
                            },
                            padding: 16
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>