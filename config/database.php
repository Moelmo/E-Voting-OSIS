<?php

declare(strict_types=1);

$host = getenv('DB_HOST') ?: 'localhost';
$port = getenv('DB_PORT') ?: '3306';
$database = getenv('DB_DATABASE') ?: 'evoting_osis';
$username = getenv('DB_USERNAME') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';

try {
	$connectionOptions = [
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
		PDO::ATTR_EMULATE_PREPARES => false,
	];

	$serverPdo = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $username, $password, $connectionOptions);
	$quotedDatabase = str_replace('`', '``', $database);
	$serverPdo->exec("CREATE DATABASE IF NOT EXISTS `{$quotedDatabase}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

	$pdo = new PDO("mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4", $username, $password, $connectionOptions);
	$pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS siswa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nis VARCHAR(50) UNIQUE NOT NULL,
    nama VARCHAR(100) NOT NULL,
	kelas VARCHAR(10) DEFAULT NULL,
	jurusan VARCHAR(30) DEFAULT NULL,
    role ENUM('admin', 'siswa') DEFAULT 'siswa',
    sudah_voting TINYINT(1) DEFAULT 0
)
SQL);
	$studentColumns = $pdo->query('SHOW COLUMNS FROM siswa')->fetchAll(PDO::FETCH_COLUMN);
	if (!in_array('kelas', $studentColumns, true)) {
		$pdo->exec("ALTER TABLE siswa ADD COLUMN kelas VARCHAR(10) DEFAULT NULL AFTER nama");
	}
	if (!in_array('jurusan', $studentColumns, true)) {
		$pdo->exec("ALTER TABLE siswa ADD COLUMN jurusan VARCHAR(30) DEFAULT NULL AFTER kelas");
	}
	$pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS kandidat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nomor_urut INT NOT NULL,
    nama_ketua VARCHAR(100) NOT NULL,
    nama_wakil VARCHAR(100) NOT NULL,
    foto VARCHAR(255) DEFAULT 'default.jpg',
    visi_misi TEXT,
    jumlah_suara INT DEFAULT 0
)
SQL);
	$pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS pengaturan_tampilan (
	id TINYINT UNSIGNED PRIMARY KEY,
	logo_1 VARCHAR(255) DEFAULT NULL,
	logo_1_aktif TINYINT(1) NOT NULL DEFAULT 1,
	logo_2 VARCHAR(255) DEFAULT NULL,
	logo_2_aktif TINYINT(1) NOT NULL DEFAULT 0,
	logo_3 VARCHAR(255) DEFAULT NULL,
	logo_3_aktif TINYINT(1) NOT NULL DEFAULT 0,
	teks_copyright VARCHAR(200) NOT NULL DEFAULT 'E-Voting OSIS. Hak cipta dilindungi.'
)
SQL);
	$pdo->exec("INSERT IGNORE INTO pengaturan_tampilan (id) VALUES (1)");
	$pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS pengaturan_rekap (
	id TINYINT UNSIGNED PRIMARY KEY,
	password_hash VARCHAR(255) NOT NULL
)
SQL);
	$recapPasswordStatement = $pdo->prepare('INSERT IGNORE INTO pengaturan_rekap (id, password_hash) VALUES (1, :password_hash)');
	$recapPasswordStatement->execute(['password_hash' => password_hash('admin', PASSWORD_DEFAULT)]);

	$pdo->exec(<<<'SQL'
INSERT IGNORE INTO siswa (nis, nama, kelas, jurusan, role) VALUES
	('adminosis', 'Administrator OSIS', NULL, NULL, 'admin')
    -- ('24011835', 'TAUFIQ IMAN PUTRA', 'XII', 'TJKT', 'siswa'),
    -- ('24011834', 'TARAKA SUCI NAFISAPUTRA', 'XII', 'TJKT', 'siswa');
SQL);
	$pdo->exec(<<<'SQL'
INSERT INTO kandidat (nomor_urut, nama_ketua, nama_wakil, visi_misi)
SELECT 1, 'Andi Wijaya', 'Rina Lestari', 'Mewujudkan OSIS yang aktif, kreatif, dan berintegritas.'
WHERE NOT EXISTS (SELECT 1 FROM kandidat WHERE nomor_urut = 1)
SQL);
	$pdo->exec(<<<'SQL'
INSERT INTO kandidat (nomor_urut, nama_ketua, nama_wakil, visi_misi)
SELECT 2, 'Fajar Pratama', 'Dewi Rahma', 'Meningkatkan prestasi siswa melalui kegiatan inovatif.'
WHERE NOT EXISTS (SELECT 1 FROM kandidat WHERE nomor_urut = 2)
SQL);
} catch (PDOException $exception) {
	http_response_code(500);
	exit('Koneksi database gagal. Periksa konfigurasi server.');
}