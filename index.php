<?php
session_start();
// 1. INCLUIMOS LA CONEXIÓN A LA BD
include 'assets/admin/db.php'; //

// 2. LÓGICA DE CALIDAD PARA PRODUCTOS DESTACADOS
// (Esta consulta es la que arregla tu página)
$query = "
    SELECT 
        p.id_producto,
        p.nombre_producto,
        p.descripcion, 
        p.imagen_principal,
        -- Obtenemos el precio MÁS BAJO de las variantes de este producto
        (SELECT MIN(vp.precio) FROM variantes_producto vp WHERE vp.id_producto = p.id_producto) AS precio_minimo
    FROM 
        productos AS p
    WHERE
        -- Solo mostramos productos que tengan stock total
        (SELECT SUM(vp.stock) FROM variantes_producto vp WHERE vp.id_producto = p.id_producto) > 0
    ORDER BY 
        p.fecha_creacion DESC
    LIMIT 3 
"; // Limitamos a 3 para la sección "Más Vendidos"

$resultado = $conn->query($query);
$productos_destacados = $resultado->fetch_all(MYSQLI_ASSOC);
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
                             class="card-img-top img-fluid" 
                             alt="<?= htmlspecialchars($producto['nombre_producto']) ?>"
                             style="height: 250px; object-fit: cover;">

                        <div class="card-body text-center">
                            <h5 class="card-title"><?= htmlspecialchars($producto['nombre_producto']) ?></h5>
                            <p class="card-text"><?= htmlspecialchars(substr($producto['descripcion'], 0, 100)) ?>...</p>

                            <p class="card-text fw-bold">
                                Desde S/ <?= number_format($producto['precio_minimo'], 2) ?>
                            </p>

                            <a href="producto.php?id=<?= $producto['id_producto'] ?>" class="btn btn-dark w-100"> 
                                <i class="bi bi-box-arrow-in-right"></i> Ver más
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
                suggestionDiv.innerHTML = "Por favor, escribe algo para buscar.";
                return;
            }

            suggestionDiv.innerHTML = "🤖 Pensando en la mejor recomendación...";

            try {
                const res = await fetch("deepseek_search.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ query })
                });

                const data = await res.json();
                console.log("✅ Respuesta IA:", data);

                suggestionDiv.innerHTML = "<b>Recomendación IA:</b> " + (data.texto || "No se recibió explicación válida de la IA.");

                // 🔍 Si la IA encontró producto exacto, redirige directo
                if (data.id_producto) {
                    setTimeout(() => {
                        window.location.href = "producto.php?id=" + data.id_producto;
                    }, 4000);
                }
                else if (data.keyword) {
                    // Si no encontró exacto, redirige a la búsqueda general
                    setTimeout(() => {
                        window.location.href = "products.php?buscar=" + encodeURIComponent(data.keyword);
                    }, 6000);
                }

            } catch (err) {
                console.error("❌ Error con el asistente:", err);
                suggestionDiv.innerHTML = "❌ Error con el asistente. Revisa la consola.";
            }
            if (data.texto) {
                suggestionDiv.innerHTML = "<b>Recomendación IA:</b> " + data.texto;
            }

            // Redirección más inteligente
            if (data.redirect) {
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 5000);
            }

        });
    </script>

</body>

</html>