<?php
// SI-NEW/file_exchange_management/import_products.php
declare(strict_types=1);

// --- Pfade ---
$BASE_DIR      = __DIR__;
$INCOMING_DIR  = $BASE_DIR . '/incoming';
$PROCESSED_DIR = $BASE_DIR . '/processed';
$ERROR_DIR     = $BASE_DIR . '/error';
$LOG_DIR       = $BASE_DIR . '/logs';

// --- Logging ---
@mkdir($LOG_DIR, 0775, true);
$logFile = $LOG_DIR . '/importer-' . date('Ymd') . '.log';
function logmsg(string $msg): void {
    global $logFile;
    file_put_contents($logFile, '['.date('Y-m-d H:i:s')."] $msg\n", FILE_APPEND);
}

// --- DB ---
require_once __DIR__ . '/../db_settings/db.php'; // stellt getPDO() bereit
$pdo = getPDO();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// --- Helpers ---
function isCsv(string $fn): bool { return (bool)preg_match('/\.csv$/i', $fn); }
function isJson(string $fn): bool { return (bool)preg_match('/\.json$/i', $fn); }

function normalizeRow(array $row): array {
    // Keys vereinheitlichen
    $row = array_change_key_case($row, CASE_LOWER);

    // Pflichtfelder prüfen
    foreach (['erp_id','name','price'] as $k) {
        if (!isset($row[$k]) || $row[$k] === '') {
            throw new RuntimeException("Missing required field: $k");
        }
    }

    // Preis normalisieren (optional: Komma zu Punkt)
    if (isset($row['price'])) {
        $row['price'] = str_replace(',', '.', (string)$row['price']);
        if (!is_numeric($row['price'])) {
            throw new RuntimeException("Invalid price for erp_id {$row['erp_id']}");
        }
        $row['price'] = number_format((float)$row['price'], 2, '.', '');
    }

    // Defaults & Typen
    $row['description'] = $row['description'] ?? '';
    $row['stock']       = isset($row['stock']) && $row['stock'] !== '' ? (int)$row['stock'] : null;
    $row['active']      = isset($row['active']) ? (int)!!$row['active'] : null;

    return $row;
}

function upsertProduct(PDO $pdo, array $data): void {
    $pdo->beginTransaction();
    try {
        $exists = $pdo->prepare("SELECT product_id FROM products WHERE erp_id = :erp_id");
        $exists->execute([':erp_id' => $data['erp_id']]);
        $row = $exists->fetch();

        if ($row) {
            // UPDATE – nur gelieferte Felder überschreiben
            $updates = [
                'name = :name',
                'description = :description',
                'price = :price',
            ];
            $params = [
                ':name'        => $data['name'],
                ':description' => $data['description'],
                ':price'       => $data['price'],
                ':erp_id'      => $data['erp_id'],
            ];
            if ($data['stock'] !== null) { $updates[] = 'stock = :stock';   $params[':stock']  = $data['stock']; }
            if ($data['active'] !== null){ $updates[] = 'active = :active'; $params[':active'] = $data['active']; }

            $sql = "UPDATE products SET ".implode(', ', $updates)." WHERE erp_id = :erp_id";
            $pdo->prepare($sql)->execute($params);
        } else {
            // INSERT – optionale Felder auf Defaults
            $sql = "INSERT INTO products (erp_id, name, description, price, stock, active)
                    VALUES (:erp_id, :name, :description, :price, :stock, :active)";
            $pdo->prepare($sql)->execute([
                ':erp_id'      => $data['erp_id'],
                ':name'        => $data['name'],
                ':description' => $data['description'],
                ':price'       => $data['price'],
                ':stock'       => $data['stock']  ?? 0,
                ':active'      => $data['active'] ?? 1,
            ]);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function parseCsv(string $file): array {
    $rows = [];
    if (($h = fopen($file, 'r')) === false) throw new RuntimeException('Cannot open CSV');
    $header = null;
    while (($data = fgetcsv($h, 0, ';')) !== false) {
        if ($header === null) {
            $header = array_map(fn($v) => trim(mb_strtolower((string)$v)), $data);
            continue;
        }
        if (count($data) === 1 && trim((string)$data[0]) === '') continue;
        $row = array_combine($header, array_map(fn($v)=> is_string($v)?trim($v):$v, $data));
        $rows[] = $row;
    }
    fclose($h);
    return $rows;
}

function parseJson(string $file): array {
    $txt = file_get_contents($file);
    $arr = json_decode($txt, true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($arr)) throw new RuntimeException('Invalid JSON');
    return $arr;
}

function moveTo(string $src, string $dstDir): void {
    @mkdir($dstDir, 0775, true);
    $base = basename($src);
    @rename($src, $dstDir . '/' . $base);
}

// --- Sicherstellen, dass Ordner existieren ---
@mkdir($INCOMING_DIR, 0775, true);
@mkdir($PROCESSED_DIR, 0775, true);
@mkdir($ERROR_DIR, 0775, true);

// --- Dateien einsammeln ---
$files = array_values(array_filter(
    array_map(fn($f) => $INCOMING_DIR.'/'.$f, array_diff(scandir($INCOMING_DIR), ['.','..'])),
    fn($f) => is_file($f) && (isCsv($f) || isJson($f))
));

if (!$files) {
    logmsg('No files to process.');
    exit(0);
}

// --- Hauptlauf ---
foreach ($files as $file) {
    $ok = 0; $err = 0; $errors = [];
    logmsg("Processing $file ...");
    try {
        $rows = isCsv($file) ? parseCsv($file) : parseJson($file);

        foreach ($rows as $i => $raw) {
            try {
                $row = normalizeRow($raw);
                upsertProduct($pdo, $row);
                $ok++;
            } catch (Throwable $e) {
                $err++;
                $errors[] = "Row ".($i+1).": ".$e->getMessage();
            }
        }

        if ($err === 0) {
            moveTo($file, $PROCESSED_DIR);
            logmsg("DONE  $file – imported: $ok, errors: 0");
        } else {
            $errTxt = "Errors while importing ".basename($file)."\n\n".implode("\n", $errors)."\n";
            @file_put_contents($ERROR_DIR.'/'.basename($file).'.errors.txt', $errTxt);
            moveTo($file, $ERROR_DIR);
            logmsg("FAILED $file – imported: $ok, errors: $err (moved to error/)");
        }
    } catch (Throwable $e) {
        @file_put_contents($ERROR_DIR.'/'.basename($file).'.fatal.txt', $e->getMessage()."\n");
        moveTo($file, $ERROR_DIR);
        logmsg("FATAL $file – ".$e->getMessage());
    }
}
