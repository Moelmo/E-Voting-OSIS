CREATE DATABASE IF NOT EXISTS evoting_osis;
USE evoting_osis;

CREATE TABLE IF NOT EXISTS siswa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nis VARCHAR(50) UNIQUE NOT NULL,
    nama VARCHAR(100) NOT NULL,
    kelas VARCHAR(10) DEFAULT NULL,
    jurusan VARCHAR(30) DEFAULT NULL,
    role ENUM('admin', 'siswa') DEFAULT 'siswa',
    sudah_voting TINYINT(1) DEFAULT 0
);

CREATE TABLE IF NOT EXISTS kandidat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nomor_urut INT NOT NULL,
    nama_ketua VARCHAR(100) NOT NULL,
    nama_wakil VARCHAR(100) NOT NULL,
    foto VARCHAR(255) DEFAULT 'default.jpg',
    visi_misi TEXT,
    jumlah_suara INT DEFAULT 0
);

CREATE TABLE IF NOT EXISTS pengaturan_tampilan (
    id TINYINT UNSIGNED PRIMARY KEY,
    logo_1 VARCHAR(255) DEFAULT NULL,
    logo_1_aktif TINYINT(1) NOT NULL DEFAULT 1,
    logo_2 VARCHAR(255) DEFAULT NULL,
    logo_2_aktif TINYINT(1) NOT NULL DEFAULT 0,
    logo_3 VARCHAR(255) DEFAULT NULL,
    logo_3_aktif TINYINT(1) NOT NULL DEFAULT 0,
    teks_copyright VARCHAR(200) NOT NULL DEFAULT 'E-Voting OSIS. Hak cipta dilindungi.'
);

INSERT IGNORE INTO pengaturan_tampilan (id) VALUES (1);

CREATE TABLE IF NOT EXISTS pengaturan_rekap (
    id TINYINT UNSIGNED PRIMARY KEY,
    password_hash VARCHAR(255) NOT NULL
);

INSERT INTO siswa (nis, nama, kelas, jurusan, role) VALUES
    ('adminosis', 'Administrator OSIS', NULL, NULL, 'admin')
    -- ('24011835', 'TAUFIQ IMAN PUTRA', 'XII', 'TJKT', 'siswa'),
    -- ('24011834', 'TARAKA SUCI NAFISAPUTRA', 'XII', 'TJKT', 'siswa');


INSERT INTO kandidat (nomor_urut, nama_ketua, nama_wakil, visi_misi) VALUES
    (1, 'Andi Wijaya', 'Rina Lestari', 'Mewujudkan OSIS yang aktif, kreatif, dan berintegritas.'),
    (2, 'Fajar Pratama', 'Dewi Rahma', 'Meningkatkan prestasi siswa melalui kegiatan inovatif.');
