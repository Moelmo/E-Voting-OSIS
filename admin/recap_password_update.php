<?php

declare(strict_types=1);

session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
	header('Location: ../index.php');
	exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	header('Location: recap.php');
	exit;
}

$csrfToken = (string) ($_POST['csrf_token'] ?? '');
if (!hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $csrfToken)) {
	header('Location: recap.php?error=' . rawurlencode('Permintaan tidak valid. Muat ulang halaman dan coba lagi.'));
	exit;
}

require __DIR__ . '/../config/database.php';

$currentPassword = (string) ($_POST['current_password'] ?? '');
$newPassword = (string) ($_POST['new_password'] ?? '');
$confirmPassword = (string) ($_POST['confirm_password'] ?? '');
$passwordHash = $pdo->query('SELECT password_hash FROM pengaturan_rekap WHERE id = 1')->fetchColumn();

if (!is_string($passwordHash) || !password_verify($currentPassword, $passwordHash)) {
	header('Location: recap.php?error=' . rawurlencode('Password saat ini salah.'));
	exit;
}

if (strlen($newPassword) < 8) {
	header('Location: recap.php?error=' . rawurlencode('Password baru minimal 8 karakter.'));
	exit;
}

if ($newPassword !== $confirmPassword) {
	header('Location: recap.php?error=' . rawurlencode('Konfirmasi password baru tidak cocok.'));
	exit;
}

$statement = $pdo->prepare('UPDATE pengaturan_rekap SET password_hash = :password_hash WHERE id = 1');
$statement->execute(['password_hash' => password_hash($newPassword, PASSWORD_DEFAULT)]);

header('Location: recap.php?success=' . rawurlencode('Password spoiler berhasil diubah.'));
exit;