<?php

declare(strict_types=1);

session_start();

if (isset($_SESSION['user'])) {
    $destination = $_SESSION['user']['role'] === 'admin' ? 'admin/dashboard.php' : 'voting.php';
    header("Location: {$destination}");
    exit;
}

require __DIR__ . '/config/database.php';
$branding = $pdo->query('SELECT * FROM pengaturan_tampilan WHERE id = 1')->fetch();

$error = isset($_GET['error']) ? trim((string) $_GET['error']) : '';
$success = isset($_GET['success']) ? trim((string) $_GET['success']) : '';

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk | E-Voting OSIS</title>
    <script>
        tailwind = {
            theme: {
                extend: {
                    fontFamily: { display: ['Georgia', 'serif'] },
                },
            },
        };
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js" defer></script>
    <script src="assets/js/app.js?v=3" defer></script>
    <style>
        @keyframes scan-line {
            0%, 100% { top: 10%; }
            50% { top: 85%; }
        }
        .animate-scan {
            animation: scan-line 2.5s ease-in-out infinite;
        }
        @keyframes float-slow {
            0%, 100% { transform: translateY(0px) scale(1); }
            50% { transform: translateY(-15px) scale(1.05); }
        }
        .animate-float {
            animation: float-slow 6s ease-in-out infinite;
        }
    </style>
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 antialiased selection:bg-cyan-500 selection:text-white">
    <main class="grid min-h-screen grid-cols-1 lg:grid-cols-[12fr_9fr]">
        <!-- Area Scanner (Kiri) -->
        <section class="relative flex min-h-[65vh] flex-col justify-between overflow-hidden bg-slate-950 p-6 sm:p-10 lg:min-h-screen lg:p-12" aria-labelledby="page-title">
            <!-- Background Ambient Glow & Effects -->
            <div class="pointer-events-none absolute -left-20 -top-20 h-96 w-96 rounded-full bg-blue-600/30 blur-[120px]"></div>
            <div class="pointer-events-none absolute -bottom-20 -right-20 h-96 w-96 rounded-full bg-cyan-500/20 blur-[120px]"></div>
            <div class="pointer-events-none absolute top-1/2 left-1/2 h-80 w-80 -translate-x-1/2 -translate-y-1/2 rounded-full bg-indigo-600/20 blur-[100px] animate-float"></div>

            <!-- Header OSIS -->
            <div class="relative z-10 flex items-center gap-4">
                <?php if (!empty($branding['logo_1']) && (int) $branding['logo_1_aktif'] === 1): ?>
                    <div class="flex h-14 shrink-0 items-center gap-2 rounded-2xl border border-white/10 bg-white/5 px-3">
                        <img class="h-10 w-10 object-contain" src="assets/<?= htmlspecialchars($branding['logo_1'], ENT_QUOTES, 'UTF-8') ?>" alt="Logo 1">
                    </div>
                <?php else: ?>
                    <div class="relative grid h-14 w-14 shrink-0 place-items-center rounded-2xl border border-cyan-400/30 bg-gradient-to-br from-cyan-500/20 to-blue-600/30 font-black tracking-widest text-cyan-300 shadow-lg shadow-cyan-500/10 backdrop-blur-md">
                        <span class="text-sm">OSIS</span>
                        <span class="absolute -top-1 -right-1 flex h-3 w-3">
                          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-cyan-400 opacity-75"></span>
                          <span class="relative inline-flex rounded-full h-3 w-3 bg-cyan-500"></span>
                        </span>
                    </div>
                <?php endif; ?>
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full border border-cyan-500/30 bg-cyan-500/10 px-3 py-0.5 text-[10px] font-extrabold tracking-[0.2em] text-cyan-300 backdrop-blur-sm">
                        <span class="h-1.5 w-1.5 rounded-full bg-cyan-400 animate-pulse"></span>
                        PEMILIHAN KETUA OSIS
                    </div>
                    <h1 id="page-title" class="mt-1 font-display text-2xl font-bold tracking-tight text-white sm:text-3xl lg:text-4xl">Scan kartu pelajar kamu.</h1>
                </div>
            </div>

            <!-- Scanner Box -->
            <div id="scanner-panel" class="relative my-6 flex min-h-[360px] flex-1 flex-col items-center justify-center overflow-hidden rounded-3xl border border-white/15 bg-slate-900/60 p-1 shadow-2xl backdrop-blur-xl group transition-all [&_video]:block [&_video]:h-full [&_video]:max-h-none [&_video]:min-h-[360px] [&_video]:w-full [&_video]:rounded-2xl [&_video]:object-cover">
                <!-- Frame HUD Corners -->
                <div class="pointer-events-none absolute inset-4 z-20 rounded-2xl border border-white/10">
                    <div class="absolute top-0 left-0 h-6 w-6 border-t-2 border-l-2 border-cyan-400"></div>
                    <div class="absolute top-0 right-0 h-6 w-6 border-t-2 border-r-2 border-cyan-400"></div>
                    <div class="absolute bottom-0 left-0 h-6 w-6 border-b-2 border-l-2 border-cyan-400"></div>
                    <div class="absolute bottom-0 right-0 h-6 w-6 border-b-2 border-r-2 border-cyan-400"></div>
                    <!-- Laser Line Effect -->
                    <div class="animate-scan absolute left-2 right-2 h-0.5 bg-gradient-to-r from-transparent via-cyan-400 to-transparent shadow-[0_0_15px_#22d3ee]"></div>
                </div>

                <div id="qr-reader" class="h-full w-full overflow-hidden rounded-2xl" aria-label="Pemindai barcode kamera"></div>
                
                <div class="absolute bottom-4 z-20 rounded-full border border-white/10 bg-slate-950/80 px-4 py-1.5 backdrop-blur-md shadow-lg">
                    <p id="scanner-status" class="text-center text-xs font-medium text-cyan-200" role="status">Menyiapkan kamera...</p>
                </div>
            </div>

            <!-- Footer Info Kiri -->
            <div class="relative z-10 flex items-center gap-3 text-xs text-slate-400">
                <span class="flex h-2 w-2 rounded-full bg-emerald-400"></span>
                <p class="max-w-md leading-5">Arahkan barcode NIS ke dalam bingkai. Kamera akan membaca barcode secara otomatis.</p>
            </div>
            <p class="relative z-10 mt-3 text-[11px] text-slate-500">&copy; <?= date('Y') ?> <?= htmlspecialchars((string) ($branding['teks_copyright'] ?? 'E-Voting OSIS. Hak cipta dilindungi.'), ENT_QUOTES, 'UTF-8') ?></p>
        </section>

        <!-- Area Form Input NIS (Kanan) -->
        <section class="relative flex min-h-[35vh] flex-col justify-center border-t border-white/10 bg-slate-900/90 p-6 backdrop-blur-2xl sm:p-10 lg:min-h-screen lg:border-t-0 lg:border-l lg:p-12" aria-label="Form masuk">
            <?php $loginLogoSlots = range(1, 3); ?>
            <?php if (array_filter($loginLogoSlots, static fn (int $slot): bool => !empty($branding['logo_' . $slot]) && (int) $branding['logo_' . $slot . '_aktif'] === 1) !== []): ?>
                <div class="relative z-10 mx-auto mb-8 grid w-full max-w-md grid-cols-3 justify-items-center gap-4" aria-label="Logo penyelenggara">
                    <?php foreach ($loginLogoSlots as $slot): ?>
                        <div class="flex h-16 w-16 items-center justify-center sm:h-20 sm:w-20">
                            <?php if (!empty($branding['logo_' . $slot]) && (int) $branding['logo_' . $slot . '_aktif'] === 1): ?>
                                <img class="h-full w-full object-contain" src="assets/<?= htmlspecialchars($branding['logo_' . $slot], ENT_QUOTES, 'UTF-8') ?>" alt="Logo <?= $slot ?>">
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class="relative z-10">
                <span class="inline-block rounded-lg bg-blue-500/10 px-3 py-1 text-[11px] font-extrabold tracking-[0.2em] text-blue-400">
                    AKSES PEMILIH
                </span>
                <h2 class="mt-3 font-display text-3xl font-bold leading-tight text-white sm:text-4xl">Barcode tidak tersedia?</h2>
                <p class="mt-3 max-w-sm text-sm leading-relaxed text-slate-400">Masukkan NIS secara manual untuk melanjutkan ke bilik suara digital.</p>
            </div>

            <!-- Alert Messages -->
            <?php if ($error !== ''): ?>
                <div data-auto-dismiss class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 px-5 backdrop-blur-md transition-opacity duration-500" role="alert">
                    <div class="w-full max-w-sm rounded-3xl border border-red-500/30 bg-slate-900 p-8 text-center shadow-2xl shadow-red-950/50">
                        <div class="mx-auto grid h-20 w-20 animate-pulse place-items-center rounded-full bg-red-500/10 text-5xl font-light text-red-500 border border-red-500/20" aria-hidden="true">×</div>
                        <p class="mt-5 text-lg font-bold text-white">Voting tidak dapat diproses</p>
                        <p class="mt-2 text-sm leading-6 text-red-400"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                </div>
            <?php endif; ?>
            <?php if ($success !== ''): ?>
                <div data-auto-dismiss class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 px-5 backdrop-blur-md transition-opacity duration-500" role="status">
                    <div class="w-full max-w-sm rounded-3xl border border-emerald-500/30 bg-slate-900 p-8 text-center shadow-2xl shadow-emerald-950/50">
                        <div class="mx-auto grid h-20 w-20 animate-bounce place-items-center rounded-full bg-emerald-500/10 text-5xl font-bold text-emerald-400 border border-emerald-500/20" aria-hidden="true">✓</div>
                        <p class="mt-5 text-lg font-bold text-white">Voting berhasil</p>
                        <p class="mt-2 text-sm leading-6 text-emerald-400"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Manual Toggle Button -->
            <button id="manual-toggle" class="group mt-8 flex min-h-12 w-full items-center justify-center gap-3 rounded-2xl border border-white/10 bg-slate-800/80 px-5 text-xs font-bold text-white shadow-lg transition-all duration-300 hover:border-cyan-500/50 hover:bg-slate-800 hover:shadow-cyan-500/10 focus:outline-none focus:ring-2 focus:ring-cyan-500/50" type="button" aria-expanded="false" aria-controls="login-form">
                <span class="text-base text-cyan-400 transition-transform group-hover:scale-110" aria-hidden="true">⌨</span>
                <span>Input NIS Secara Manual</span>
            </button>

            <!-- Form Input Manual -->
            <form id="login-form" class="mt-6 transition-all duration-300" action="login_process.php" method="post" hidden>
                <label class="mb-2 block text-xs font-bold tracking-wider text-slate-300" for="nis">NOMOR INDUK SISWA (NIS)</label>
                <div class="group relative flex items-center overflow-hidden rounded-2xl border border-white/10 bg-slate-950/60 transition-all focus-within:border-cyan-400 focus-within:ring-2 focus-within:ring-cyan-400/20">
                    <span class="pl-4 text-xs font-extrabold tracking-widest text-cyan-400" aria-hidden="true">NIS</span>
                    <input class="w-full border-0 bg-transparent px-3 py-4 text-base font-semibold text-white outline-none placeholder:text-slate-600 focus:ring-0" id="nis" name="nis" type="text" inputmode="numeric" autocomplete="username" placeholder="Masukkan NIS kamu..." required>
                </div>
                <button class="group mt-5 flex min-h-12 w-full items-center justify-between rounded-2xl bg-gradient-to-r from-blue-600 via-cyan-600 to-blue-600 bg-[length:200%_auto] px-6 text-xs font-bold text-white shadow-xl shadow-cyan-600/20 transition-all duration-300 hover:bg-[right_center] hover:shadow-cyan-500/30 focus:outline-none focus:ring-2 focus:ring-cyan-400" type="submit">
                    <span>MASUK DENGAN NIS</span>
                    <span class="text-base transition-transform group-hover:translate-x-1" aria-hidden="true">→</span>
                </button>
            </form>

            <p class="mt-10 text-center text-xs leading-5 text-slate-500">
                Belum terdaftar atau NIS bermasalah? <br>
                <span class="text-slate-400 font-medium">Hubungi panitia atau teknisi e-voting.</span>
            </p>
        </section>
    </main>

    <script>
        document.querySelectorAll('[data-auto-dismiss]').forEach((alert) => {
            setTimeout(() => {
                alert.classList.add('opacity-0');
                setTimeout(() => alert.remove(), 500);
            }, 3000);
        });
    </script>
</body>
</html>