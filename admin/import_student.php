<?php

declare(strict_types=1);

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: students.php');
    exit;
}

if (!isset($_FILES['file_excel']) || $_FILES['file_excel']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['student_flash'] = ['type' => 'error', 'message' => 'Pilih file CSV/Excel yang valid.'];
    header('Location: students.php');
    exit;
}

$fileTmpPath = $_FILES['file_excel']['tmp_name'];
$fileExtension = strtolower(pathinfo($_FILES['file_excel']['name'], PATHINFO_EXTENSION));

if (!in_array($fileExtension, ['csv', 'xlsx'], true)) {
    $_SESSION['student_flash'] = ['type' => 'error', 'message' => 'Format file harus .csv atau .xlsx.'];
    header('Location: students.php');
    exit;
}

require __DIR__ . '/../config/database.php';

function readCsvRows(string $filePath): Generator
{
    $handle = fopen($filePath, 'r');
    if ($handle === false) {
        throw new RuntimeException('Gagal membuka file CSV.');
    }

    try {
        $firstLine = fgets($handle);
        $delimiter = ($firstLine !== false && strpos($firstLine, ';') !== false) ? ';' : ',';
        rewind($handle);

        while (($row = fgetcsv($handle, 1000, $delimiter)) !== false) {
            yield $row;
        }
    } finally {
        fclose($handle);
    }
}

function readXlsxRows(string $filePath): Generator
{
    if (!class_exists(ZipArchive::class) || !function_exists('simplexml_load_string')) {
        throw new RuntimeException('Import XLSX membutuhkan ekstensi PHP ZipArchive dan SimpleXML.');
    }

    $archive = new ZipArchive();
    if ($archive->open($filePath) !== true) {
        throw new RuntimeException('File XLSX tidak dapat dibuka.');
    }

    try {
        $namespace = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
        $sharedStrings = [];
        $sharedStringsXml = $archive->getFromName('xl/sharedStrings.xml');
        if ($sharedStringsXml !== false) {
            $sharedDocument = simplexml_load_string($sharedStringsXml, SimpleXMLElement::class, LIBXML_NONET);
            if ($sharedDocument === false) {
                throw new RuntimeException('Data shared strings pada XLSX tidak valid.');
            }

            foreach ($sharedDocument->children($namespace)->si as $item) {
                $text = '';
                foreach ($item->children($namespace) as $part) {
                    if ($part->getName() === 't') {
                        $text .= (string)$part;
                    } elseif ($part->getName() === 'r') {
                        $text .= (string)$part->children($namespace)->t;
                    }
                }
                $sharedStrings[] = $text;
            }
        }

        $workbookXml = $archive->getFromName('xl/workbook.xml');
        $relationshipsXml = $archive->getFromName('xl/_rels/workbook.xml.rels');
        if ($workbookXml === false || $relationshipsXml === false) {
            throw new RuntimeException('Daftar worksheet pada file XLSX tidak ditemukan.');
        }

        $workbookDocument = simplexml_load_string($workbookXml, SimpleXMLElement::class, LIBXML_NONET);
        $relationshipsDocument = simplexml_load_string($relationshipsXml, SimpleXMLElement::class, LIBXML_NONET);
        if ($workbookDocument === false || $relationshipsDocument === false) {
            throw new RuntimeException('Daftar worksheet pada file XLSX tidak valid.');
        }

        $relationshipNamespace = 'http://schemas.openxmlformats.org/package/2006/relationships';
        $officeRelationshipNamespace = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
        $targetsById = [];
        foreach ($relationshipsDocument->children($relationshipNamespace)->Relationship as $relationship) {
            if ((string)$relationship['TargetMode'] !== 'External') {
                $targetsById[(string)$relationship['Id']] = (string)$relationship['Target'];
            }
        }

        $worksheetPaths = [];
        $sheets = $workbookDocument->children($namespace)->sheets->children($namespace)->sheet;
        foreach ($sheets as $sheet) {
            $relationshipId = (string)$sheet->attributes($officeRelationshipNamespace)['id'];
            $target = $targetsById[$relationshipId] ?? '';
            if ($target === '') {
                continue;
            }

            $path = str_starts_with($target, '/') ? ltrim($target, '/') : 'xl/' . $target;
            $normalizedParts = [];
            foreach (explode('/', $path) as $part) {
                if ($part === '' || $part === '.') {
                    continue;
                }
                if ($part === '..') {
                    array_pop($normalizedParts);
                    continue;
                }
                $normalizedParts[] = $part;
            }
            $worksheetPaths[] = implode('/', $normalizedParts);
        }

        if ($worksheetPaths === []) {
            throw new RuntimeException($sheets->count() === 0
                ? 'Workbook XLSX tidak memiliki worksheet yang dapat dibaca.'
                : 'Relasi worksheet pada file XLSX tidak ditemukan atau tidak valid.');
        }

        foreach ($worksheetPaths as $worksheetPath) {
            $sheetXml = $archive->getFromName($worksheetPath);
            if ($sheetXml === false) {
                throw new RuntimeException('Worksheet ' . $worksheetPath . ' tidak dapat dibaca.');
            }

            $sheetDocument = simplexml_load_string($sheetXml, SimpleXMLElement::class, LIBXML_NONET);
            if ($sheetDocument === false) {
                throw new RuntimeException('Data worksheet ' . $worksheetPath . ' tidak valid.');
            }

            foreach ($sheetDocument->children($namespace)->sheetData->row as $row) {
                $cells = [];
                foreach ($row->children($namespace)->c as $cell) {
                    $reference = (string)$cell['r'];
                    if (!preg_match('/^([A-Z]+)/', $reference, $matches)) {
                        continue;
                    }

                    $columnIndex = 0;
                    foreach (str_split($matches[1]) as $letter) {
                        $columnIndex = ($columnIndex * 26) + (ord($letter) - ord('A') + 1);
                    }
                    $columnIndex--;

                    $cellType = (string)$cell['t'];
                    $cellChildren = $cell->children($namespace);
                    if ($cellType === 'inlineStr') {
                        $value = (string)$cellChildren->is->children($namespace)->t;
                    } else {
                        $value = (string)$cellChildren->v;
                        if ($cellType === 's') {
                            $value = $sharedStrings[(int)$value] ?? '';
                        }
                    }
                    $cells[$columnIndex] = $value;
                }

                if ($cells !== []) {
                    ksort($cells);
                    $lastColumn = max(array_keys($cells));
                    $values = [];
                    for ($columnIndex = 0; $columnIndex <= $lastColumn; $columnIndex++) {
                        $values[] = $cells[$columnIndex] ?? '';
                    }
                    yield $values;
                } else {
                    yield [];
                }
            }
        }
    } finally {
        $archive->close();
    }
}

