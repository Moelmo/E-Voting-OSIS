<?php

declare(strict_types=1);
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

require __DIR__ . '/../config/database.php';
$branding = $pdo->query('SELECT teks_copyright FROM pengaturan_tampilan WHERE id = 1')->fetch();
if ($branding === false) {
    throw new RuntimeException('Pengaturan copyright tidak ditemukan.');
}
$_SESSION['copyright_token'] ??= bin2hex(random_bytes(32));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['copyright_token'], (string) ($_POST['csrf_token'] ?? ''))) {
        header('Location: copyright.php?error=' . rawurlencode('Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.'));
        exit;
    }

    $copyright = trim((string) ($_POST['teks_copyright'] ?? ''));
    $characterCount = preg_match_all('/./us', $copyright, $matches);
    if ($copyright === '' || $characterCount === false || $characterCount > 200) {
        header('Location: copyright.php?error=' . rawurlencode('Teks wajib diisi dan maksimal 200 karakter.'));
        exit;
    }

    $statement = $pdo->prepare('UPDATE pengaturan_tampilan SET teks_copyright = :copyright WHERE id = 1');
    $statement->execute(['copyright' => $copyright]);
    unset($_SESSION['copyright_token']);
    header('Location: copyright.php?success=' . rawurlencode('Teks copyright berhasil disimpan.'));
    exit;
}

$error = trim((string) ($_GET['error'] ?? ''));
$success = trim((string) ($_GET['success'] ?? ''));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Copyright | Admin</title>
    <script>tailwind = { theme: { extend: { fontFamily: { display: ['Georgia', 'serif'] } } } };</script>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 antialiased selection:bg-cyan-500 selection:text-white">
    <main class="flex min-h-screen w-full flex-col lg:h-screen lg:flex-row">
        <?php $activePage = 'copyright'; require __DIR__ . '/_sidebar.php'; ?>
        <div class="min-w-0 flex-1 overflow-y-auto">
            <article class="mx-auto w-full max-w-4xl px-5 py-8 sm:px-8 lg:px-10 lg:py-10">
                <header class="rounded-3xl border border-white/10 bg-gradient-to-r from-slate-900 via-blue-950 to-slate-900 p-7 shadow-2xl sm:p-9">
                    <p class="text-[11px] font-extrabold tracking-[0.2em] text-cyan-300">IDENTITAS PEMILIHAN</p>
                    <h1 class="mt-3 font-display text-3xl font-bold text-white sm:text-4xl">Pengaturan copyright</h1>
                    <p class="mt-2 text-sm text-slate-400">Teks tampil pada footer halaman masuk dan bilik voting.</p>
                </header>

                <?php if ($error !== ''): ?><p class="mt-5 rounded-xl border border-red-500/30 bg-red-500/10 p-4 text-sm text-red-300" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
                <?php if ($success !== ''): ?><p class="mt-5 rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-4 text-sm text-emerald-300" role="status"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>

                <section class="mt-6 rounded-2xl border border-white/10 bg-slate-900/80 p-6 shadow-xl sm:p-8">
                    <form action="copyright.php" method="post">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['copyright_token'], ENT_QUOTES, 'UTF-8') ?>">
                        <label class="block text-sm font-bold text-slate-200" for="teks-copyright">Teks copyright</label>
                        <input id="teks-copyright" class="mt-3 w-full rounded-xl border border-white/10 bg-slate-950 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400" name="teks_copyright" maxlength="200" value="<?= htmlspecialchars($branding['teks_copyright'], ENT_QUOTES, 'UTF-8') ?>" required>
                        <p class="mt-2 text-xs text-slate-500">Maksimal 200 karakter.</p>
                        <button class="mt-6 rounded-xl bg-cyan-500 px-5 py-3.5 text-xs font-bold text-slate-950 shadow-lg shadow-cyan-500/20 transition hover:bg-cyan-400" type="submit">Simpan copyright</button>
                    </form>
                </section>
            </article>
        </div>
    </main>
</body>
</html>
