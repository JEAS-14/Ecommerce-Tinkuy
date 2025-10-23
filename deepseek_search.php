<?php
include 'assets/admin/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $query = trim($data['query'] ?? '');

    if (empty($query)) {
        echo json_encode([
            "texto" => "Por favor escribe algo para buscar.",
            "keyword" => "",
            "id_producto" => null
        ]);
        exit;
    }

    // --- Normalizamos texto ---
    $keyword = strtolower(rtrim($query, "s"));

    // --- Verificar si el producto existe ---
    $stmt = $conn->prepare("SELECT id_producto FROM productos WHERE LOWER(nombre_producto) LIKE CONCAT('%', ?, '%') LIMIT 1");
    $stmt->bind_param("s", $keyword);
    $stmt->execute();
    $res = $stmt->get_result();

    $id_producto = null;
    $existe = false;

    if ($row = $res->fetch_assoc()) {
        $id_producto = $row['id_producto'];
        $existe = true;
    }

    // --- Petición a DeepSeek (OpenRouter) ---
    $apiKey = "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";
    $ch = curl_init("https://openrouter.ai/api/v1/chat/completions");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer $apiKey"
    ]);

    $contexto = $existe 
        ? "El usuario está buscando un producto que existe en el catálogo de una tienda artesanal peruana."
        : "El usuario busca un producto artesanal, pero puede que no exista en el catálogo.";

    $payload = [
        "model" => "deepseek/deepseek-chat",
        "messages" => [
            ["role" => "system", "content" => "Eres un asistente experto en productos artesanales. Describe brevemente qué es el producto y su posible uso o material. No menciones la tienda."],
            ["role" => "user", "content" => "$contexto El producto es: $query"]
        ]
    ];

    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // --- Manejo de errores HTTP ---
    if ($http_code >= 400 || !$response) {
        echo json_encode([
            "texto" => "No se pudo obtener la información del asistente. Pero puedes explorar nuestro catálogo para descubrir productos similares.",
            "keyword" => $keyword,
            "id_producto" => $id_producto
        ]);
        exit;
    }

    $decoded = json_decode($response, true);
    $content = $decoded['choices'][0]['message']['content'] ?? "No se recibió explicación válida de la IA.";

    // --- Agregamos una nota si el producto existe ---
    if ($existe) {
        $content .= " Este producto está disponible en nuestra tienda.";
    } else {
        $content .= " No encontramos este producto, pero puedes ver opciones similares en nuestro catálogo.";
    }

    echo json_encode([
        "texto" => $content,
        "keyword" => $keyword,
        "id_producto" => $id_producto
    ]);
}
?>
