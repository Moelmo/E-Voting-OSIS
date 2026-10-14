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
        $sharedStrings = [];
        $sharedStringsXml = $archive->getFromName('xl/sharedStrings.xml');
        if ($sharedStringsXml !== false) {
            $sharedDocument = simplexml_load_string($sharedStringsXml, SimpleXMLElement::class, LIBXML_NONET);
            if ($sharedDocument === false) {
                throw new RuntimeException('Data shared strings pada XLSX tidak valid.');
            }

            $sharedStringItems = $sharedDocument->xpath('//*[local-name()="si"]') ?: [];
            foreach ($sharedStringItems as $item) {
                $text = '';
                $textNodes = $item->xpath('.//*[local-name()="t"]') ?: [];
                foreach ($textNodes as $textNode) {
                    $text .= (string)$textNode;
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

        $targetsById = [];
        $relationshipNodes = $relationshipsDocument->xpath('//*[local-name()="Relationship"]') ?: [];
        foreach ($relationshipNodes as $relationship) {
            $targetsById[(string)$relationship['Id']] = (string)$relationship['Target'];
        }

        $sheetNodes = $workbookDocument->xpath('//*[local-name()="sheets"]/*[local-name()="sheet"]') ?: [];
        $worksheetPaths = [];
        foreach ($sheetNodes as $sheet) {
            $relationshipIds = $sheet->xpath('./@*[local-name()="id"]') ?: [];
            $relationshipId = isset($relationshipIds[0]) ? (string)$relationshipIds[0] : '';
            $target = $targetsById[$relationshipId] ?? '';
            if ($target === '') {
                continue;
            }

            if (str_starts_with($target, '/')) {
                $path = ltrim($target, '/');
            } else {
                $path = 'xl/' . $target;
            }

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
            throw new RuntimeException($sheetNodes === []
                ? 'Workbook XLSX tidak memiliki worksheet yang dapat dibaca.'
                : 'Relasi worksheet pada file XLSX tidak ditemukan atau tidak valid. ID relasi: ' . implode(', ', array_keys($targetsById)));
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

            $rowNodes = $sheetDocument->xpath('//*[local-name()="sheetData"]/*[local-name()="row"]') ?: [];
            foreach ($rowNodes as $row) {
                $cells = [];
                $cellNodes = $row->xpath('./*[local-name()="c"]') ?: [];
                foreach ($cellNodes as $cell) {
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
                    if ($cellType === 'inlineStr') {
                        $value = '';
                        $textNodes = $cell->xpath('.//*[local-name()="t"]') ?: [];
                        foreach ($textNodes as $textNode) {
                            $value .= (string)$textNode;
                        }
                    } else {
                        $valueNodes = $cell->xpath('./*[local-name()="v"]') ?: [];
                        $value = isset($valueNodes[0]) ? (string)$valueNodes[0] : '';
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
                }
            }
        }
    } finally {
        $archive->close();
    }
}

function normalizeImportValue(mixed $value): string
{
    $value = str_replace(["\xEF\xBB\xBF", "\xC2\xA0"], ['', ' '], (string)$value);
    return trim($value);
}

function normalizeImportCategory(mixed $value): string
{
    $value = normalizeImportValue($value);
    $normalized = preg_replace('/\s+/u', ' ', $value);
    return strtoupper($normalized ?? $value);
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
$invalidRequired = 0;
$invalidKelas = 0;
$invalidJurusan = 0;

$pdo->beginTransaction();

try {
    $statement = $pdo->prepare("INSERT INTO siswa (nis, nama, kelas, jurusan, role, sudah_voting) VALUES (:nis, :nama, :kelas, :jurusan, 'siswa', 0) ON DUPLICATE KEY UPDATE nama = VALUES(nama), kelas = VALUES(kelas), jurusan = VALUES(jurusan)");
    $rows = $fileExtension === 'xlsx' ? readXlsxRows($fileTmpPath) : readCsvRows($fileTmpPath);

    foreach ($rows as $data) {
        if (strtolower(normalizeImportValue($data[0] ?? '')) === 'nis') {
            continue;
        }

        $nis = normalizeImportValue($data[0] ?? '');
        $nama = normalizeImportValue($data[1] ?? '');
        $kelas = normalizeImportCategory($data[2] ?? '');
        $jurusan = normalizeImportCategory($data[3] ?? '');

        $hasInvalidRequired = $nis === '' || $nama === '';
        $hasInvalidKelas = !in_array($kelas, $allowedKelas, true);
        $hasInvalidJurusan = !in_array($jurusan, $allowedJurusan, true);
        $invalidRequired += (int)$hasInvalidRequired;
        $invalidKelas += (int)$hasInvalidKelas;
        $invalidJurusan += (int)$hasInvalidJurusan;

        if ($hasInvalidRequired || $hasInvalidKelas || $hasInvalidJurusan) {
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
        'message' => "Import selesai. Berhasil: {$inserted} data, dilewati: {$skipped}. Masalah kolom: NIS/nama {$invalidRequired}, kelas {$invalidKelas}, jurusan {$invalidJurusan}."
    ];
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['student_flash'] = ['type' => 'error', 'message' => 'Terjadi kesalahan saat mengimpor data: ' . $e->getMessage()];
}

header('Location: students.php');
exit;