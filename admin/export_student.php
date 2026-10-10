<?php

declare(strict_types=1);

session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: students.php');
    exit;
}

require __DIR__ . '/../config/database.php';

try {
    $statement = $pdo->query("SELECT nis, nama, kelas, jurusan, sudah_voting FROM siswa WHERE role = 'siswa' ORDER BY kelas ASC, jurusan ASC, nama ASC");
    $siswaList = $statement->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['student_flash'] = ['type' => 'error', 'message' => 'Gagal mengambil data siswa.'];
    header('Location: students.php');
    exit;
}

$filename = "data_siswa_" . date('Y-m-d_H-i-s') . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// UTF-8 BOM agar angka/karakter dibaca rapi oleh MS Excel
fputs($output, "\xEF\xBB\xBF");

// Header kolom CSV
fputcsv($output, ['nis', 'nama', 'kelas', 'jurusan', 'sudah_voting'], ';');

foreach ($siswaList as $row) {
    fputcsv($output, [
        $row['nis'],
        $row['nama'],
        $row['kelas'],
        $row['jurusan'],
        $row['sudah_voting'] ? 'Sudah' : 'Belum'
    ], ';');
}

fclose($output);
exit;