<?php

declare(strict_types=1);

session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
	http_response_code(403);
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode(['error' => 'Unauthorized']);
	exit;
}

require __DIR__ . '/../config/database.php';

$statement = $pdo->query('SELECT nomor_urut, nama_ketua, nama_wakil, jumlah_suara FROM kandidat ORDER BY nomor_urut ASC');
$candidates = $statement->fetchAll();
$summaryStatement = $pdo->query("SELECT COUNT(*) AS total, COALESCE(SUM(sudah_voting = 1), 0) AS sudah_voting FROM siswa WHERE role = 'siswa'");
$summary = $summaryStatement->fetch() ?: ['total' => 0, 'sudah_voting' => 0];

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
	'total' => (int) $summary['total'],
	'sudah_voting' => (int) $summary['sudah_voting'],
	'belum_voting' => (int) $summary['total'] - (int) $summary['sudah_voting'],
	'candidates' => array_map(static fn (array $candidate): array => [
		'label' => 'No. ' . $candidate['nomor_urut'] . ' - ' . $candidate['nama_ketua'],
		'votes' => (int) $candidate['jumlah_suara'],
	], $candidates),
]);