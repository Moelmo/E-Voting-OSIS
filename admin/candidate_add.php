<?php

declare(strict_types=1);

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
	header('Location: candidates.php');
	exit;
}

$namaKetua = trim((string) ($_POST['nama_ketua'] ?? ''));
$namaWakil = trim((string) ($_POST['nama_wakil'] ?? ''));
$description = trim((string) ($_POST['visi_misi'] ?? ''));

if ($namaKetua === '' || $namaWakil === '' || $description === '') {
	header('Location: candidates.php?error=' . rawurlencode('Nama ketua, nama wakil, dan visi misi wajib diisi.'));
	exit;
}

require __DIR__ . '/../config/database.php';

try {
	$count = (int) $pdo->query('SELECT COUNT(*) FROM kandidat')->fetchColumn();
	if ($count >= 4) {
		throw new RuntimeException('Maksimal 4 paslon sudah tercapai.');
	}

	$photoName = 'default.jpg';
	$photo = $_FILES['foto'] ?? null;
	if ($photo !== null && $photo['error'] !== UPLOAD_ERR_NO_FILE) {
		if ($photo['error'] !== UPLOAD_ERR_OK || $photo['size'] > 5 * 1024 * 1024) {
			throw new RuntimeException('Foto tidak valid atau ukurannya lebih dari 5 MB.');
		}
		$imageInfo = getimagesize($photo['tmp_name']);
		$allowedMimeTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
		if ($imageInfo === false || !isset($allowedMimeTypes[$imageInfo['mime']])) {
			throw new RuntimeException('Format foto harus JPG, PNG, atau WebP.');
		}
		$assetsDirectory = __DIR__ . '/../assets';
		$uploadDirectory = $assetsDirectory . '/uploads';
		$relativeDirectory = 'uploads';
		if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0775, true) && !is_dir($uploadDirectory)) {
			$uploadDirectory = $assetsDirectory;
			$relativeDirectory = '';
		}
		if (!is_writable($uploadDirectory)) {
			throw new RuntimeException('Folder assets tidak memiliki izin tulis untuk Apache.');
		}
		$fileName = 'candidate-' . bin2hex(random_bytes(8)) . '.' . $allowedMimeTypes[$imageInfo['mime']];
		if (!move_uploaded_file($photo['tmp_name'], $uploadDirectory . '/' . $fileName)) {
			throw new RuntimeException('Foto gagal disimpan.');
		}
		$photoName = ($relativeDirectory === '' ? '' : $relativeDirectory . '/') . $fileName;
	}

	$statement = $pdo->prepare('INSERT INTO kandidat (nomor_urut, nama_ketua, nama_wakil, foto, visi_misi, jumlah_suara) VALUES (:nomor_urut, :nama_ketua, :nama_wakil, :foto, :visi_misi, 0)');
	$statement->execute([
		'nomor_urut' => $count + 1,
		'nama_ketua' => $namaKetua,
		'nama_wakil' => $namaWakil,
		'foto' => $photoName,
		'visi_misi' => $description,
	]);
	header('Location: candidates.php?success=' . rawurlencode('Paslon berhasil ditambahkan.'));
	exit;
} catch (Throwable $exception) {
	header('Location: candidates.php?error=' . rawurlencode($exception->getMessage()));
	exit;
}