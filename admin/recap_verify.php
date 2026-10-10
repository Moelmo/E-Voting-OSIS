<?php

declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
	http_response_code(403);
	echo json_encode(['success' => false]);
	exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	http_response_code(405);
	header('Allow: POST');
	echo json_encode(['success' => false]);
	exit;
}

$csrfToken = (string) ($_POST['csrf_token'] ?? '');
if (!hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $csrfToken)) {
	http_response_code(403);
	echo json_encode(['success' => false]);
	exit;
}

require __DIR__ . '/../config/database.php';

$passwordHash = $pdo->query('SELECT password_hash FROM pengaturan_rekap WHERE id = 1')->fetchColumn();

$password = (string) ($_POST['password'] ?? '');
if (!is_string($passwordHash) || !password_verify($password, $passwordHash)) {
	http_response_code(401);
	echo json_encode(['success' => false]);
	exit;
}

$candidates = $pdo->query('SELECT nomor_urut, jumlah_suara FROM kandidat ORDER BY nomor_urut ASC')->fetchAll();

echo json_encode([
	'success' => true,
	'candidates' => array_map(static fn (array $candidate): array => [
		'nomor_urut' => (int) $candidate['nomor_urut'],
		'jumlah_suara' => (int) $candidate['jumlah_suara'],
	], $candidates),
]);