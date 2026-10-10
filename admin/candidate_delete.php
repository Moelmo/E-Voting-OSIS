<?php

declare(strict_types=1);

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
	header('Location: candidates.php');
	exit;
}

$candidateId = filter_input(INPUT_POST, 'candidate_id', FILTER_VALIDATE_INT);
if ($candidateId === false || $candidateId === null || $candidateId < 1) {
	header('Location: candidates.php?error=' . rawurlencode('Paslon tidak valid.'));
	exit;
}

require __DIR__ . '/../config/database.php';

try {
	$count = (int) $pdo->query('SELECT COUNT(*) FROM kandidat')->fetchColumn();
	if ($count <= 1) {
		throw new RuntimeException('Minimal harus ada satu paslon.');
	}
	$statement = $pdo->prepare('DELETE FROM kandidat WHERE id = :id');
	$statement->execute(['id' => $candidateId]);
	if ($statement->rowCount() === 0) {
		throw new RuntimeException('Paslon tidak ditemukan.');
	}

	$pdo->exec('SET @candidate_number = 0');
	$pdo->exec('UPDATE kandidat SET nomor_urut = (@candidate_number := @candidate_number + 1) ORDER BY nomor_urut ASC, id ASC');
	header('Location: candidates.php?success=' . rawurlencode('Paslon berhasil dihapus.'));
	exit;
} catch (Throwable $exception) {
	header('Location: candidates.php?error=' . rawurlencode($exception->getMessage()));
	exit;
}