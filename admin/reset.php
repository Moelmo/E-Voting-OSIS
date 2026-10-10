<?php

declare(strict_types=1);

session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
	header('Location: ../index.php');
	exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	header('Location: dashboard.php');
	exit;
}

require __DIR__ . '/../config/database.php';

try {
	$pdo->beginTransaction();
	$pdo->exec('UPDATE kandidat SET jumlah_suara = 0');
	$pdo->exec("UPDATE siswa SET sudah_voting = 0 WHERE role = 'siswa'");
	$pdo->commit();
	header('Location: dashboard.php?success=' . rawurlencode('Semua voting berhasil direset.'));
	exit;
} catch (PDOException $exception) {
	if ($pdo->inTransaction()) {
		$pdo->rollBack();
	}
	header('Location: dashboard.php?error=' . rawurlencode('Reset voting gagal dilakukan.'));
	exit;
}
