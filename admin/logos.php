<?php

declare(strict_types=1);
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

require __DIR__ . '/../config/database.php';
$logos = $pdo->query('SELECT * FROM pengaturan_tampilan WHERE id = 1')->fetch();
if ($logos === false) {
    throw new RuntimeException('Pengaturan logo tidak ditemukan.');
}
$_SESSION['logos_token'] ??= bin2hex(random_bytes(32));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newFiles = [];
    $uploadDirectory = __DIR__ . '/../assets/uploads';
    try {
        if (!hash_equals($_SESSION['logos_token'], (string) ($_POST['csrf_token'] ?? ''))) {
            throw new RuntimeException('Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.');
        }

        $values = [];
        for ($slot = 1; $slot <= 3; $slot++) {
            $pathKey = 'logo_' . $slot;
            $values[$pathKey] = $logos[$pathKey];
            $values[$pathKey . '_aktif'] = isset($_POST[$pathKey . '_aktif']) ? 1 : 0;
            $cropData = (string) ($_POST['logo_crops'][$slot] ?? '');

            if ($cropData !== '') {
                if (!preg_match('/^data:image\/png;base64,([A-Za-z0-9+\/=]+)$/', $cropData, $matches) || strlen($matches[1]) > 4 * 1024 * 1024) {
                    throw new RuntimeException('Hasil crop logo ' . $slot . ' tidak valid atau terlalu besar.');
                }
                $imageData = base64_decode($matches[1], true);
                $imageInfo = $imageData === false ? false : getimagesizefromstring($imageData);
                if ($imageInfo === false || $imageInfo['mime'] !== 'image/png' || $imageInfo[0] > 1200 || $imageInfo[1] > 1200 || strlen($imageData) > 3 * 1024 * 1024) {
                    throw new RuntimeException('Hasil crop logo ' . $slot . ' tidak valid atau terlalu besar.');
                }
                if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0775, true) && !is_dir($uploadDirectory)) {
                    throw new RuntimeException('Folder upload logo tidak dapat dibuat.');
                }
                if (!is_writable($uploadDirectory)) {
                    throw new RuntimeException('Folder upload logo tidak memiliki izin tulis.');
                }

                $fileName = 'branding-' . $slot . '-' . bin2hex(random_bytes(8)) . '.png';
                if (file_put_contents($uploadDirectory . '/' . $fileName, $imageData, LOCK_EX) === false) {
                    throw new RuntimeException('Logo ' . $slot . ' gagal disimpan.');
                }
                $values[$pathKey] = 'uploads/' . $fileName;
                $newFiles[] = $uploadDirectory . '/' . $fileName;
            } elseif (isset($_POST['hapus_logo'][$slot])) {
                $values[$pathKey] = null;
            }
        }

        $statement = $pdo->prepare('UPDATE pengaturan_tampilan SET logo_1 = :logo_1, logo_1_aktif = :aktif_1, logo_2 = :logo_2, logo_2_aktif = :aktif_2, logo_3 = :logo_3, logo_3_aktif = :aktif_3 WHERE id = 1');
        $statement->execute([
            'logo_1' => $values['logo_1'], 'aktif_1' => $values['logo_1_aktif'],
            'logo_2' => $values['logo_2'], 'aktif_2' => $values['logo_2_aktif'],
            'logo_3' => $values['logo_3'], 'aktif_3' => $values['logo_3_aktif'],
        ]);

        for ($slot = 1; $slot <= 3; $slot++) {
            $previousPath = $logos['logo_' . $slot];
            if ($previousPath !== null && $previousPath !== $values['logo_' . $slot] && str_starts_with($previousPath, 'uploads/branding-' . $slot . '-')) {
                $previousFile = $uploadDirectory . '/' . basename($previousPath);
                if (is_file($previousFile)) {
                    unlink($previousFile);
                }
            }
        }

        unset($_SESSION['logos_token']);
        header('Location: logos.php?success=' . rawurlencode('Pengaturan logo berhasil disimpan.'));
        exit;
    } catch (Throwable $exception) {
        foreach ($newFiles as $newFile) {
            if (is_file($newFile)) {
                unlink($newFile);
            }
        }
        header('Location: logos.php?error=' . rawurlencode($exception->getMessage()));
        exit;
    }
}

