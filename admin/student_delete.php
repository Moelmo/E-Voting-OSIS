<?php

declare(strict_types=1);

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: students.php');
    exit;
}

require __DIR__ . '/../config/database.php';

// Ambil input hapus masal (array) atau hapus tunggal (int)
$studentIdsRaw = $_POST['student_ids'] ?? null;
$studentIdRaw = $_POST['student_id'] ?? null;

$targetIds = [];

if (is_array($studentIdsRaw)) {
    foreach ($studentIdsRaw as $id) {
        $filtered = filter_var($id, FILTER_VALIDATE_INT);
        if ($filtered !== false && $filtered !== null && $filtered > 0) {
            $targetIds[] = (int) $filtered;
        }
    }
} elseif ($studentIdRaw !== null) {
    $filtered = filter_var($studentIdRaw, FILTER_VALIDATE_INT);
    if ($filtered !== false && $filtered !== null && $filtered > 0) {
        $targetIds[] = (int) $filtered;
    }
}

if (empty($targetIds)) {
    $_SESSION['student_flash'] = ['type' => 'error', 'message' => 'Pilih setidaknya satu siswa yang valid untuk dihapus.'];
    header('Location: students.php');
    exit;
}

try {
    $pdo->beginTransaction();

    // Re-verify ketersediaan siswa dengan role 'siswa'
    $inClause = implode(',', array_fill(0, count($targetIds), '?'));
    $findStatement = $pdo->prepare("SELECT id FROM siswa WHERE role = 'siswa' AND id IN ($inClause)");
    $findStatement->execute($targetIds);
    $validIds = $findStatement->fetchAll(PDO::FETCH_COLUMN);

    if (empty($validIds)) {
        $pdo->rollBack();
        $_SESSION['student_flash'] = ['type' => 'error', 'message' => 'Siswa tidak ditemukan atau bukan merupakan akun siswa.'];
        header('Location: students.php');
        exit;
    }

    // Proses Hapus
    $deleteInClause = implode(',', array_fill(0, count($validIds), '?'));
    $deleteStatement = $pdo->prepare("DELETE FROM siswa WHERE role = 'siswa' AND id IN ($deleteInClause)");
    $deleteStatement->execute($validIds);

    // Verifikasi penyeimbangan hapus
    $verifyStatement = $pdo->prepare("SELECT COUNT(*) FROM siswa WHERE id IN ($deleteInClause)");
    $verifyStatement->execute($validIds);
    if ((int) $verifyStatement->fetchColumn() !== 0) {
        throw new RuntimeException('Database tidak berhasil menghapus data siswa.');
    }

    $pdo->commit();

    $count = count($validIds);
    $message = $count === 1 
        ? 'Data siswa berhasil dihapus.' 
        : "Sebanyak {$count} data siswa berhasil dihapus.";

    $_SESSION['student_flash'] = ['type' => 'success', 'message' => $message];
    header('Location: students.php');
    exit;
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['student_flash'] = ['type' => 'error', 'message' => 'Data siswa gagal dihapus: ' . $exception->getMessage()];
    header('Location: students.php');
    exit;
}