<?php
// SI-NEW/file_exchange_management/pull_from_cap.php
declare(strict_types=1);

// Pfade + Logging
$BASE_DIR     = __DIR__;
$INCOMING_DIR = $BASE_DIR . '/incoming';
$LOG_DIR      = $BASE_DIR . '/logs';
@mkdir($INCOMING_DIR, 0775, true);
@mkdir($LOG_DIR, 0775, true);

$logFile = $LOG_DIR . '/pull-' . date('Ymd') . '.log';
function logmsg(string $m): void {
  global $logFile;
  file_put_contents($logFile, '['.date('Y-m-d H:i:s')."] $m\n", FILE_APPEND);
}

// Basis-Config
$CAP_BASE_URL = rtrim(getenv('CAP_BASE_URL') ?: 'http://localhost:4004', '/');

// >>> Nur noch funktionierende Endpunkte (REST klein; OData als Fallback)
$REST_CANDIDATES = [
  '/rest/api/products',
  '/odata/v4/simple-erp/Products?$format=json',  // optionaler Fallback (liefert {"value":[...]})
];

// Basic-Auth (laut deiner Doku)
$BASIC_USER = getenv('CAP_USER') ?: 'service-user';
$BASIC_PASS = getenv('CAP_PASS') ?: 'service-user';

// --- HTTP Helper: liefert [status,int, body,string]
function http_get_raw(string $url, string $user, string $pass): array {
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Accept: application/json'],
    CURLOPT_USERPWD        => $user . ':' . $pass,
    CURLOPT_HTTPAUTH       => CURLAUTH_BASIC,     // <<< Basic Auth erzwingen
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT        => 20,
  ]);
  $body = curl_exec($ch);
  if ($body === false) {
    $err = curl_error($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE) ?: 0;
    curl_close($ch);
    throw new RuntimeException("HTTP error for $url: $err (status=$status)");
  }
  $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  return [$status, $body];
}

// JSON/Array aus gängigen REST-/OData-Formaten extrahieren
function decode_items(string $body): array {
  $json = json_decode($body, true);
  if (is_array($json)) {
    if (array_is_list($json)) return $json;           // reines Array (REST)
    foreach (['items','value','data'] as $k) {
      if (isset($json[$k]) && is_array($json[$k])) return $json[$k]; // OData: value
    }
  }
  throw new RuntimeException('REST response is not a JSON array or known wrapper.');
}

// Preis "1200 EUR" / "2.300,50 €" → "1200.00" / "2300.50"
function normalize_price(string|int|float|null $v): ?string {
  if ($v === null) return null;
  $s = trim((string)$v);
  if (preg_match('/([0-9]+(?:[.,][0-9]{1,2})?)/', $s, $m)) {
    $num = str_replace(',', '.', $m[1]);
    return number_format((float)$num, 2, '.', '');
  }
  return null;
}

// Mapping einer Zeile (REST -> Importer)
function map_row(array $src): ?array {
  $dst = [
    'erp_id'      => $src['productID']   ?? $src['id'] ?? $src['ID'] ?? null,
    'name'        => $src['name']        ?? $src['title'] ?? null,
    'description' => $src['description'] ?? ($src['longText'] ?? ''),
    'price'       => normalize_price($src['price'] ?? null),
    'stock'       => (int)($src['stock'] ?? 0),
    'active'      => 1,
  ];
  if (!$dst['erp_id'] || !$dst['name'] || $dst['price'] === null) return null;
  return $dst;
}

// MAIN
try {
  $chosenUrl = null; $lastErr = null;
  foreach ($REST_CANDIDATES as $path) {
    $url = (str_starts_with($path, 'http') ? $path : $CAP_BASE_URL . $path);
    try {
      logmsg("Trying REST: $url");
      [$status, $body] = http_get_raw($url, $BASIC_USER, $BASIC_PASS);
      if ($status >= 200 && $status < 300) {
        $items = decode_items($body);
        logmsg("Using endpoint: $url (items=".count($items).")");
        $out = [];
        foreach ($items as $r) {
          if (!is_array($r)) continue;
          $m = map_row($r);
          if ($m) $out[] = $m; else logmsg('Skip row (missing fields): '.json_encode($r, JSON_UNESCAPED_UNICODE));
        }
        if (!$out) throw new RuntimeException('Keine gültigen Produktdatensätze gefunden.');
        $fn = $INCOMING_DIR . '/products-' . date('Ymd-His') . '.json';
        file_put_contents($fn, json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        logmsg("Wrote file: $fn (records=".count($out).")");
        echo "OK: Pulled ".count($out)." products from ".parse_url($url, PHP_URL_PATH)." to ".basename($fn).PHP_EOL;
        exit(0);
      } else {
        $lastErr = "HTTP $status for $url";
        logmsg($lastErr);
      }
    } catch (Throwable $e) {
      $lastErr = $e->getMessage();
      logmsg("Error on $url: ".$lastErr);
    }
  }
  throw new RuntimeException("No working REST endpoint found. Last error: ".$lastErr);
} catch (Throwable $e) {
  logmsg('ERROR: ' . $e->getMessage());
  fwrite(STDERR, "ERROR: ".$e->getMessage().PHP_EOL);
  exit(1);
}
