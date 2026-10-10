<?php

declare(strict_types=1);

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'siswa') {
	header('Location: index.php');
	exit;
}

$candidateId = filter_input(INPUT_POST, 'kandidat_id', FILTER_VALIDATE_INT);
if ($candidateId === false || $candidateId === null || $candidateId < 1) {
	header('Location: voting.php?error=' . rawurlencode('Pilih salah satu kandidat terlebih dahulu.'));
	exit;
}

require __DIR__ . '/config/database.php';

try {
	$pdo->beginTransaction();

	$studentStatement = $pdo->prepare('SELECT sudah_voting FROM siswa WHERE id = :id FOR UPDATE');
	$studentStatement->execute(['id' => $_SESSION['user']['id']]);
	$student = $studentStatement->fetch();

	$candidateStatement = $pdo->prepare('SELECT id FROM kandidat WHERE id = :id LIMIT 1');
	$candidateStatement->execute(['id' => $candidateId]);

	if ($student === false) {
		$pdo->rollBack();
		header('Location: voting.php?error=' . rawurlencode('Suara tidak dapat diproses. Silakan coba lagi.'));
		exit;
	}

	if ((int) $student['sudah_voting'] === 1) {
		$pdo->rollBack();
		session_destroy();
		header('Location: index.php?error=' . rawurlencode('Anda sudah voting. Suara tidak dapat diproses lagi.'));
		exit;
	}

	if ($candidateStatement->fetch() === false) {
		$pdo->rollBack();
		header('Location: voting.php?error=' . rawurlencode('Kandidat tidak ditemukan. Silakan coba lagi.'));
		exit;
	}

	$voteStatement = $pdo->prepare('UPDATE kandidat SET jumlah_suara = jumlah_suara + 1 WHERE id = :id');
	$voteStatement->execute(['id' => $candidateId]);
	$markStudentStatement = $pdo->prepare('UPDATE siswa SET sudah_voting = 1 WHERE id = :id');
	$markStudentStatement->execute(['id' => $_SESSION['user']['id']]);
	$pdo->commit();

	session_destroy();
	header('Location: index.php?success=' . rawurlencode('Suara kamu berhasil dicatat. Terima kasih sudah memilih.'));
	exit;
} catch (PDOException $exception) {
	if ($pdo->inTransaction()) {
		$pdo->rollBack();
	}
	header('Location: voting.php?error=' . rawurlencode('Terjadi kendala saat menyimpan suara.'));
	exit;
}
