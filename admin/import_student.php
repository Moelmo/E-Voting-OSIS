<?php

declare(strict_types=1);

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: students.php');
    exit;
}

if (!isset($_FILES['file_excel']) || $_FILES['file_excel']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['student_flash'] = ['type' => 'error', 'message' => 'Pilih file CSV/Excel yang valid.'];
    header('Location: students.php');
    exit;
}

$fileTmpPath = $_FILES['file_excel']['tmp_name'];
$fileExtension = strtolower(pathinfo($_FILES['file_excel']['name'], PATHINFO_EXTENSION));

if ($fileExtension !== 'csv') {
    $_SESSION['student_flash'] = ['type' => 'error', 'message' => 'Format file harus .csv (Simpan Excel sebagai CSV).'];
    header('Location: students.php');
    exit;
}

require __DIR__ . '/../config/database.php';

$allowedKelas = ['X', 'XI', 'XII'];
$allowedJurusan = [
    'TJKT-1', 'TJKT-2', 'TJKT-3', 'TJKT-4',
    'DKV-1', 'DKV-2', 'DKV-3',
    'DPK-1', 'DPK-2', 'DPK-3', 'DPK-4',
    'ANIMASI-1', 'ANIMASI-2',
    'BUSANA-1', 'BUSANA-2',
    'SENI RUPA'
];

$handle = fopen($fileTmpPath, 'r');
if ($handle === false) {
    $_SESSION['student_flash'] = ['type' => 'error', 'message' => 'Gagal membuka file.'];
    header('Location: students.php');
    exit;
}

$inserted = 0;
$skipped = 0;

$pdo->beginTransaction();

try {
    $statement = $pdo->prepare("INSERT INTO siswa (nis, nama, kelas, jurusan, role, sudah_voting) VALUES (:nis, :nama, :kelas, :jurusan, 'siswa', 0) ON DUPLICATE KEY UPDATE nama = VALUES(nama), kelas = VALUES(kelas), jurusan = VALUES(jurusan)");

    // Deteksi separator (koma atau titik koma)
    $firstLine = fgets($handle);
    $delimiter = (strpos($firstLine, ';') !== false) ? ';' : ',';
    rewind($handle);

    while (($data = fgetcsv($handle, 1000, $delimiter)) !== false) {
        // Abaikan baris header jika ada
        if (strtolower(trim((string)$data[0])) === 'nis') {
            continue;
        }

        $nis = trim((string)($data[0] ?? ''));
        $nama = trim((string)($data[1] ?? ''));
        $kelas = trim((string)($data[2] ?? ''));
        $jurusan = trim((string)($data[3] ?? ''));

        if ($nis === '' || $nama === '' || !in_array($kelas, $allowedKelas, true) || !in_array($jurusan, $allowedJurusan, true)) {
            $skipped++;
            continue;
        }

        $statement->execute([
            'nis' => $nis,
            'nama' => $nama,
            'kelas' => $kelas,
            'jurusan' => $jurusan
        ]);
        $inserted++;
    }

    fclose($handle);
    $pdo->commit();

    $_SESSION['student_flash'] = [
        'type' => 'success',
        'message' => "Import selesai. Berhasil: {$inserted} data, Dilewati/Gagal: {$skipped} data."
    ];
} catch (Exception $e) {
    $pdo->rollBack();
    fclose($handle);
    $_SESSION['student_flash'] = ['type' => 'error', 'message' => 'Terjadi kesalahan saat mengimpor data: ' . $e->getMessage()];
}

header('Location: students.php');
exit;