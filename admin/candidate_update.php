<?php

declare(strict_types=1);

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
	header('Location: dashboard.php');
	exit;
}

$candidateId = filter_input(INPUT_POST, 'candidate_id', FILTER_VALIDATE_INT);
$namaKetua = trim((string) ($_POST['nama_ketua'] ?? ''));
$namaWakil = trim((string) ($_POST['nama_wakil'] ?? ''));
$description = trim((string) ($_POST['visi_misi'] ?? ''));

if ($candidateId === false || $candidateId === null || $candidateId < 1 || $namaKetua === '' || $namaWakil === '' || $description === '') {
	header('Location: dashboard.php?error=' . rawurlencode('Nama ketua, nama wakil, dan deskripsi wajib diisi.'));
	exit;
}

require __DIR__ . '/../config/database.php';

try {
	$photoName = null;
	$photo = $_FILES['foto'] ?? null;

	if ($photo !== null && $photo['error'] !== UPLOAD_ERR_NO_FILE) {
		if ($photo['error'] === UPLOAD_ERR_INI_SIZE || $photo['error'] === UPLOAD_ERR_FORM_SIZE) {
			throw new RuntimeException('Foto terlalu besar. Maksimal ukuran upload server terlampaui.');
		}

		if ($photo['error'] !== UPLOAD_ERR_OK || $photo['size'] > 5 * 1024 * 1024) {
			throw new RuntimeException('Foto tidak valid atau ukurannya lebih dari 5 MB.');
		}

		$imageInfo = getimagesize($photo['tmp_name']);
		$allowedMimeTypes = [
			'image/jpeg' => 'jpg',
			'image/png' => 'png',
			'image/webp' => 'webp',
		];

		if ($imageInfo === false || !isset($allowedMimeTypes[$imageInfo['mime']])) {
			throw new RuntimeException('Format foto harus JPG, PNG, atau WebP.');
		}

		$assetsDirectory = __DIR__ . '/../assets';
		$uploadDirectory = $assetsDirectory . '/uploads';
		$relativeDirectory = 'uploads';
		if (!is_dir($assetsDirectory) || !is_readable($assetsDirectory)) {
			throw new RuntimeException('Folder assets tidak ditemukan atau tidak dapat dibaca.');
		}
		if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0775, true) && !is_dir($uploadDirectory)) {
			$uploadDirectory = $assetsDirectory;
			$relativeDirectory = '';
		}
		if (!is_writable($uploadDirectory)) {
			@chmod($uploadDirectory, 0775);
		}
		if (!is_writable($uploadDirectory)) {
			throw new RuntimeException('Folder assets tidak memiliki izin tulis untuk Apache.');
		}

		$fileName = 'candidate-' . $candidateId . '-' . bin2hex(random_bytes(8)) . '.' . $allowedMimeTypes[$imageInfo['mime']];
		$photoName = ($relativeDirectory === '' ? '' : $relativeDirectory . '/') . $fileName;
		if (!move_uploaded_file($photo['tmp_name'], $uploadDirectory . '/' . $fileName)) {
			throw new RuntimeException('Foto gagal disimpan.');
		}
	}

	if ($photoName === null) {
		$statement = $pdo->prepare('UPDATE kandidat SET nama_ketua = :nama_ketua, nama_wakil = :nama_wakil, visi_misi = :visi_misi WHERE id = :id');
		$statement->execute(['nama_ketua' => $namaKetua, 'nama_wakil' => $namaWakil, 'visi_misi' => $description, 'id' => $candidateId]);
	} else {
		$statement = $pdo->prepare('UPDATE kandidat SET nama_ketua = :nama_ketua, nama_wakil = :nama_wakil, visi_misi = :visi_misi, foto = :foto WHERE id = :id');
		$statement->execute(['nama_ketua' => $namaKetua, 'nama_wakil' => $namaWakil, 'visi_misi' => $description, 'foto' => $photoName, 'id' => $candidateId]);
	}

	header('Location: dashboard.php?success=' . rawurlencode('Data paslon berhasil diperbarui.'));
	exit;
} catch (Throwable $exception) {
	header('Location: dashboard.php?error=' . rawurlencode($exception->getMessage()));
	exit;
}