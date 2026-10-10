<?php

declare(strict_types=1);

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
	header('Location: students.php');
	exit;
}

$nis = trim((string) ($_POST['nis'] ?? ''));
$nama = trim((string) ($_POST['nama'] ?? ''));
$kelas = trim((string) ($_POST['kelas'] ?? ''));
$jurusan = trim((string) ($_POST['jurusan'] ?? ''));
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

if ($nis === '' || $nama === '' || !in_array($kelas, $allowedKelas, true) || !in_array($jurusan, $allowedJurusan, true)) {
	$_SESSION['student_flash'] = ['type' => 'error', 'message' => 'NIS, nama, kelas, dan jurusan wajib diisi dengan benar.'];
	header('Location: students.php');
	exit;
}

require __DIR__ . '/../config/database.php';

try {
	$statement = $pdo->prepare("INSERT INTO siswa (nis, nama, kelas, jurusan, role, sudah_voting) VALUES (:nis, :nama, :kelas, :jurusan, 'siswa', 0)");
	$statement->execute(['nis' => $nis, 'nama' => $nama, 'kelas' => $kelas, 'jurusan' => $jurusan]);
	$_SESSION['student_flash'] = ['type' => 'success', 'message' => 'Data siswa berhasil ditambahkan.'];
	header('Location: students.php');
	exit;
} catch (PDOException $exception) {
	$message = $exception->getCode() === '23000' ? 'NIS sudah terdaftar.' : 'Data siswa gagal ditambahkan.';
	$_SESSION['student_flash'] = ['type' => 'error', 'message' => $message];
	header('Location: students.php');
	exit;
}