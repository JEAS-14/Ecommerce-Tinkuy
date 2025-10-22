<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $data = json_decode(file_get_contents("php://input"), true);
  $query = $data['query'] ?? '';

  $ch = curl_init("https://openrouter.ai/api/v1/chat/completions");
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer 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"
  ]);

  $payload = [
    "model" => "deepseek/deepseek-chat-v3.1:free",
    "messages" => [
      [
        "role" => "system",
        "content" => "Responde de forma breve y clara recomendando un producto del catálogo. Además, dame una sola palabra clave de búsqueda (ejemplo: 'chompa'). Responde en JSON: {\"texto\":\"<recomendacion>\", \"keyword\":\"<palabra>\"}"
      ],
      ["role" => "user", "content" => $query]
    ]
  ];

  curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
  $response = curl_exec($ch);
  curl_close($ch);

  $decoded = json_decode($response, true);
  $content = $decoded['choices'][0]['message']['content'] ?? '';

  // Intentar decodificar el JSON de la IA
  $ia = json_decode($content, true);

  if ($ia && isset($ia['texto'])) {
    $texto = $ia['texto'];
    $keyword = $ia['keyword'] ?? "";
  } else {
    // fallback: si la IA no responde en JSON
    $texto = $content ?: "No se recibió explicación de la IA.";
    $keyword = "";
  }

  header('Content-Type: application/json');
  echo json_encode([
    "texto" => $texto,
    "keyword" => $keyword
  ]);
}
?>
