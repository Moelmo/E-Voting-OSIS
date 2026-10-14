<?php
declare(strict_types=1);
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') { header('Location: ../index.php'); exit; }
require __DIR__ . '/../config/database.php';

$allowedKelas = ['X', 'XI', 'XII'];
$allowedJurusan = [
    // TJKT
    'TJKT-1', 'TJKT-2', 'TJKT-3', 'TJKT-4',
    
    // DKV
    'DKV-1', 'DKV-2', 'DKV-3',
    
    // DPK
    'DPK-1', 'DPK-2', 'DPK-3', 'DPK-4',
    
    // ANIMASI
    'ANIMASI-1', 'ANIMASI-2',
    
    // BUSANA
    'BUSANA-1', 'BUSANA-2',
    
    // SENI RUPA
    'SENI RUPA'
];
$filterKelas = trim((string) ($_GET['kelas'] ?? ''));
$filterJurusan = trim((string) ($_GET['jurusan'] ?? ''));
$sort = (string) ($_GET['sort'] ?? 'nama');
$direction = strtoupper((string) ($_GET['direction'] ?? 'ASC')) === 'DESC' ? 'DESC' : 'ASC';
$sortColumns = ['nama' => 'nama', 'nis' => 'nis', 'kelas' => 'kelas', 'jurusan' => 'jurusan', 'status' => 'sudah_voting'];
$sortColumn = $sortColumns[$sort] ?? 'nama';

$where = ["role = 'siswa'"];
$params = [];
if (in_array($filterKelas, $allowedKelas, true)) { $where[] = 'kelas = :kelas'; $params['kelas'] = $filterKelas; } else { $filterKelas = ''; }
if (in_array($filterJurusan, $allowedJurusan, true)) { $where[] = 'jurusan = :jurusan'; $params['jurusan'] = $filterJurusan; } else { $filterJurusan = ''; }

$statement = $pdo->prepare('SELECT id, nama, nis, kelas, jurusan, sudah_voting FROM siswa WHERE ' . implode(' AND ', $where) . " ORDER BY {$sortColumn} {$direction}, nama ASC");
$statement->execute($params);
$students = $statement->fetchAll();

