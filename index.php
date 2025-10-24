<?php
session_start();
// 1. INCLUIMOS LA CONEXIÓN A LA BD
include 'assets/admin/db.php';

// 2. LÓGICA CORREGIDA PARA PRODUCTOS DESTACADOS (SOLO ACTIVOS)
$query = "
    SELECT
        p.id_producto,
        p.nombre_producto,
        p.descripcion,
        p.imagen_principal,
        -- Obtenemos el precio MÁS BAJO de las variantes ACTIVAS con stock
        (SELECT MIN(vp.precio)
         FROM variantes_producto vp
         WHERE vp.id_producto = p.id_producto
           AND vp.estado = 'activo' -- <<< Solo variantes activas
           AND vp.stock > 0        -- <<< Con stock
        ) AS precio_minimo
    FROM
        productos AS p
    WHERE
        p.estado = 'activo' -- <<< FILTRO 1: Solo productos activos
        -- <<< FILTRO 2: Asegurar que tenga al menos UNA variante activa con stock >>>
        AND EXISTS (
            SELECT 1
            FROM variantes_producto vp
            WHERE vp.id_producto = p.id_producto
              AND vp.estado = 'activo'
              AND vp.stock > 0
        )
    ORDER BY
        -- p.fecha_creacion DESC -- O podrías ordenar por más vendidos si tuvieras esa lógica
        RAND() -- Orden aleatorio como placeholder si no tienes fecha_creacion
    LIMIT 3 -- Mantenemos el límite a 3
";

