<?php

declare(strict_types=1);

session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'siswa') {
    header('Location: index.php');
    exit;
}

require __DIR__ . '/config/database.php';

try {
    $statement = $pdo->query(
        'SELECT id, nomor_urut, nama_ketua, nama_wakil, foto, visi_misi
         FROM kandidat
         ORDER BY nomor_urut ASC'
    );

    $candidates = $statement->fetchAll();
    $branding = $pdo->query('SELECT * FROM pengaturan_tampilan WHERE id = 1')->fetch();
    $candidateCount = count($candidates);

    $gridClass = match ($candidateCount) {
        1 => 'grid-cols-1 max-w-md mx-auto',
        2 => 'grid-cols-1 sm:grid-cols-2',
        3 => 'grid-cols-1 sm:grid-cols-3',
        default => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4',
    };

    $accentClasses = [
        'from-blue-500 to-cyan-400',
        'from-indigo-500 to-blue-500',
        'from-cyan-500 to-teal-400',
        'from-blue-600 to-indigo-500'
    ];

    $error = isset($_GET['error'])
        ? trim((string) $_GET['error'])
        : '';

    $success = isset($_GET['success'])
        ? trim((string) $_GET['success'])
        : '';

} catch (PDOException $exception) {
    http_response_code(500);
    exit('Data kandidat tidak dapat dimuat. Periksa koneksi dan struktur database.');
}

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>E-Voting OSIS | SMKN 9 Surakarta</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700;800&display=swap"
        rel="stylesheet"
    >

    <script src="https://cdn.tailwindcss.com"></script>

    <style>

        * {
            box-sizing: border-box;
        }

        html,
        body {
            width: 100%;
            height: 100%;
            margin: 0;
            overflow: hidden;
        }

        body {
            font-family: 'Inter', sans-serif;
            background:
                radial-gradient(
                    circle at 15% 15%,
                    rgba(37, 99, 235, .22),
                    transparent 25%
                ),
                radial-gradient(
                    circle at 85% 80%,
                    rgba(6, 182, 212, .16),
                    transparent 25%
                ),
                #06101f;
            color: white;
        }

        .font-display {
            font-family: 'Poppins', sans-serif;
        }

        /* =========================
           BACKGROUND DECORATION
        ========================= */

        .background-grid {
            position: fixed;
            inset: 0;
            z-index: 0;
            pointer-events: none;

            background-image:
                linear-gradient(
                    rgba(96, 165, 250, .055) 1px,
                    transparent 1px
                ),
                linear-gradient(
                    90deg,
                    rgba(96, 165, 250, .055) 1px,
                    transparent 1px
                );

            background-size: 45px 45px;

            mask-image: linear-gradient(
                to bottom,
                black,
                rgba(0,0,0,.8),
                transparent
            );
        }

        .glow {
            position: fixed;
            border-radius: 999px;
            pointer-events: none;
            filter: blur(70px);
            z-index: 0;
        }

        .glow-blue {
            width: 260px;
            height: 260px;
            top: -100px;
            left: -80px;
            background: rgba(37, 99, 235, .25);
        }

        .glow-cyan {
            width: 300px;
            height: 300px;
            right: -120px;
            bottom: -100px;
            background: rgba(6, 182, 212, .18);
        }

        .glow-center {
            width: 180px;
            height: 180px;
            top: 40%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(30, 64, 175, .12);
        }

        .decor-circle {
            position: fixed;
            border: 1px solid rgba(96, 165, 250, .12);
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
        }

        .circle-one {
            width: 420px;
            height: 420px;
            left: -260px;
            top: 35%;
        }

        .circle-two {
            width: 500px;
            height: 500px;
            right: -320px;
            top: -180px;
        }

        .dot-pattern {
            position: fixed;
            width: 160px;
            height: 100px;
            right: 30px;
            top: 100px;

            background-image: radial-gradient(
                rgba(96,165,250,.35) 1.5px,
                transparent 1.5px
            );

            background-size: 14px 14px;
            opacity: .35;
            pointer-events: none;
        }

        .bottom-line {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            height: 2px;
            background: linear-gradient(
                90deg,
                transparent,
                rgba(59,130,246,.6),
                rgba(34,211,238,.6),
                transparent
            );
            z-index: 5;
        }

        /* =========================
           CARD
        ========================= */

        .candidate-card {
            background:
                linear-gradient(
                    145deg,
                    rgba(255,255,255,.97),
                    rgba(239,246,255,.97)
                );

            color: #0f172a;

            border: 2px solid rgba(255,255,255,.12);

            box-shadow:
                0 20px 45px rgba(0,0,0,.22),
                0 0 0 1px rgba(59,130,246,.05);

            transition:
                transform .3s ease,
                box-shadow .3s ease,
                border-color .3s ease;
        }

        .candidate-card:hover {
            transform: translateY(-5px);

            box-shadow:
                0 25px 55px rgba(0,0,0,.32),
                0 0 30px rgba(37,99,235,.15);
        }

        .candidate-card:has(input:checked) {
            border-color: #2563eb;

            box-shadow:
                0 20px 50px rgba(37,99,235,.25),
                0 0 0 4px rgba(59,130,246,.15);
        }

        .candidate-photo {
            aspect-ratio: 4 / 3;
            width: 100%;
            overflow: hidden;
            position: relative;
            background: #dbeafe;
        }

        .candidate-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform .5s ease;
        }

        .candidate-card:hover .candidate-photo img {
            transform: scale(1.04);
        }

        .number-badge {
            position: absolute;
            top: 10px;
            left: 10px;

            min-width: 42px;
            height: 42px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 13px;

            color: white;
            font-family: 'Poppins', sans-serif;
            font-size: 18px;
            font-weight: 800;

            box-shadow: 0 8px 20px rgba(0,0,0,.25);
        }

        .check-badge {
            position: absolute;
            top: 10px;
            right: 10px;

            width: 38px;
            height: 38px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 50%;

            background: #10b981;
            color: white;

            transform: scale(0);
            opacity: 0;

            transition: .25s ease;

            box-shadow: 0 8px 20px rgba(16,185,129,.35);
        }

        .candidate-card:has(input:checked) .check-badge {
            transform: scale(1);
            opacity: 1;
        }

        .candidate-info {
            padding: 10px 14px 12px;
        }

        .candidate-name {
            font-family: 'Poppins', sans-serif;
            font-size: clamp(15px, 1.4vw, 19px);
            line-height: 1.15;
            font-weight: 800;
            color: #0f172a;
        }

        .candidate-wakil {
            margin-top: 3px;
            font-size: 12px;
            font-weight: 600;
            color: #2563eb;
        }

        .visi-title {
            margin-top: 8px;

            display: flex;
            align-items: center;
            gap: 5px;

            font-size: 9px;
            font-weight: 800;
            letter-spacing: .1em;
            text-transform: uppercase;

            color: #64748b;
        }

        .visi-text {
            margin-top: 3px;

            font-size: 10px;
            line-height: 1.35;

            color: #475569;

            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* =========================
           ANIMATION
        ========================= */

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-card {
            animation: fadeUp .55s ease forwards;
            opacity: 0;
        }

        /* =========================
           MOBILE
        ========================= */

        @media (max-width: 640px) {

            html,
            body {
                overflow: hidden;
            }

            .background-grid {
                background-size: 30px 30px;
            }

            .circle-one,
            .circle-two {
                opacity: .5;
            }

            .dot-pattern {
                right: 5px;
                top: 75px;
                transform: scale(.7);
            }

            .candidate-info {
                padding: 7px 9px 8px;
            }

            .candidate-name {
                font-size: 14px;
            }

            .candidate-wakil {
                font-size: 10px;
            }

            .visi-title {
                margin-top: 5px;
                font-size: 8px;
            }

            .visi-text {
                font-size: 9px;
                -webkit-line-clamp: 1;
            }

            .number-badge {
                width: 34px;
                min-width: 34px;
                height: 34px;
                font-size: 14px;
                border-radius: 10px;
            }

            .check-badge {
                width: 32px;
                height: 32px;
            }
        }

    </style>

</head>

<body>

    <!-- =========================
         BACKGROUND
    ========================== -->

    <div class="background-grid"></div>

    <div class="glow glow-blue"></div>
    <div class="glow glow-cyan"></div>
    <div class="glow glow-center"></div>

    <div class="decor-circle circle-one"></div>
    <div class="decor-circle circle-two"></div>

    <div class="dot-pattern"></div>

    <div class="bottom-line"></div>


    <!-- =========================
         MAIN
    ========================== -->

    <main class="relative z-10 mx-auto flex h-screen w-full max-w-[1250px] flex-col px-4 py-3 sm:px-6 lg:px-8">


        <!-- HEADER -->

        <header
            class="mb-2 flex shrink-0 items-center justify-between rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-2.5 shadow-xl backdrop-blur-xl"
        >

            <div class="flex min-w-0 items-center gap-3">

                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-blue-600 to-cyan-500 shadow-lg shadow-blue-600/25"
                >

                    <svg
                        class="h-5 w-5 text-white"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>

                </div>


                <div class="min-w-0">

                    <h1 class="font-display truncate text-sm font-bold text-white sm:text-base">
                        E-Voting OSIS
                    </h1>

                    <div class="flex items-center gap-1.5 truncate">

                        <span class="truncate text-[10px] font-semibold text-blue-300 sm:text-xs">
                            <?= htmlspecialchars((string) $_SESSION['user']['nama'], ENT_QUOTES, 'UTF-8') ?>
                        </span>

                        <span class="text-[9px] text-slate-500 sm:text-[10px]">
                            •
                        </span>

                        <span class="text-[9px] text-slate-400 sm:text-[10px]">
                            NIS <?= htmlspecialchars((string) $_SESSION['user']['nis'], ENT_QUOTES, 'UTF-8') ?>
                        </span>

                        <?php if (!empty($_SESSION['user']['kelas'])): ?>

                            <span class="hidden rounded-full bg-blue-500/15 px-2 py-0.5 text-[9px] font-bold text-blue-300 sm:inline-block">
                                <?= htmlspecialchars((string) $_SESSION['user']['kelas'], ENT_QUOTES, 'UTF-8') ?>
                            </span>

                        <?php endif; ?>

                    </div>

                </div>

            </div>


            <a
                href="logout.php"
                class="shrink-0 rounded-lg bg-white/10 px-3 py-2 text-[10px] font-bold text-white transition hover:bg-red-500/80 sm:px-4 sm:text-xs"
            >
                Keluar
            </a>

        </header>


        <!-- TITLE -->

        <?php $votingLogoSlots = range(1, 3); ?>
        <?php if (array_filter($votingLogoSlots, static fn (int $slot): bool => !empty($branding['logo_' . $slot]) && (int) $branding['logo_' . $slot . '_aktif'] === 1) !== []): ?>
            <div class="mb-2 grid shrink-0 grid-cols-3 justify-items-center gap-6 self-center" aria-label="Logo penyelenggara">
                <?php foreach ($votingLogoSlots as $slot): ?>
                    <div class="flex h-16 w-16 items-center justify-center sm:h-20 sm:w-20">
                        <?php if (!empty($branding['logo_' . $slot]) && (int) $branding['logo_' . $slot . '_aktif'] === 1): ?>
                            <img class="h-full w-full object-contain" src="assets/<?= htmlspecialchars($branding['logo_' . $slot], ENT_QUOTES, 'UTF-8') ?>" alt="Logo <?= $slot ?>">
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <section class="mb-2 shrink-0 text-center">

            <div class="mb-0.5 inline-flex items-center gap-2">

                <span class="h-px w-7 bg-gradient-to-r from-transparent to-blue-400"></span>

                <span class="text-[9px] font-bold uppercase tracking-[.2em] text-blue-300">
                    Pemilihan OSIS
                </span>

                <span class="h-px w-7 bg-gradient-to-l from-transparent to-blue-400"></span>

            </div>

            <h2 class="font-display text-lg font-extrabold tracking-tight text-white sm:text-2xl">
                Pilih Ketua & Wakil Ketua OSIS
            </h2>

            <p class="text-[9px] text-slate-400 sm:text-[10px]">
                Tentukan pilihanmu dengan teliti sebelum mengirim suara.
            </p>

        </section>


        <!-- ALERT ERROR -->

        <?php if ($error !== ''): ?>

            <div
                data-auto-dismiss
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 px-5 backdrop-blur-md"
                role="alert"
            >

                <div class="w-full max-w-sm rounded-2xl bg-white p-7 text-center shadow-2xl">

                    <div class="mx-auto grid h-16 w-16 place-items-center rounded-full bg-red-100 text-4xl text-red-500">
                        ×
                    </div>

                    <p class="mt-4 font-display text-lg font-bold text-slate-900">
                        Voting Gagal
                    </p>

                    <p class="mt-2 text-sm text-slate-600">
                        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                    </p>

                </div>

            </div>

        <?php endif; ?>


        <!-- ALERT SUCCESS -->

        <?php if ($success !== ''): ?>

            <div
                data-auto-dismiss
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 px-5 backdrop-blur-md"
                role="status"
            >

                <div class="w-full max-w-sm rounded-2xl bg-white p-7 text-center shadow-2xl">

                    <div class="mx-auto grid h-16 w-16 place-items-center rounded-full bg-emerald-100 text-4xl font-bold text-emerald-500">
                        ✓
                    </div>

                    <p class="mt-4 font-display text-lg font-bold text-slate-900">
                        Voting Berhasil!
                    </p>

                    <p class="mt-2 text-sm text-slate-600">
                        <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
                    </p>

                </div>

            </div>

        <?php endif; ?>


        <!-- =========================
             FORM VOTING
        ========================== -->

        <form
            id="vote-form"
            action="vote_process.php"
            method="post"
            class="flex min-h-0 flex-1 flex-col"
        >


            <!-- CANDIDATE GRID -->

            <div
                class="grid min-h-0 flex-1 <?= $gridClass ?> gap-2.5 sm:gap-3 lg:gap-4"
            >

                <?php foreach ($candidates as $index => $candidate): ?>

                    <label
                        class="candidate-card animate-card group relative flex min-h-0 cursor-pointer flex-col overflow-hidden rounded-2xl"
                        style="animation-delay: <?= ($index + 1) * 100 ?>ms;"
                    >

                        <input
                            class="peer sr-only"
                            type="radio"
                            name="kandidat_id"
                            value="<?= (int) $candidate['id'] ?>"
                            required
                        >


                        <!-- FOTO 4:3 -->

                        <div class="candidate-photo shrink-0">

                            <?php if (!empty($candidate['foto']) && $candidate['foto'] !== 'default.jpg'): ?>

                                <img
                                    src="assets/<?= htmlspecialchars((string) $candidate['foto'], ENT_QUOTES, 'UTF-8') ?>"
                                    alt="Kandidat <?= (int) $candidate['nomor_urut'] ?>"
                                >

                            <?php else: ?>

                                <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-blue-100 to-slate-200">

                                    <svg
                                        class="h-16 w-16 text-blue-300"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="1.5"
                                            d="M5.121 17.804A9 9 0 0112 15a9 9 0 016.879 2.804M15 10a3 3 0 11-6 0 3 3 0 016 0z"
                                        />
                                    </svg>

                                </div>

                            <?php endif; ?>


                            <!-- NOMOR -->

                            <div
                                class="number-badge bg-gradient-to-br <?= $accentClasses[$index % 4] ?>"
                            >
                                <?= (int) $candidate['nomor_urut'] ?>
                            </div>


                            <!-- CHECK -->

                            <div class="check-badge">

                                <svg
                                    class="h-5 w-5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="3"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M5 13l4 4L19 7"
                                    />
                                </svg>

                            </div>

                        </div>


                        <!-- =========================
                             DESKRIPSI DI BAWAH FOTO
                        ========================== -->

                        <div class="candidate-info flex min-h-0 flex-1 flex-col">

                            <h3 class="candidate-name">

                                <?= htmlspecialchars(
                                    (string) $candidate['nama_ketua'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </h3>


                            <p class="candidate-wakil">

                                Wakil:
                                <?= htmlspecialchars(
                                    (string) $candidate['nama_wakil'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </p>


                            <div class="visi-title">

                                <span class="h-1 w-1 rounded-full bg-blue-500"></span>

                                Visi & Misi

                            </div>


                            <p class="visi-text">

                                <?= htmlspecialchars(
                                    (string) $candidate['visi_misi'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </p>

                        </div>

                    </label>

                <?php endforeach; ?>

            </div>


            <!-- =========================
                 BUTTON
            ========================== -->

            <?php if ($candidates !== []): ?>

                <div class="flex shrink-0 justify-center py-2 sm:py-2.5">

                    <button
                        type="submit"
                        class="group relative flex h-10 w-full max-w-[300px] items-center justify-center overflow-hidden rounded-xl bg-gradient-to-r from-blue-600 via-blue-600 to-cyan-500 text-xs font-bold text-white shadow-lg shadow-blue-600/25 transition hover:scale-[1.02] hover:shadow-blue-500/40 focus:outline-none focus:ring-4 focus:ring-blue-400/30 sm:h-11 sm:text-sm"
                    >

                        <span class="relative z-10 flex items-center gap-2">

                            Kirim Suara

                            <svg
                                class="h-4 w-4 transition-transform group-hover:translate-x-1"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M14 5l7 7m0 0l-7 7m7-7H3"
                                />
                            </svg>

                        </span>

                        <div
                            class="absolute inset-0 translate-y-full bg-white/15 transition-transform group-hover:translate-y-0"
                        ></div>

                    </button>

                </div>

            <?php else: ?>

                <div class="mt-2 rounded-xl border border-red-400/20 bg-red-500/10 p-4 text-center text-xs text-red-300">
                    Belum ada kandidat yang tersedia.
                </div>

            <?php endif; ?>

        </form>

        <footer class="shrink-0 py-1 text-center text-[9px] text-slate-500">
            &copy; <?= date('Y') ?> <?= htmlspecialchars((string) ($branding['teks_copyright'] ?? 'E-Voting OSIS. Hak cipta dilindungi.'), ENT_QUOTES, 'UTF-8') ?>
        </footer>

    </main>


    <!-- =========================
         MODAL KONFIRMASI
    ========================== -->

    <div
        id="confirm-modal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/75 px-5 backdrop-blur-md"
        role="dialog"
        aria-modal="true"
    >

        <div
            id="modal-content"
            class="max-h-[90vh] w-full max-w-md overflow-y-auto scale-95 rounded-3xl bg-white p-6 opacity-0 shadow-2xl transition-all duration-300"
        >

            <div class="mb-5 flex items-start justify-between">

                <div>

                    <span class="text-[10px] font-bold uppercase tracking-widest text-blue-600">
                        Konfirmasi
                    </span>

                    <h2 class="mt-1 font-display text-2xl font-bold text-slate-900">
                        Sudah yakin?
                    </h2>

                </div>


                <button
                    id="cancel-vote"
                    type="button"
                    class="grid h-9 w-9 place-items-center rounded-full bg-slate-100 text-slate-500 transition hover:bg-slate-200"
                >

                    <svg
                        class="h-5 w-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>

                </button>

            </div>


            <div class="flex items-center gap-4 rounded-2xl border border-blue-100 bg-blue-50 p-4">

                <img
                    id="confirm-photo"
                    class="hidden h-16 w-16 rounded-xl object-cover shadow-sm"
                    alt="Foto terpilih"
                >

                <div>

                    <p class="text-xs font-medium text-slate-500">
                        Pilihan Kamu:
                    </p>

                    <p class="mt-1 font-display font-bold text-blue-950">
                        <span id="confirm-chair"></span><br>
                        <span id="confirm-deputy" class="text-xs font-medium text-blue-700"></span>
                    </p>

                </div>

            </div>


            <section class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <h3 class="text-xs font-bold uppercase tracking-wide text-blue-700">Visi &amp; Misi</h3>
                <p id="confirm-vision" class="mt-2 max-h-40 overflow-y-auto whitespace-pre-line break-words text-sm leading-relaxed text-slate-700"></p>
            </section>


            <p class="mt-5 text-center text-xs leading-relaxed text-slate-500">
                Suara yang sudah dikirim bersifat final dan tidak dapat diubah lagi.
            </p>


            <div class="mt-5 grid grid-cols-2 gap-3">

                <button
                    id="cancel-vote-secondary"
                    type="button"
                    class="rounded-xl border-2 border-slate-200 bg-white py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50"
                >
                    Batal
                </button>


                <button
                    id="confirm-vote"
                    type="button"
                    class="rounded-xl bg-blue-600 py-3 text-sm font-bold text-white shadow-lg shadow-blue-600/25 transition hover:bg-blue-700"
                >
                    Ya, Kirim
                </button>

            </div>

        </div>

    </div>


    <!-- =========================
         JAVASCRIPT
    ========================== -->

    <script>

        const voteForm = document.querySelector('#vote-form');

        const confirmModal = document.querySelector('#confirm-modal');

        const modalContent = document.querySelector('#modal-content');

        const confirmChair =
            document.querySelector('#confirm-chair');

        const confirmDeputy =
            document.querySelector('#confirm-deputy');

        const confirmPhoto =
            document.querySelector('#confirm-photo');

        const confirmVision =
            document.querySelector('#confirm-vision');


        const closeConfirmModal = () => {

            modalContent.classList.remove(
                'scale-100',
                'opacity-100'
            );

            modalContent.classList.add(
                'scale-95',
                'opacity-0'
            );

            setTimeout(() => {

                confirmModal.classList.add('hidden');

                confirmModal.classList.remove('flex');

            }, 200);

        };


        const openConfirmModal = () => {

            const selected =
                voteForm.querySelector(
                    'input[name="kandidat_id"]:checked'
                );

            if (!selected) {

                voteForm.reportValidity();

                return;

            }


            const card = selected.closest('label');


            const namaKetua =
                card.querySelector('h3')
                    .textContent
                    .trim();


            const namaWakil =
                card.querySelector('.candidate-wakil')
                    .textContent
                    .trim()
                    .replace(/^Wakil:\s*/i, '');


            confirmChair.textContent = namaKetua;
            confirmDeputy.textContent = `& ${namaWakil}`;

            confirmVision.textContent =
                card.querySelector('.visi-text').textContent.trim();


            const photo =
                card.querySelector('img');


            if (photo) {

                confirmPhoto.src = photo.src;

                confirmPhoto.classList.remove('hidden');

            } else {

                confirmPhoto.classList.add('hidden');

            }


            confirmModal.classList.remove('hidden');

            confirmModal.classList.add('flex');


            setTimeout(() => {

                modalContent.classList.remove(
                    'scale-95',
                    'opacity-0'
                );

                modalContent.classList.add(
                    'scale-100',
                    'opacity-100'
                );

            }, 10);

        };


        voteForm.addEventListener('submit', (event) => {

            event.preventDefault();

            openConfirmModal();

        });


        document
            .querySelector('#confirm-vote')
            .addEventListener('click', () => {

                voteForm.submit();

            });


        document
            .querySelector('#cancel-vote')
            .addEventListener(
                'click',
                closeConfirmModal
            );


        document
            .querySelector('#cancel-vote-secondary')
            .addEventListener(
                'click',
                closeConfirmModal
            );


        confirmModal.addEventListener('click', (event) => {

            if (event.target === confirmModal) {

                closeConfirmModal();

            }

        });


        document.addEventListener('keydown', (event) => {

            if (event.key === 'Escape') {

                closeConfirmModal();

            }

        });


        /* Auto dismiss */

        document
            .querySelectorAll('[data-auto-dismiss]')
            .forEach((alert) => {

                setTimeout(() => {

                    alert.style.opacity = '0';
                    alert.style.transform = 'scale(.95)';
                    alert.style.transition = '.5s ease';

                    setTimeout(() => {

                        alert.remove();

                    }, 500);

                }, 3500);

            });

    </script>

</body>

</html>