$studentFlash = $_SESSION['student_flash'] ?? null;
unset($_SESSION['student_flash']);
$error = $studentFlash !== null && ($studentFlash['type'] ?? '') === 'error' ? (string) $studentFlash['message'] : '';
$success = $studentFlash !== null && ($studentFlash['type'] ?? '') === 'success' ? (string) $studentFlash['message'] : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Siswa | Admin</title>
    <script>tailwind={theme:{extend:{fontFamily:{display:['Georgia','serif']}}}};</script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes float-slow {
            0%, 100% { transform: translateY(0px) scale(1); }
            50% { transform: translateY(-10px) scale(1.03); }
        }
        .animate-float { animation: float-slow 6s ease-in-out infinite; }
        
        @keyframes modal-pop {
            0% { opacity: 0; transform: scale(0.95) translateY(10px); }
            100% { opacity: 1; transform: scale(1) translateY(0); }
        }
        .animate-modal { animation: modal-pop 0.2s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
    </style>
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 antialiased selection:bg-cyan-500 selection:text-white">
    <main class="flex min-h-screen w-full flex-col lg:h-screen lg:flex-row">
        <?php $activePage = 'students'; require __DIR__ . '/_sidebar.php'; ?>
        
        <div class="relative min-w-0 flex-1 overflow-y-auto">
            <!-- Background Ambient Glow -->
            <div class="pointer-events-none fixed -top-20 -left-20 h-96 w-96 rounded-full bg-blue-600/20 blur-[120px]"></div>
            <div class="pointer-events-none fixed top-1/3 -right-20 h-96 w-96 rounded-full bg-cyan-500/15 blur-[120px]"></div>

            <article class="relative z-10 mx-auto w-full max-w-7xl px-5 py-8 sm:px-8 lg:px-10 lg:py-10">
                <!-- Header Banner -->
                <header class="relative overflow-hidden rounded-3xl border border-white/10 bg-gradient-to-r from-slate-900 via-blue-950 to-slate-900 p-7 shadow-2xl backdrop-blur-xl sm:p-9">
                    <div class="pointer-events-none absolute -right-10 -bottom-10 h-64 w-64 rounded-full bg-cyan-500/20 blur-[80px] animate-float"></div>
                    <div class="relative z-10">
                        <div class="inline-flex items-center gap-2 rounded-full border border-cyan-500/30 bg-cyan-500/10 px-3 py-1 text-[11px] font-extrabold tracking-[0.2em] text-cyan-300">
                            <span class="h-1.5 w-1.5 rounded-full bg-cyan-400 animate-pulse"></span>
                            DATA PEMILIH
                        </div>
                        <h1 class="mt-3 font-display text-3xl font-bold tracking-tight text-white sm:text-4xl lg:text-5xl">Siswa terdaftar.</h1>
                        <p class="mt-2 text-sm text-slate-400">Tambah, cari, urutkan, import/export, dan kelola hak pilih siswa secara efisien.</p>
                    </div>
                </header>

                <!-- Modal Flash Messages -->
                <?php if ($error !== ''): ?>
                    <div data-auto-dismiss class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 px-5 backdrop-blur-md transition-opacity duration-300" role="alert">
                        <div class="w-full max-w-sm rounded-3xl border border-red-500/30 bg-slate-900 p-7 text-center shadow-2xl shadow-red-950/50 animate-modal">
                            <div class="mx-auto grid h-16 w-16 animate-pulse place-items-center rounded-full border border-red-500/20 bg-red-500/10 text-4xl text-red-500" aria-hidden="true">×</div>
                            <p class="mt-4 font-bold text-white text-lg">Gagal</p>
                            <p class="mt-2 text-sm text-red-400"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($success !== ''): ?>
                    <div data-auto-dismiss class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 px-5 backdrop-blur-md transition-opacity duration-300" role="status">
                        <div class="w-full max-w-sm rounded-3xl border border-emerald-500/30 bg-slate-900 p-7 text-center shadow-2xl shadow-emerald-950/50 animate-modal">
                            <div class="mx-auto grid h-16 w-16 animate-bounce place-items-center rounded-full border border-emerald-500/20 bg-emerald-500/10 text-4xl text-emerald-400" aria-hidden="true">✓</div>
                            <p class="mt-4 font-bold text-white text-lg">Berhasil</p>
                            <p class="mt-2 text-sm text-emerald-400"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Form Tambah Siswa Manual & Import/Export Grid -->
                <div class="mt-8 grid gap-6 lg:grid-cols-3">
                    <!-- Manual Add (2 Columns) -->
                    <section class="rounded-3xl border border-white/10 bg-slate-900/80 p-6 shadow-2xl backdrop-blur-xl lg:col-span-2 sm:p-8">
                        <div class="flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full bg-cyan-400"></span>
                            <h2 class="font-display text-2xl font-bold text-white sm:text-3xl">Tambah siswa</h2>
                        </div>
                        <form class="mt-6 grid gap-3 md:grid-cols-2" action="student_add.php" method="post">
                            <input class="rounded-2xl border border-white/10 bg-slate-950/60 px-4 py-3.5 text-sm font-medium text-white placeholder-slate-500 outline-none transition focus:border-cyan-400 focus:ring-2 focus:ring-cyan-400/20" name="nis" placeholder="NIS Siswa" required>
                            <input class="rounded-2xl border border-white/10 bg-slate-950/60 px-4 py-3.5 text-sm font-medium text-white placeholder-slate-500 outline-none transition focus:border-cyan-400 focus:ring-2 focus:ring-cyan-400/20" name="nama" placeholder="Nama Lengkap" required>
                            <select class="rounded-2xl border border-white/10 bg-slate-950/60 px-4 py-3.5 text-sm font-medium text-slate-200 outline-none transition focus:border-cyan-400 focus:ring-2 focus:ring-cyan-400/20" name="kelas" required>
                                <option value="" class="bg-slate-900 text-slate-400">Pilih Kelas</option>
                                <option value="X" class="bg-slate-900 text-white">Kelas X</option>
                                <option value="XI" class="bg-slate-900 text-white">Kelas XI</option>
                                <option value="XII" class="bg-slate-900 text-white">Kelas XII</option>
                            </select>
                            <select class="rounded-2xl border border-white/10 bg-slate-950/60 px-4 py-3.5 text-sm font-medium text-slate-200 outline-none transition focus:border-cyan-400 focus:ring-2 focus:ring-cyan-400/20" name="jurusan" required>
                                <option value="" class="bg-slate-900 text-slate-400">Pilih Jurusan</option>
                                <?php foreach ($allowedJurusan as$option): ?>
                                    <option class="bg-slate-900 text-white"><?= $option ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="md:col-span-2 rounded-2xl bg-gradient-to-r from-blue-600 via-cyan-600 to-blue-600 bg-[length:200%_auto] px-5 py-3.5 text-xs font-bold text-white shadow-lg shadow-cyan-600/20 transition-all duration-300 hover:bg-[right_center] hover:shadow-cyan-500/30 active:scale-95" type="submit">
                                + Tambah Siswa Manual
                            </button>
                        </form>
                    </section>

                    <!-- Import & Export Tools (1 Column) -->
                    <section class="flex flex-col justify-between rounded-3xl border border-white/10 bg-slate-900/80 p-6 shadow-2xl backdrop-blur-xl sm:p-8">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                                <h2 class="font-display text-2xl font-bold text-white">Import / Export</h2>
                            </div>
                            <p class="mt-2 text-xs text-slate-400">Kelola data pemilih secara masal menggunakan format CSV/Excel.</p>

                            <!-- Form Import -->
                            <form action="import_student.php" method="post" enctype="multipart/form-data" class="mt-5 space-y-3">
                                <div>
                                    <label class="block text-xs font-bold text-slate-300 mb-1.5">File CSV atau Excel (.csv, .xlsx)</label>
                                    <input type="file" name="file_excel" accept=".csv,.xlsx" class="block w-full text-xs text-slate-400 file:mr-3 file:rounded-xl file:border-0 file:bg-slate-800 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-cyan-300 hover:file:bg-slate-700" required>
                                </div>
                                <button type="submit" class="w-full rounded-2xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-xs font-bold text-emerald-400 transition hover:bg-emerald-500/20 hover:border-emerald-500/50">
                                    ↑ Import Data CSV / Excel
                                </button>
                            </form>
                        </div>

                        <!-- Button Export -->
                        <div class="mt-6 border-t border-white/10 pt-5">
                            <a href="export_student.php" class="block w-full text-center rounded-2xl border border-cyan-500/30 bg-cyan-500/10 px-4 py-3 text-xs font-bold text-cyan-300 transition hover:bg-cyan-500/20 hover:border-cyan-500/50">
                                ↓ Export Data Siswa (Excel)
                            </a>
                        </div>
                    </section>
                </div>

                <!-- Tabel Data Siswa & Filter -->
                <section class="mt-8 rounded-3xl border border-white/10 bg-slate-900/80 p-6 shadow-2xl backdrop-blur-xl sm:p-8">
                    <form id="bulkDeleteForm" action="student_delete.php" method="post">
                        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                            <div>
                                <span class="text-[11px] font-extrabold tracking-[0.2em] text-cyan-400">DATABASE SISWA</span>
                                <h2 class="mt-1 font-display text-2xl font-bold text-white sm:text-3xl">Daftar pemilih</h2>
                            </div>
                            <div class="flex flex-wrap items-center gap-3">
                                <button type="button" id="btnBulkDelete" onclick="openDeleteModal('bulk')" class="hidden items-center gap-2 rounded-full border border-red-500/40 bg-red-500/20 px-4 py-2 text-xs font-bold text-red-300 backdrop-blur-md transition-all hover:bg-red-500/30 active:scale-95">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    Hapus Terpilih (<span id="selectedCount">0</span>)
                                </button>
                                <span class="inline-flex items-center gap-2 rounded-full border border-cyan-500/30 bg-cyan-500/10 px-4 py-2 text-xs font-bold text-cyan-300 backdrop-blur-md">
                                    <span class="h-2 w-2 rounded-full bg-cyan-400 animate-pulse"></span>
                                    <?= count($students) ?> Siswa Terdaftar
                                </span>
                            </div>
                        </div>

                        <!-- Form Filter & Sorting (Dipisah lewat JS submit agar tidak bentrok dengan form bulk delete) -->
                        <div class="my-6 grid gap-3 rounded-2xl border border-white/10 bg-slate-950/50 p-4 sm:grid-cols-2 lg:grid-cols-5">
                            <select id="filterKelas" class="rounded-xl border border-white/10 bg-slate-900 px-3.5 py-2.5 text-xs font-medium text-slate-200 outline-none focus:border-cyan-400">
                                <option value="">Semua Kelas</option>
                                <?php foreach ($allowedKelas as$option): ?>
                                    <option value="<?= $option ?>" <?= $filterKelas === $option ? 'selected' : '' ?>>Kelas <?= $option ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select id="filterJurusan" class="rounded-xl border border-white/10 bg-slate-900 px-3.5 py-2.5 text-xs font-medium text-slate-200 outline-none focus:border-cyan-400">
                                <option value="">Semua Jurusan</option>
                                <?php foreach ($allowedJurusan as$option): ?>
                                    <option value="<?= $option ?>" <?= $filterJurusan === $option ? 'selected' : '' ?>><?= $option ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select id="filterSort" class="rounded-xl border border-white/10 bg-slate-900 px-3.5 py-2.5 text-xs font-medium text-slate-200 outline-none focus:border-cyan-400">
                                <option value="nama" <?= $sort === 'nama' ? 'selected' : '' ?>>Urutkan Nama</option>
                                <option value="nis" <?= $sort === 'nis' ? 'selected' : '' ?>>Urutkan NIS</option>
                                <option value="kelas" <?= $sort === 'kelas' ? 'selected' : '' ?>>Urutkan Kelas</option>
                                <option value="jurusan" <?= $sort === 'jurusan' ? 'selected' : '' ?>>Urutkan Jurusan</option>
                                <option value="status" <?= $sort === 'status' ? 'selected' : '' ?>>Urutkan Status</option>
                            </select>
                            <select id="filterDirection" class="rounded-xl border border-white/10 bg-slate-900 px-3.5 py-2.5 text-xs font-medium text-slate-200 outline-none focus:border-cyan-400">
                                <option value="ASC" <?= $direction === 'ASC' ? 'selected' : '' ?>>A-Z / Kecil - Besar</option>
                                <option value="DESC" <?= $direction === 'DESC' ? 'selected' : '' ?>>Z-A / Besar - Kecil</option>
                            </select>
                            <button type="button" onclick="applyFilter()" class="rounded-xl border border-white/10 bg-slate-800 px-4 py-2.5 text-xs font-bold text-white transition hover:border-cyan-500/50 hover:bg-slate-700">
                                Terapkan Filter
                            </button>
                        </div>

                        <!-- Table Data -->
                        <div class="overflow-x-auto rounded-2xl border border-white/10 bg-slate-950/40">
                            <table class="w-full min-w-[760px] text-left text-sm">
                                <thead class="border-b border-white/10 bg-slate-900/90 text-xs uppercase tracking-wider text-slate-400">
                                    <tr>
                                        <th class="px-5 py-4 w-10 text-center">
                                            <input type="checkbox" id="selectAll" class="h-4 w-4 rounded border-slate-700 bg-slate-900 text-cyan-500 focus:ring-cyan-500/20 focus:ring-offset-0 cursor-pointer">
                                        </th>
                                        <th class="px-5 py-4">Nama</th>
                                        <th class="px-5 py-4">NIS</th>
                                        <th class="px-5 py-4">Kelas / Jurusan</th>
                                        <th class="px-5 py-4 text-center">Status Voting</th>
                                        <th class="px-5 py-4 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-white/5">
                                    <?php if (empty($students)): ?>
                                        <tr>
                                            <td colspan="6" class="px-5 py-8 text-center text-xs text-slate-500">
                                                Tidak ada data siswa yang ditemukan.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($students as$student): ?>
                                            <tr class="transition-colors hover:bg-slate-800/40">
                                                <td class="px-5 py-4 text-center">
                                                    <input type="checkbox" name="student_ids[]" value="<?= (int) $student['id'] ?>" class="student-checkbox h-4 w-4 rounded border-slate-700 bg-slate-900 text-cyan-500 focus:ring-cyan-500/20 focus:ring-offset-0 cursor-pointer">
                                                </td>
                                                <td class="px-5 py-4 font-semibold text-white">
                                                    <?= htmlspecialchars($student['nama'], ENT_QUOTES, 'UTF-8') ?>
                                                </td>
                                                <td class="px-5 py-4 text-xs font-mono text-cyan-300/80">
                                                    <?= htmlspecialchars($student['nis'], ENT_QUOTES, 'UTF-8') ?>
                                                </td>
                                                <td class="px-5 py-4">
                                                    <span class="inline-block rounded-lg border border-cyan-500/30 bg-cyan-500/10 px-2.5 py-1 text-xs font-bold text-cyan-300">
                                                        <?= htmlspecialchars($student['kelas'], ENT_QUOTES, 'UTF-8') ?>
                                                    </span>
                                                    <span class="ml-1.5 text-xs text-slate-400">
                                                        <?= htmlspecialchars($student['jurusan'], ENT_QUOTES, 'UTF-8') ?>
                                                    </span>
                                                </td>
                                                <td class="px-5 py-4 text-center">
                                                    <?php if ((int) $student['sudah_voting'] === 1): ?>
                                                        <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-500/30 bg-emerald-500/10 px-3 py-1 text-xs font-bold text-emerald-400">
                                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                                                            Sudah Voting
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="inline-flex items-center gap-1.5 rounded-full border border-white/10 bg-slate-800/80 px-3 py-1 text-xs font-bold text-slate-400">
                                                            <span class="h-1.5 w-1.5 rounded-full bg-slate-500"></span>
                                                            Belum Voting
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-5 py-4 text-right">
                                                    <button type="button" onclick="openDeleteModal('single', <?= (int) $student['id'] ?>, '<?= htmlspecialchars(addslashes($student['nama']), ENT_QUOTES, 'UTF-8') ?>')" class="rounded-xl border border-red-500/20 bg-red-500/10 px-3.5 py-2 text-xs font-bold text-red-400 transition-all hover:border-red-500 hover:bg-red-600 hover:text-white active:scale-95">
                                                        Hapus
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </form>
                </section>
            </article>
        </div>
    </main>

    <!-- Custom Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-950/80 px-5 backdrop-blur-md transition-all duration-300">
        <div class="relative w-full max-w-md rounded-3xl border border-red-500/30 bg-slate-900 p-7 text-center shadow-2xl shadow-red-950/50 animate-modal">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full border border-red-500/30 bg-red-500/10 text-red-500">
                <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            
            <h3 class="mt-4 font-display text-xl font-bold text-white">Konfirmasi Hapus</h3>
            <p id="deleteModalMessage" class="mt-2 text-xs leading-relaxed text-slate-400">
                Apakah Anda yakin ingin menghapus data siswa ini? Tindakan ini tidak dapat dibatalkan.
            </p>

            <div class="mt-6 flex items-center justify-center gap-3">
                <button type="button" onclick="closeDeleteModal()" class="w-full rounded-xl border border-white/10 bg-slate-800 py-3 text-xs font-bold text-slate-300 transition hover:bg-slate-700">
                    Batal
                </button>
                <button type="button" id="confirmDeleteBtn" class="w-full rounded-xl border border-red-500/40 bg-gradient-to-r from-red-600 to-rose-600 py-3 text-xs font-bold text-white shadow-lg shadow-red-600/30 transition hover:brightness-110 active:scale-95">
                    Ya, Hapus
                </button>
            </div>
        </div>
    </div>

    <script>
        // Auto dismiss flash notification
        document.querySelectorAll('[data-auto-dismiss]').forEach((alert) => { 
            setTimeout(() => { 
                alert.classList.add('opacity-0'); 
                setTimeout(() => alert.remove(), 300); 
            }, 1200); 
        });

        // Filter Functionality
        function applyFilter() {
            const kelas = document.getElementById('filterKelas').value;
            const jurusan = document.getElementById('filterJurusan').value;
            const sort = document.getElementById('filterSort').value;
            const direction = document.getElementById('filterDirection').value;
            
            const params = new URLSearchParams();
            if (kelas) params.set('kelas', kelas);
            if (jurusan) params.set('jurusan', jurusan);
            if (sort) params.set('sort', sort);
            if (direction) params.set('direction', direction);

            window.location.search = params.toString();
        }

        // Bulk Checkbox Handling
        const selectAll = document.getElementById('selectAll');
        const studentCheckboxes = document.querySelectorAll('.student-checkbox');
        const btnBulkDelete = document.getElementById('btnBulkDelete');
        const selectedCount = document.getElementById('selectedCount');

        function updateBulkState() {
            const checkedCount = document.querySelectorAll('.student-checkbox:checked').length;
            selectedCount.textContent = checkedCount;
            
            if (checkedCount > 0) {
                btnBulkDelete.classList.remove('hidden');
                btnBulkDelete.classList.add('flex');
            } else {
                btnBulkDelete.classList.add('hidden');
                btnBulkDelete.classList.remove('flex');
            }

            if (studentCheckboxes.length > 0) {
                selectAll.checked = checkedCount === studentCheckboxes.length;
            }
        }

        if (selectAll) {
            selectAll.addEventListener('change', function() {
                studentCheckboxes.forEach(cb => cb.checked = this.checked);
                updateBulkState();
            });
        }

        studentCheckboxes.forEach(cb => {
            cb.addEventListener('change', updateBulkState);
        });

        // Delete Modal Handling
        let pendingSingleId = null;
        let deleteMode = 'single';

        function openDeleteModal(mode, id = null, name = '') {
            deleteMode = mode;
            const modal = document.getElementById('deleteModal');
            const message = document.getElementById('deleteModalMessage');

            if (mode === 'single') {
                pendingSingleId = id;
                message.innerHTML = `Apakah Anda yakin ingin menghapus siswa <span class="font-semibold text-white">${name}</span>? Data yang dihapus tidak dapat dikembalikan.`;
            } else {
                const count = document.querySelectorAll('.student-checkbox:checked').length;
                message.innerHTML = `Apakah Anda yakin ingin menghapus <span class="font-semibold text-white">${count} siswa</span> terpilih? Data yang dihapus tidak dapat dikembalikan.`;
            }

            modal.classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
            pendingSingleId = null;
        }

        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            const form = document.getElementById('bulkDeleteForm');
            
            if (deleteMode === 'single' && pendingSingleId) {
                // Uncheck semua checkbox dulu
                studentCheckboxes.forEach(cb => cb.checked = false);
                
                // Cari atau buat hidden input khusus untuk single delete ID
                let hiddenInput = document.getElementById('singleDeleteInput');
                if (!hiddenInput) {
                    hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.id = 'singleDeleteInput';
                    hiddenInput.name = 'student_id';
                    form.appendChild(hiddenInput);
                }
                hiddenInput.value = pendingSingleId;
            }
            
            form.submit();
        });
    </script>
</body>
</html>