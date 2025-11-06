<?php
include 'assets/admin/db.php'; // Asegúrate que la ruta sea correcta

header('Content-Type: application/json'); // Indicar que la respuesta es JSON

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $query = trim($data['query'] ?? '');

    // Inicializar respuesta base
    $response_data = [
        "texto_ia" => "Lo siento, no pude procesar la solicitud.", // Descripción de la IA
        "mensaje_tienda" => "", // Mensaje sobre disponibilidad
        "enlace_tienda" => null, // URL del enlace (al producto o catálogo)
        "texto_enlace" => ""    // Texto para el enlace (ej. "Ver producto", "Explorar catálogo")
    ];

    if (empty($query)) {
        $response_data["texto_ia"] = "Por favor escribe algo para buscar.";
        echo json_encode($response_data);
        exit;
    }

    // --- 1. Consultar la BD (igual que antes, SIN filtro de estado) ---
    $producto_encontrado = null;
    $conn_activo = false;
    try {
        if ($conn->connect_errno) { throw new Exception("Error conexión BD."); }
        $conn_activo = true;
        $keyword_like = '%' . strtolower($query) . '%';
        $stmt = $conn->prepare("SELECT id_producto, nombre_producto, estado FROM productos WHERE LOWER(nombre_producto) LIKE ? ORDER BY CASE WHEN LOWER(nombre_producto) = LOWER(?) THEN 0 ELSE 1 END LIMIT 1");
        if (!$stmt) throw new Exception("Error preparando consulta: " . $conn->error);
        $query_lower = strtolower($query);
        $stmt->bind_param("ss", $keyword_like, $query_lower);
        if (!$stmt->execute()) throw new Exception("Error ejecutando consulta: " . $stmt->error);
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) { $producto_encontrado = $row; }
        $stmt->close();
    } catch (Exception $e) {
         error_log("Error BD en deepseek_search: " . $e->getMessage());
    } finally {
        if ($conn_activo && $conn) { $conn->close(); }
    }

    // --- 2. Preparar Petición a la IA ---
    $apiKey = "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"; // Verifica
    $api_url = "https://openrouter.ai/api/v1/chat/completions";
    $chosen_model = "mistralai/mistral-7b-instruct"; // Usando Mistral

    $prompt_contexto = "Eres un asistente experto en productos artesanales peruanos. Describe muy brevemente (1-2 frases MÁXIMO) qué es el producto consultado, su material o uso común. Actúa como una mini-wiki enciclopédica. NO menciones tiendas, disponibilidad, ni precios.";
    $user_message = "Describe brevemente este producto artesanal: " . $query;

    $payload = [ /* ... payload ... */
        "model" => $chosen_model,
        "messages" => [ ["role" => "system", "content" => $prompt_contexto], ["role" => "user", "content" => $user_message] ],
        "max_tokens" => 60, "temperature" => 0.5
    ];

    // --- Ejecutar cURL ---
    $ch = curl_init($api_url);
    // ... (opciones cURL igual que antes) ...
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json", "Authorization: Bearer $apiKey",
        "HTTP-Referer: http://localhost", "X-Title: Tinkuy Ecommerce"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $api_response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_errno = curl_errno($ch);
    $curl_error = curl_error($ch);
    curl_close($ch);

    // --- 3. Procesar Respuesta ---
    if ($curl_errno > 0) { // Error cURL
        error_log("Error cURL API ($chosen_model): [$curl_errno] $curl_error");
        $response_data["texto_ia"] = "Error de conexión al contactar al asistente (cURL: $curl_errno).";
        // Determinar enlace basado solo en BD
        if ($producto_encontrado && $producto_encontrado['estado'] === 'activo') {
             $response_data["mensaje_tienda"] = "Sin embargo, el producto sí está disponible.";
             $response_data["enlace_tienda"] = "producto.php?id=" . $producto_encontrado['id_producto'];
             $response_data["texto_enlace"] = "Ver Producto";
        } else { /* ... lógica para inactivo o no encontrado ... */
             $response_data["mensaje_tienda"] = "Puedes buscar en nuestro catálogo.";
             $response_data["enlace_tienda"] = "products.php";
             $response_data["texto_enlace"] = "Explorar Catálogo";
        }

    } elseif ($http_code >= 200 && $http_code < 300 && $api_response) { // Éxito HTTP
        $decoded = json_decode($api_response, true);
        if (json_last_error() === JSON_ERROR_NONE && isset($decoded['choices'][0]['message']['content'])) {
            $ai_text_raw = $decoded['choices'][0]['message']['content'];
            $response_data["texto_ia"] = trim(str_replace('"', '', $ai_text_raw));
            if (empty($response_data["texto_ia"])) { $response_data["texto_ia"] = "El asistente respondió vacío."; }
        } else { // JSON inválido
             error_log("Respuesta JSON inválida ($chosen_model): " . json_last_error_msg() . " | Resp: " . $api_response);
             $response_data["texto_ia"] = "El asistente dio una respuesta inesperada.";
        }
        // Determinar enlace basado en BD (igual que antes)
        if ($producto_encontrado && $producto_encontrado['estado'] === 'activo') {
            $response_data["mensaje_tienda"] = "¡Lo tenemos disponible!";
            $response_data["enlace_tienda"] = "producto.php?id=" . $producto_encontrado['id_producto'];
            $response_data["texto_enlace"] = "Ver '" . htmlspecialchars($producto_encontrado['nombre_producto']) . "'";
        } else if ($producto_encontrado) { // Existe pero inactivo
            $response_data["mensaje_tienda"] = "No está disponible actualmente. Explora similares.";
            $response_data["enlace_tienda"] = "products.php";
            $response_data["texto_enlace"] = "Explorar Catálogo";
        } else { // No existe
            $response_data["mensaje_tienda"] = "No lo encontramos. ¿Buscas en el catálogo?";
            $response_data["enlace_tienda"] = "products.php?buscar=" . urlencode($query);
            $response_data["texto_enlace"] = "Buscar '" . htmlspecialchars($query) . "'";
        }

    } else { // Error HTTP (4xx, 5xx)
        error_log("Error HTTP API ($chosen_model): $http_code | Resp: " . $api_response);
        $response_data["texto_ia"] = "No pudimos contactar al asistente (Error: $http_code).";
        // Determinar enlace basado solo en BD (igual que en error cURL)
         if ($producto_encontrado && $producto_encontrado['estado'] === 'activo') {
             $response_data["mensaje_tienda"] = "Sin embargo, el producto sí está disponible.";
             $response_data["enlace_tienda"] = "producto.php?id=" . $producto_encontrado['id_producto'];
             $response_data["texto_enlace"] = "Ver Producto";
        } else { /* ... lógica para inactivo o no encontrado ... */
             $response_data["mensaje_tienda"] = "Puedes buscar en nuestro catálogo.";
             $response_data["enlace_tienda"] = "products.php";
             $response_data["texto_enlace"] = "Explorar Catálogo";
        }
    }

    echo json_encode($response_data);

} else {
    echo json_encode(["texto_ia" => "Método no permitido.", "mensaje_tienda" => "", "enlace_tienda" => null, "texto_enlace" => ""]);
}
?>