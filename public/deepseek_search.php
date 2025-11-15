<?php
// Endpoint público y autónomo para el asistente IA (DeepSeek via OpenRouter)

header('Content-Type: application/json');

function tinkuy_is_local_request() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    return $ip === '127.0.0.1' || $ip === '::1';
}

// Ruta de diagnóstico local: GET ?debug=1 — no expone secretos
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['debug']) && $_GET['debug'] === '1' && tinkuy_is_local_request()) {
    $root = realpath(__DIR__ . '/..');
    $envLocal = $root . DIRECTORY_SEPARATOR . '.env.local';
    $envFile  = $root . DIRECTORY_SEPARATOR . '.env';
    $existsLocal = is_readable($envLocal);
    $existsEnv   = is_readable($envFile);
    $hasEnvVar   = getenv('OPENROUTER_API_KEY') ? true : false;
    $demoVar     = getenv('OPENROUTER_DEMO') ? true : false;
    $probeLocal  = function_exists('tinkuy_load_env_key') ? (tinkuy_load_env_key($envLocal, 'OPENROUTER_API_KEY') ? true : false) : false;
    $probeEnv    = function_exists('tinkuy_load_env_key') ? (tinkuy_load_env_key($envFile, 'OPENROUTER_API_KEY') ? true : false) : false;
    echo json_encode([
        'root' => $root,
        'env_local_path' => $envLocal,
        'env_path' => $envFile,
        'env_local_readable' => $existsLocal,
        'env_readable' => $existsEnv,
        'has_env_var' => $hasEnvVar,
        'env_local_has_key' => $probeLocal,
        'env_has_key' => $probeEnv,
        'demo_env_set' => $demoVar,
        'note' => 'Este endpoint GET solo está disponible desde localhost y no muestra valores de claves.'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido. Use POST.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$query = isset($data['query']) ? trim($data['query']) : '';

if ($query === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Query vacío. Proporcione una consulta válida.']);
    exit;
}

// 1) Backend del compañero (si está configurado), prioridad sobre OpenRouter
$root = realpath(__DIR__ . '/..');
$backendUrl = getenv('TINKUY_AI_BACKEND_URL');
if (!$backendUrl) {
    if (!function_exists('tinkuy_load_env_key')) {
        // asegúrate de que la función exista más abajo antes de su uso; si no, definiremos una copia local minimal
    }
    $backendUrl = tinkuy_load_env_key($root . DIRECTORY_SEPARATOR . '.env.local', 'TINKUY_AI_BACKEND_URL')
               ?: tinkuy_load_env_key($root . DIRECTORY_SEPARATOR . '.env', 'TINKUY_AI_BACKEND_URL');
}

if ($backendUrl) {
    $ch = curl_init($backendUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [ 'Content-Type: application/json' ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['query' => $query]));
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErrNo = curl_errno($ch);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($curlErrNo) {
        http_response_code(502);
        echo json_encode(['error' => 'Error de red al contactar backend del compañero: ' . $curlErr]);
        exit;
    }
    if ($httpCode < 200 || $httpCode >= 300 || !$response) {
        http_response_code(502);
        echo json_encode(['error' => 'Backend del compañero devolvió estado no exitoso (' . $httpCode . ').']);
        exit;
    }
    $decoded = json_decode($response, true);
    if (is_array($decoded)) {
        // Mapeos tolerantes
        $texto = $decoded['texto'] ?? $decoded['content'] ?? $decoded['message'] ?? $decoded['text'] ?? '';
        $keyword = $decoded['keyword'] ?? $decoded['palabra'] ?? '';
        if ($texto === '' && is_string($response)) {
            $texto = $response;
        }
        if ($texto === '') $texto = 'Respuesta recibida del backend.';
        echo json_encode(['texto' => $texto, 'keyword' => $keyword]);
        exit;
    } else {
        // Si no es JSON, devolver como texto crudo
        $texto = is_string($response) ? $response : 'Respuesta no reconocida del backend.';
        echo json_encode(['texto' => $texto, 'keyword' => '']);
        exit;
    }
}

// Usar variable de entorno para la API key (más seguro). Defina OPENROUTER_API_KEY en su entorno.
function tinkuy_load_env_key($path, $key) {
    if (!is_readable($path)) {
        return null;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!$lines) return null;
    foreach ($lines as $line) {
        // Remover BOM UTF-8 si existe
        if (strncmp($line, "\xEF\xBB\xBF", 3) === 0) {
            $line = substr($line, 3);
        }
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $k = trim($parts[0]);
            if (strcasecmp($k, $key) === 0) {
                $v = trim($parts[1]);
                $v = trim($v, "\"' ");
                return $v;
            }
        }
    }
    return null;
}

// Flag global para habilitar/deshabilitar IA desde entorno/.env
$iaEnabled = getenv('TINKUY_AI_ENABLED');
if ($iaEnabled === false || $iaEnabled === '') {
    $rootFlag = realpath(__DIR__ . '/..');
    $iaEnabled = tinkuy_load_env_key($rootFlag . DIRECTORY_SEPARATOR . '.env.local', 'TINKUY_AI_ENABLED')
              ?: tinkuy_load_env_key($rootFlag . DIRECTORY_SEPARATOR . '.env', 'TINKUY_AI_ENABLED');
}
if (is_string($iaEnabled)) {
    $v = strtolower(trim($iaEnabled));
    if ($v === '0' || $v === 'false' || $v === 'off' ) {
        http_response_code(503);
        echo json_encode(['error' => 'IA deshabilitada por configuración (TINKUY_AI_ENABLED=0).']);
        exit;
    }
}

$apiKey = getenv('OPENROUTER_API_KEY');
if (!$apiKey) {
    // Fallback: leer desde .env.local o .env en la raíz del proyecto
    $root = realpath(__DIR__ . '/..');
    $apiKey = tinkuy_load_env_key($root . DIRECTORY_SEPARATOR . '.env.local', 'OPENROUTER_API_KEY')
           ?: tinkuy_load_env_key($root . DIRECTORY_SEPARATOR . '.env', 'OPENROUTER_API_KEY');
}
// Modo demo: permitir respuestas mock cuando no hay API key
$demoFlag = (isset($_GET['demo']) && $_GET['demo'] === '1');
if (!$apiKey && !$demoFlag) {
    // Leer demo flag desde entorno o .env
    $root = realpath(__DIR__ . '/..');
    $envDemo = getenv('OPENROUTER_DEMO');
    if (!$envDemo) {
        $envDemo = tinkuy_load_env_key($root . DIRECTORY_SEPARATOR . '.env.local', 'OPENROUTER_DEMO')
                ?: tinkuy_load_env_key($root . DIRECTORY_SEPARATOR . '.env', 'OPENROUTER_DEMO');
    }
    $demoFlag = ($envDemo === '1');
}

if (!$apiKey && $demoFlag) {
    // Respuesta simulada para demo sin API externa
    $lower = mb_strtolower($query, 'UTF-8');
    $keyword = '';
    if (strpos($lower, 'chompa') !== false || strpos($lower, 'frío') !== false) $keyword = 'chompa';
    elseif (strpos($lower, 'regalo') !== false) $keyword = 'collar';
    elseif (strpos($lower, 'collar') !== false) $keyword = 'collar';
    elseif (strpos($lower, 'joya') !== false || strpos($lower, 'joyería') !== false) $keyword = 'joyería';
    else {
        // toma la primera palabra significativa (>=4 letras) como aproximación
        $parts = preg_split('/\s+/', $lower);
        foreach ($parts as $p) { if (mb_strlen($p, 'UTF-8') >= 4) { $keyword = $p; break; } }
        if ($keyword === '' && !empty($parts)) $keyword = $parts[0];
    }
    $texto = 'Recomendación demo: tenemos opciones resaltadas para tu búsqueda. ¡Explora el catálogo!';
    echo json_encode(['texto' => $texto, 'keyword' => $keyword]);
    exit;
}

if (!$apiKey) {
    http_response_code(500);
    echo json_encode(['error' => 'Falta configurar OPENROUTER_API_KEY. Defina la variable de entorno o un archivo .env(.local).']);
    exit;
}

$ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKey,
]);

