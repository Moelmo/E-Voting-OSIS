<?php
declare(strict_types=1);
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

require __DIR__ . '/../config/database.php';
$branding = $pdo->query('SELECT teks_copyright FROM pengaturan_tampilan WHERE id = 1')->fetch();
$summary = $pdo->query("SELECT COUNT(*) AS total, COALESCE(SUM(sudah_voting = 1), 0) AS sudah_voting FROM siswa WHERE role = 'siswa'")->fetch() ?: ['total' => 0, 'sudah_voting' => 0];
$candidateCount = (int) $pdo->query('SELECT COUNT(*) FROM kandidat')->fetchColumn();
$error = isset($_GET['error']) ? trim((string) $_GET['error']) : '';
$success = isset($_GET['success']) ? trim((string) $_GET['success']) : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Overview | Admin E-Voting</title>
    <script>tailwind = { theme: { extend: { fontFamily: { display: ['Georgia', 'serif'] } } } };</script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <style>
        @keyframes float-slow {
            0%, 100% { transform: translateY(0px) scale(1); }
            50% { transform: translateY(-10px) scale(1.03); }
        }
        .animate-float { animation: float-slow 6s ease-in-out infinite; }
    </style>
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 antialiased selection:bg-cyan-500 selection:text-white">
    <main class="flex min-h-screen w-full flex-col lg:h-screen lg:flex-row">
        <?php $activePage = 'overview'; require __DIR__ . '/_sidebar.php'; ?>
        <div class="relative min-w-0 flex-1 overflow-y-auto">
            <!-- Background Ambient Glow -->
            <div class="pointer-events-none fixed -top-20 -left-20 h-96 w-96 rounded-full bg-blue-600/20 blur-[120px]"></div>
            <div class="pointer-events-none fixed top-1/3 -right-20 h-96 w-96 rounded-full bg-cyan-500/15 blur-[120px]"></div>

            <article class="relative z-10 mx-auto w-full max-w-7xl px-5 py-8 sm:px-8 lg:px-10 lg:py-10">
                <!-- Header Banner -->
                <header class="relative overflow-hidden rounded-3xl border border-white/10 bg-gradient-to-r from-slate-900 via-blue-950 to-slate-900 p-7 shadow-2xl backdrop-blur-xl sm:p-9">
                    <div class="pointer-events-none absolute -right-10 -bottom-10 h-64 w-64 rounded-full bg-cyan-500/20 blur-[80px] animate-float"></div>
                    <div class="relative z-10 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                        <div>
                            <div class="inline-flex items-center gap-2 rounded-full border border-cyan-500/30 bg-cyan-500/10 px-3 py-1 text-[11px] font-extrabold tracking-[0.2em] text-cyan-300">
                                <span class="h-1.5 w-1.5 rounded-full bg-cyan-400 animate-pulse"></span>
                                PANEL PANITIA
                            </div>
                            <h1 class="mt-3 font-display text-3xl font-bold tracking-tight text-white sm:text-4xl lg:text-5xl">Overview voting.</h1>
                            <p class="mt-2 text-sm text-slate-400">Ringkasan cepat kondisi pemilihan hari ini secara real-time.</p>
                        </div>
                    </div>
                </header>

                <!-- Alerts -->
                <?php if ($error !== ''): ?>
                    <div class="mt-6 rounded-2xl border border-red-500/30 bg-red-500/10 px-5 py-4 text-xs font-semibold text-red-400 backdrop-blur-md" role="alert">
                        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>
                <?php if ($success !== ''): ?>
                    <div class="mt-6 rounded-2xl border border-emerald-500/30 bg-emerald-500/10 px-5 py-4 text-xs font-semibold text-emerald-400 backdrop-blur-md" role="status">
                        <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <!-- Summary Cards -->
                <section class="mt-6 grid gap-5 sm:grid-cols-3" aria-label="Ringkasan voting">
                    <div class="group relative overflow-hidden rounded-3xl border border-white/10 bg-gradient-to-br from-blue-900/50 to-slate-900/80 p-6 shadow-xl backdrop-blur-xl transition-all duration-300 hover:border-blue-500/40 hover:-translate-y-1">
                        <div class="pointer-events-none absolute -right-6 -bottom-6 h-28 w-28 rounded-full bg-blue-500/20 blur-2xl transition-all group-hover:scale-150"></div>
                        <span class="text-xs font-bold tracking-wider text-blue-300">SUDAH MEMILIH</span>
                        <strong class="mt-3 block font-display text-5xl font-extrabold text-white sm:text-6xl"><?= (int) $summary['sudah_voting'] ?></strong>
                        <div class="mt-3 flex items-center gap-1.5 text-[11px] text-blue-400">
                            <span class="inline-block h-2 w-2 rounded-full bg-cyan-400"></span>
                            <span>Suara masuk</span>
                        </div>
                    </div>

                    <div class="group relative overflow-hidden rounded-3xl border border-white/10 bg-gradient-to-br from-cyan-900/40 to-slate-900/80 p-6 shadow-xl backdrop-blur-xl transition-all duration-300 hover:border-cyan-500/40 hover:-translate-y-1">
                        <div class="pointer-events-none absolute -right-6 -bottom-6 h-28 w-28 rounded-full bg-cyan-500/20 blur-2xl transition-all group-hover:scale-150"></div>
                        <span class="text-xs font-bold tracking-wider text-cyan-300">TOTAL PEMILIH</span>
                        <strong class="mt-3 block font-display text-5xl font-extrabold text-white sm:text-6xl"><?= (int) $summary['total'] ?></strong>
                        <div class="mt-3 flex items-center gap-1.5 text-[11px] text-cyan-400">
                            <span class="inline-block h-2 w-2 rounded-full bg-blue-400"></span>
                            <span>Siswa terdaftar</span>
                        </div>
                    </div>

                    <div class="group relative overflow-hidden rounded-3xl border border-white/10 bg-gradient-to-br from-violet-900/40 to-slate-900/80 p-6 shadow-xl backdrop-blur-xl transition-all duration-300 hover:border-violet-500/40 hover:-translate-y-1">
                        <div class="pointer-events-none absolute -right-6 -bottom-6 h-28 w-28 rounded-full bg-violet-500/20 blur-2xl transition-all group-hover:scale-150"></div>
                        <span class="text-xs font-bold tracking-wider text-violet-300">PASANGAN CALON</span>
                        <strong class="mt-3 block font-display text-5xl font-extrabold text-white sm:text-6xl"><?= $candidateCount ?></strong>
                        <div class="mt-3 flex items-center gap-1.5 text-[11px] text-violet-400">
                            <span class="inline-block h-2 w-2 rounded-full bg-violet-400"></span>
                            <span>Kandidat OSIS</span>
                        </div>
                    </div>
                </section>

                <!-- Section: Chart & Quick Access -->
                <section class="mt-6 grid gap-5 lg:grid-cols-[1.35fr_0.65fr]">
                    <!-- Progress Chart -->
                    <div class="relative overflow-hidden rounded-3xl border border-white/10 bg-slate-900/80 p-6 shadow-2xl backdrop-blur-xl sm:p-8">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-[11px] font-extrabold tracking-[0.2em] text-cyan-400">PARTISIPASI</span>
                                <h2 class="mt-1 font-display text-2xl font-bold text-white sm:text-3xl">Progress pemilih</h2>
                            </div>
                            <span class="inline-flex items-center gap-2 rounded-full border border-emerald-500/30 bg-emerald-500/10 px-3 py-1 text-xs font-bold text-emerald-400 backdrop-blur-md">
                                <span class="relative flex h-2 w-2">
                                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                  <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                </span>
                                Live
                            </span>
                        </div>
                        <div class="relative mt-6 h-72">
                            <canvas id="overview-chart" aria-label="Grafik partisipasi pemilih"></canvas>
                        </div>
                    </div>

                    <!-- Quick Action Links -->
                    <div class="relative flex flex-col justify-between overflow-hidden rounded-3xl border border-white/10 bg-slate-900/90 p-7 shadow-2xl backdrop-blur-xl">
                        <div>
                            <span class="text-[11px] font-extrabold tracking-[0.2em] text-cyan-400">AKSES CEPAT</span>
                            <h2 class="mt-1 font-display text-2xl font-bold text-white sm:text-3xl">Kelola pemilihan.</h2>
                        </div>
                        <div class="mt-6 space-y-3">
                            <a class="group flex items-center justify-between rounded-2xl bg-gradient-to-r from-blue-600 via-cyan-600 to-blue-600 bg-[length:200%_auto] px-5 py-4 text-xs font-bold text-white shadow-lg shadow-cyan-600/20 transition-all duration-300 hover:bg-[right_center] hover:shadow-cyan-500/30" href="students.php">
                                <span>Kelola data siswa</span>
                                <span class="transition-transform group-hover:translate-x-1">→</span>
                            </a>
                            <a class="group flex items-center justify-between rounded-2xl border border-white/10 bg-slate-800/80 px-5 py-4 text-xs font-bold text-slate-200 transition-all duration-300 hover:border-cyan-500/50 hover:bg-slate-800 hover:text-white" href="candidates.php">
                                <span>Edit paslon</span>
                                <span class="transition-transform group-hover:translate-x-1">→</span>
                            </a>
                            <a class="group flex items-center justify-between rounded-2xl border border-white/10 bg-slate-800/80 px-5 py-4 text-xs font-bold text-slate-200 transition-all duration-300 hover:border-cyan-500/50 hover:bg-slate-800 hover:text-white" href="recap.php">
                                <span>Buka rekap suara</span>
                                <span class="transition-transform group-hover:translate-x-1">→</span>
                            </a>
                        </div>
                    </div>
                </section>

                <section id="branding" class="mt-8 grid gap-4 md:grid-cols-2" aria-label="Pengaturan identitas">
                    <a class="group rounded-2xl border border-white/10 bg-slate-900/80 p-6 shadow-xl transition hover:border-cyan-500/40" href="logos.php">
                        <span class="text-[11px] font-extrabold tracking-[0.2em] text-cyan-400">IDENTITAS VISUAL</span>
                        <h2 class="mt-2 font-display text-xl font-bold text-white">Kelola tiga logo</h2>
                        <p class="mt-2 text-xs leading-5 text-slate-400">Atur posisi, status aktif, unggah gambar, dan crop setiap logo.</p>
                    </a>
                    <a class="group rounded-2xl border border-white/10 bg-slate-900/80 p-6 shadow-xl transition hover:border-cyan-500/40" href="copyright.php">
                        <span class="text-[11px] font-extrabold tracking-[0.2em] text-cyan-400">TEKS FOOTER</span>
                        <h2 class="mt-2 font-display text-xl font-bold text-white">Kelola copyright</h2>
                        <p class="mt-2 text-xs leading-5 text-slate-400">Ubah teks yang ditampilkan pada footer halaman pemilih.</p>
                    </a>
                </section>
                <footer class="mt-8 border-t border-white/10 py-5 text-center text-xs text-slate-500">
                    &copy; <?= date('Y') ?> <?= htmlspecialchars($branding['teks_copyright'], ENT_QUOTES, 'UTF-8') ?>
                </footer>
            </article>
        </div>
    </main>
    <script>
        new Chart(document.querySelector('#overview-chart'), { 
            type: 'doughnut', 
            data: { 
                labels: ['Sudah memilih', 'Belum memilih'], 
                datasets: [{ 
                    data: [<?= (int) $summary['sudah_voting'] ?>, <?= max(0, (int) $summary['total'] - (int) $summary['sudah_voting']) ?>], 
                    backgroundColor: ['#22d3ee', '#334155'], 
                    borderWidth: 0,
                    hoverOffset: 4
                }] 
            }, 
            options: { 
                responsive: true, 
                maintainAspectRatio: false, 
                cutout: '72%', 
                plugins: { 
                    legend: { 
                        position: 'bottom',
                        labels: {
                            color: '#94a3b8',
                            font: { size: 12 },
                            padding: 20
                        }
                    } 
                } 
            } 
        });
    </script>
</body>
</html>