$allowedKelas = ['X', 'XI', 'XII'];
$allowedJurusan = [
    'TJKT-1', 'TJKT-2', 'TJKT-3', 'TJKT-4',
    'DKV-1', 'DKV-2', 'DKV-3',
    'DPK-1', 'DPK-2', 'DPK-3', 'DPK-4',
    'ANIMASI-1', 'ANIMASI-2',
    'BUSANA-1', 'BUSANA-2',
    'SENI RUPA'
];

$inserted = 0;
$skipped = 0;

$pdo->beginTransaction();

try {
    $statement = $pdo->prepare("INSERT INTO siswa (nis, nama, kelas, jurusan, role, sudah_voting) VALUES (:nis, :nama, :kelas, :jurusan, 'siswa', 0) ON DUPLICATE KEY UPDATE nama = VALUES(nama), kelas = VALUES(kelas), jurusan = VALUES(jurusan)");
    $rows = $fileExtension === 'xlsx' ? readXlsxRows($fileTmpPath) : readCsvRows($fileTmpPath);

    foreach ($rows as $data) {
        // Abaikan baris header jika ada
        if (strtolower(trim((string)($data[0] ?? ''))) === 'nis') {
            continue;
        }

        $nis = trim((string)($data[0] ?? ''));
        $nama = trim((string)($data[1] ?? ''));
        $kelas = trim((string)($data[2] ?? ''));
        $jurusan = trim((string)($data[3] ?? ''));

        if ($nis === '' || $nama === '' || !in_array($kelas, $allowedKelas, true) || !in_array($jurusan, $allowedJurusan, true)) {
            $skipped++;
            continue;
        }

        $statement->execute([
            'nis' => $nis,
            'nama' => $nama,
            'kelas' => $kelas,
            'jurusan' => $jurusan
        ]);
        $inserted++;
    }

    $pdo->commit();

    $_SESSION['student_flash'] = [
        'type' => 'success',
        'message' => "Import selesai. Berhasil: {$inserted} data, Dilewati/Gagal: {$skipped} data."
    ];
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['student_flash'] = ['type' => 'error', 'message' => 'Terjadi kesalahan saat mengimpor data: ' . $e->getMessage()];
}

header('Location: students.php');
exit;