$payload = [
    'model' => 'deepseek/deepseek-chat',
    'messages' => [
        [
            'role' => 'system',
            'content' => 'Responde de forma breve y clara recomendando un producto del catálogo. Además, dame una sola palabra clave de búsqueda (ejemplo: "chompa"). Responde en JSON: {"texto":"<recomendacion>", "keyword":"<palabra>"}',
        ],
        [
            'role' => 'user',
            'content' => $query,
        ],
    ],
];

curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_TIMEOUT, 20);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErrNo = curl_errno($ch);
$curlErr = curl_error($ch);
curl_close($ch);

if ($curlErrNo) {
    http_response_code(502);
    echo json_encode(['error' => 'Error de red al contactar OpenRouter: ' . $curlErr]);
    exit;
}

if ($httpCode < 200 || $httpCode >= 300 || !$response) {
    http_response_code(502);
    echo json_encode(['error' => 'OpenRouter devolvió un estado no exitoso (' . $httpCode . ').']);
    exit;
}

$decoded = json_decode($response, true);
$content = $decoded['choices'][0]['message']['content'] ?? '';

// Limpiar posibles fences de markdown
$content = preg_replace('/```json\s*/i', '', $content);
$content = preg_replace('/```\s*$/', '', $content);
$content = trim($content);

$ia = json_decode($content, true);
if (is_array($ia) && isset($ia['texto'])) {
    $texto = $ia['texto'];
    $keyword = $ia['keyword'] ?? '';
} else {
    $texto = $content ?: 'No se recibió explicación de la IA.';
    $keyword = '';
}

echo json_encode([
    'texto' => $texto,
    'keyword' => $keyword,
]);