$error = trim((string) ($_GET['error'] ?? ''));
$success = trim((string) ($_GET['success'] ?? ''));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logo Pemilihan | Admin</title>
    <script>tailwind = { theme: { extend: { fontFamily: { display: ['Georgia', 'serif'] } } } };</script>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 antialiased selection:bg-cyan-500 selection:text-white">
    <main class="flex min-h-screen w-full flex-col lg:h-screen lg:flex-row">
        <?php $activePage = 'logos'; require __DIR__ . '/_sidebar.php'; ?>
        <div class="min-w-0 flex-1 overflow-y-auto">
            <article class="mx-auto w-full max-w-7xl px-5 py-8 sm:px-8 lg:px-10 lg:py-10">
                <header class="rounded-3xl border border-white/10 bg-gradient-to-r from-slate-900 via-blue-950 to-slate-900 p-7 shadow-2xl sm:p-9">
                    <p class="text-[11px] font-extrabold tracking-[0.2em] text-cyan-300">IDENTITAS PEMILIHAN</p>
                    <h1 class="mt-3 font-display text-3xl font-bold text-white sm:text-4xl">Pengaturan logo</h1>
                    <p class="mt-2 text-sm text-slate-400">Logo tampil berjajar di atas akses NIS dan pada bilik voting. Slot yang nonaktif tetap menyediakan ruang agar susunan tidak bergeser.</p>
                </header>

                <?php if ($error !== ''): ?><p class="mt-5 rounded-xl border border-red-500/30 bg-red-500/10 p-4 text-sm text-red-300" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
                <?php if ($success !== ''): ?><p class="mt-5 rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-4 text-sm text-emerald-300" role="status"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>

                <form class="mt-6" action="logos.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['logos_token'], ENT_QUOTES, 'UTF-8') ?>">
                    <div class="grid gap-5 xl:grid-cols-3">
                        <?php
                        $locations = [1 => 'Slot kiri', 2 => 'Slot tengah', 3 => 'Slot kanan'];
                        for ($slot = 1; $slot <= 3; $slot++):
                            $pathKey = 'logo_' . $slot;
                        ?>
                            <fieldset data-logo-editor class="min-w-0 rounded-2xl border border-white/10 bg-slate-900/80 p-5 shadow-xl">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <legend class="text-base font-bold text-white">Logo <?= $slot ?></legend>
                                        <p class="mt-1 text-xs text-cyan-300"><?= $locations[$slot] ?></p>
                                    </div>
                                    <label class="inline-flex cursor-pointer items-center gap-2 text-xs font-semibold text-slate-300">
                                        <input class="h-4 w-4 accent-cyan-400" type="checkbox" name="<?= $pathKey ?>_aktif" value="1" <?= (int) $logos[$pathKey . '_aktif'] === 1 ? 'checked' : '' ?>>
                                        Aktif
                                    </label>
                                </div>
                                <div class="mt-4 flex h-28 items-center justify-center overflow-hidden rounded-xl border border-dashed border-white/15 bg-slate-950 p-3">
                                    <?php if (!empty($logos[$pathKey])): ?>
                                        <img class="max-h-full max-w-full object-contain" src="../assets/<?= htmlspecialchars($logos[$pathKey], ENT_QUOTES, 'UTF-8') ?>" alt="Logo <?= $slot ?> saat ini">
                                    <?php else: ?>
                                        <span class="text-xs text-slate-500">Belum ada logo</span>
                                    <?php endif; ?>
                                </div>
                                <label class="mt-4 block text-xs font-semibold text-slate-300" for="logo-file-<?= $slot ?>">Ganti logo</label>
                                <input id="logo-file-<?= $slot ?>" data-logo-file class="mt-2 block w-full text-xs text-slate-400 file:mr-3 file:rounded-lg file:border-0 file:bg-cyan-500/15 file:px-3 file:py-2 file:font-bold file:text-cyan-300" type="file" accept="image/jpeg,image/png,image/webp">
                                <?php if (!empty($logos[$pathKey])): ?>
                                    <label class="mt-3 flex cursor-pointer items-center gap-2 text-xs text-red-300"><input data-logo-clear class="h-4 w-4 accent-red-400" type="checkbox" name="hapus_logo[<?= $slot ?>]" value="1">Hapus logo tersimpan</label>
                                <?php else: ?>
                                    <input data-logo-clear type="checkbox" name="hapus_logo[<?= $slot ?>]" value="1" hidden>
                                <?php endif; ?>
                                <div data-logo-crop class="mt-4" hidden>
                                    <p class="mb-2 text-[11px] text-slate-400">Geser gambar untuk mengatur posisi, lalu atur zoom.</p>
                                    <canvas class="aspect-square w-full touch-none rounded-xl border border-white/10 bg-white" width="320" height="320" aria-label="Pratinjau crop logo <?= $slot ?>"></canvas>
                                    <label class="mt-3 block text-[11px] font-semibold text-slate-300" for="logo-zoom-<?= $slot ?>">Zoom</label>
                                    <input id="logo-zoom-<?= $slot ?>" data-logo-zoom class="mt-2 w-full accent-cyan-400" type="range" min="1" max="3" step="0.01" value="1">
                                </div>
                                <input data-logo-output type="hidden" name="logo_crops[<?= $slot ?>]">
                            </fieldset>
                        <?php endfor; ?>
                    </div>
                    <button class="mt-6 w-full rounded-xl bg-cyan-500 px-5 py-3.5 text-xs font-bold text-slate-950 shadow-lg shadow-cyan-500/20 transition hover:bg-cyan-400 sm:w-auto" type="submit">Simpan pengaturan logo</button>
                </form>
            </article>
        </div>
    </main>
    <script>
        document.querySelectorAll('[data-logo-editor]').forEach((editor) => {
            const input = editor.querySelector('[data-logo-file]');
            const canvas = editor.querySelector('canvas');
            const zoom = editor.querySelector('[data-logo-zoom]');
            const output = editor.querySelector('[data-logo-output]');
            const clearInput = editor.querySelector('[data-logo-clear]');
            const context = canvas.getContext('2d');
            const image = new Image();
            let scale = 1;
            let baseScale = 1;
            let offsetX = 0;
            let offsetY = 0;
            let pointer = null;
            let objectUrl = null;
            const paint = () => {
                if (!image.complete || !image.naturalWidth) return;
                context.clearRect(0, 0, canvas.width, canvas.height);
                context.drawImage(image, offsetX, offsetY, image.naturalWidth * scale, image.naturalHeight * scale);
                output.value = canvas.toDataURL('image/png');
            };
            input.addEventListener('change', () => {
                const file = input.files && input.files[0];
                if (!file) return;
                if (file.size > 8 * 1024 * 1024 || !['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
                    input.setCustomValidity('Pilih gambar JPG, PNG, atau WebP maksimal 8 MB.');
                    input.reportValidity();
                    input.value = '';
                    return;
                }
                input.setCustomValidity('');
                if (objectUrl) URL.revokeObjectURL(objectUrl);
                objectUrl = URL.createObjectURL(file);
                image.onload = () => {
                    baseScale = Math.max(canvas.width / image.naturalWidth, canvas.height / image.naturalHeight);
                    scale = baseScale;
                    zoom.value = '1';
                    offsetX = (canvas.width - image.naturalWidth * scale) / 2;
                    offsetY = (canvas.height - image.naturalHeight * scale) / 2;
                    editor.querySelector('[data-logo-crop]').hidden = false;
                    if (clearInput) clearInput.checked = false;
                    paint();
                };
                image.src = objectUrl;
            });
            zoom.addEventListener('input', () => {
                const previousScale = scale;
                scale = baseScale * Number(zoom.value);
                offsetX = canvas.width / 2 - (canvas.width / 2 - offsetX) * (scale / previousScale);
                offsetY = canvas.height / 2 - (canvas.height / 2 - offsetY) * (scale / previousScale);
                offsetX = Math.min(0, Math.max(canvas.width - image.naturalWidth * scale, offsetX));
                offsetY = Math.min(0, Math.max(canvas.height - image.naturalHeight * scale, offsetY));
                paint();
            });
            canvas.addEventListener('pointerdown', (event) => {
                pointer = { x: event.clientX, y: event.clientY };
                canvas.setPointerCapture(event.pointerId);
            });
            canvas.addEventListener('pointermove', (event) => {
                if (!pointer) return;
                const bounds = canvas.getBoundingClientRect();
                offsetX += (event.clientX - pointer.x) * canvas.width / bounds.width;
                offsetY += (event.clientY - pointer.y) * canvas.height / bounds.height;
                offsetX = Math.min(0, Math.max(canvas.width - image.naturalWidth * scale, offsetX));
                offsetY = Math.min(0, Math.max(canvas.height - image.naturalHeight * scale, offsetY));
                pointer = { x: event.clientX, y: event.clientY };
                paint();
            });
            canvas.addEventListener('pointerup', () => { pointer = null; });
            canvas.addEventListener('pointercancel', () => { pointer = null; });
            if (clearInput) clearInput.addEventListener('change', () => {
                if (!clearInput.checked) return;
                input.value = '';
                output.value = '';
                editor.querySelector('[data-logo-crop]').hidden = true;
            });
        });
    </script>
</body>
</html>
