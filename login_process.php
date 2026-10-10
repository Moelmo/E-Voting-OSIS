<?php

declare(strict_types=1);

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	header('Location: index.php');
	exit;
}

$nis = trim((string) ($_POST['nis'] ?? ''));

if ($nis === '') {
	header('Location: index.php?error=' . rawurlencode('NIS wajib diisi.'));
	exit;
}

require __DIR__ . '/config/database.php';

$statement = $pdo->prepare('SELECT id, nis, nama, kelas, jurusan, role, sudah_voting FROM siswa WHERE nis = :nis LIMIT 1');
$statement->execute(['nis' => $nis]);
$student = $statement->fetch();

if ($student === false) {
	header('Location: index.php?error=' . rawurlencode('NIS tidak ditemukan. Silakan hubungi panitia.'));
	exit;
}

if ($student['role'] === 'siswa' && (int) $student['sudah_voting'] === 1) {
	header('Location: index.php?error=' . rawurlencode('NIS ini sudah digunakan untuk memilih.'));
	exit;
}

session_regenerate_id(true);
$_SESSION['user'] = [
	'id' => (int) $student['id'],
	'nis' => $student['nis'],
	'nama' => $student['nama'],
	'kelas' => $student['kelas'],
	'jurusan' => $student['jurusan'],
	'role' => $student['role'],
];

$destination = $student['role'] === 'admin' ? 'admin/dashboard.php' : 'voting.php';
header("Location: {$destination}");
exit;