$resultado = $conn->query($query);
$productos_destacados = []; // Inicializar como array vacío
if ($resultado) { // Verificar si la consulta fue exitosa
    $productos_destacados = $resultado->fetch_all(MYSQLI_ASSOC);
} else {
    // Opcional: Registrar el error si la consulta falla
    error_log("Error en consulta de productos destacados: " . $conn->error);
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Tienda online de productos artesanales como gorros, chompas y ponchos de alpaca.">
    <meta name="keywords" content="artesanías, alpaca, productos tradicionales, ropa, accesorios, Perú">
    <title>Inicio | Tinkuy</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="d-flex flex-column min-vh-100">

    <?php
    // Variable para indicar a navbar.php cuál es la página actual
    $pagina_actual = 'index';
    include 'assets/component/navbar.php';
    ?>

    <div class="flex-grow-1">
        <div id="mainCarousel" class="carousel slide mt-4" data-bs-ride="carousel">
            <div class="carousel-inner">
                <div class="carousel-item active">
                    <img src="assets/img/banner1.png" class="d-block w-100" alt="Banner 1" loading="lazy">
                </div>
                <div class="carousel-item">
                    <img src="assets/img/banner2.png" class="d-block w-100" alt="Banner 2" loading="lazy">
                </div>
                <div class="carousel-item">
                    <img src="assets/img/banner3.png" class="d-block w-100" alt="Banner 3" loading="lazy">
                </div>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#mainCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Anterior</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#mainCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Siguiente</span>
            </button>
        </div>

        <section class="container my-5">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h4 class="mb-3">¿Buscas algo en especial?</h4>
                    <p>Escribe lo que quieras encontrar y nuestro <strong>Asistente Inteligente (IA)</strong> te
                        recomendará productos.</p>
                </div>
                <div class="col-md-6">
                    <form id="iaSearchForm">
                        <div class="input-group">
                            <input type="text" id="iaSearchInput" class="form-control" placeholder="Buscar productos..."
                                aria-label="Buscar productos">
                            <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i>
                                Buscar</button>
                        </div>
                    </form>
                    <div id="iaSuggestion" class="mt-3 text-muted"></div>
                </div>
            </div>
        </section>

        <section class="container my-5">
            <h2 class="text-center mb-4">Más Vendidos</h2>
            <div class="row">

                <?php if (empty($productos_destacados)): ?>
                    <div class="col-12">
                        <p class="text-center text-muted">No hay productos destacados en este momento.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($productos_destacados as $producto): ?>
                        <div class="col-md-4 mb-4">
                            <div class="card h-100 shadow-sm">
                                <img src="assets/img/productos/<?= htmlspecialchars($producto['imagen_principal']) ?>"
                                    class="card-img-top producto-img"
                                    alt="<?= htmlspecialchars($producto['nombre_producto']) ?>">

                                <div class="card-body text-center">
                                    <h5 class="card-title"><?= htmlspecialchars($producto['nombre_producto']) ?></h5>
                                    <p class="card-text"><?= htmlspecialchars(substr($producto['descripcion'], 0, 100)) ?>...
                                    </p>

                                    <p class="card-text fw-bold">
                                        Desde S/ <?= number_format($producto['precio_minimo'], 2) ?>
                                    </p>

                                    <a href="producto.php?id=<?= $producto['id_producto'] ?>" class="btn btn-dark w-100"> <i
                                            class="bi bi-box-arrow-in-right"></i> Ver más
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

            </div>
        </section>
    </div>

    <?php include 'assets/component/footer.php'; // Este se queda al final del div flex-grow-1 y antes de cerrar el body ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" defer></script>

<script>
        document.getElementById("iaSearchForm").addEventListener("submit", async function (e) {
            e.preventDefault();

            const query = document.getElementById("iaSearchInput").value.trim();
            const suggestionDiv = document.getElementById("iaSuggestion");

            if (!query) {
                suggestionDiv.innerHTML = "<small class='text-danger'>Por favor, escribe algo para buscar.</small>";
                return;
            }

            suggestionDiv.innerHTML = `<div class="d-flex align-items-center"><div class="spinner-border spinner-border-sm me-2 text-primary" role="status"></div><span class="text-muted">Buscando información y disponibilidad...</span></div>`; // Indicador de carga

            try {
                const res = await fetch("deepseek_search.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ query })
                });

                if (!res.ok) {
                    // Intentar leer el cuerpo del error si es posible
                    let errorBody = await res.text();
                    console.error("Respuesta cruda del error:", errorBody);
                    throw new Error(`Error HTTP: ${res.status}`);
                }

                const data = await res.json();
                console.log("✅ Respuesta IA Procesada:", data);

                // 1. Construir HTML con la descripción de la IA
                let outputHtml = "<b>Asistente IA:</b> " + (data.texto_ia || "No se pudo obtener descripción.");

                // 2. Añadir mensaje sobre la tienda si existe
                if (data.mensaje_tienda) {
                    outputHtml += "<br><small><i>" + data.mensaje_tienda + "</i></small>";
                }

                // 3. Preparar redirección si aplica (REINTRODUCIDO setTimeout)
                if ((data.accion === 'ver_producto' || data.accion === 'ver_catalogo') && data.url_redirect) {
                    outputHtml += "<br><small class='text-primary'><i>Redirigiendo en 5 segundos...</i></small>"; // Mensaje de redirección
                    // Iniciar el temporizador para redirigir
                    setTimeout(() => {
                        window.location.href = data.url_redirect; // Usar la URL proporcionada
                    }, 5000); // 5 segundos
                } else if (data.accion !== 'ninguna') {
                     console.warn("Acción desconocida o sin URL:", data.accion, data.url_redirect);
                     // Si no hay redirección, quizás añadir un enlace manual por si acaso
                     if (!data.url_redirect && data.accion === 'ver_catalogo') {
                         outputHtml += ` <a href="products.php" class="link-secondary small">Explorar catálogo <i class="bi bi-arrow-right-short"></i></a>`;
                     }
                }

                // Mostrar todo en el div
                suggestionDiv.innerHTML = outputHtml;

            } catch (err) {
                console.error("❌ Error con el asistente:", err);
                suggestionDiv.innerHTML = "<small class='text-danger'>❌ Hubo un problema al contactar con el asistente. Intenta buscar directamente en el <a href='products.php' class='link-danger'>catálogo</a>.</small>";
            }
        });
    </script>

</body>

</html>