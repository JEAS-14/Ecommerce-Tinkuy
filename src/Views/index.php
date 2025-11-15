<?php
// src/Views/index.php
// Esta Vista espera que la variable $productos_destacados ya exista.

// --- DEFINICIÓN DE RUTAS ---
$project_root = "/Ecommerce-Tinkuy";
$base_url = $project_root . "/public"; // Para rutas HTML (css, img, js)
$controller_url = $base_url . "/index.php"; // El "Cerebro"
$pagina_actual = 'index'; // Para el navbar
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Tienda online de productos artesanales...">
    <meta name="keywords" content="artesanías, alpaca, productos tradicionales...">
    <title>Inicio | Tinkuy</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    
    <link rel="stylesheet" href="<?= $base_url ?>/css/style.css"> 
</head>

<body class="d-flex flex-column min-vh-100">

    <?php 
    // RUTA NAVBAR CORREGIDA
    include BASE_PATH . '/src/Views/components/navbar.php'; 
    ?>

    <div class="flex-grow-1">
        <div id="mainCarousel" class="carousel slide mt-4" data-bs-ride="carousel">
            <div class="carousel-inner">
                
                <div class="carousel-item active">
                   <img src="<?= $project_root ?>/public/img/banner1.png"  class="d-block w-100" alt="Banner 1" loading="lazy"> 
                </div>
                <div class="carousel-item">
                    <img src="<?= $project_root ?>/public/img/banner3.png"  class="d-block w-100" alt="Banner 2" loading="lazy"> 
                </div>
                <div class="carousel-item">
                    <img src="<?= $project_root ?>/public/img/banner3.png"  class="d-block w-100" alt="Banner 3" loading="lazy"> 
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

        <!-- Sección de Búsqueda Inteligente con IA -->
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
                            <button class="btn btn-outline-primary" type="submit">
                                <i class="bi bi-search"></i> Buscar
                            </button>
                        </div>
                    </form>
                    <div id="iaSuggestion" class="mt-3 text-muted"></div>
                </div>
            </div>
        </section>

        <section class="container my-5">
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
                                
                                <img src="<?= $project_root ?>/public/img/productos/<?= htmlspecialchars($producto['imagen_principal']) ?>"
     class="card-img-top producto-img"
     alt="<?= htmlspecialchars($producto['nombre_producto']) ?>">

                                <div class="card-body text-center">
                                    <h5 class="card-title"><?= htmlspecialchars($producto['nombre_producto']) ?></h5>
                                    <p class="card-text"><?= htmlspecialchars(substr($producto['descripcion'], 0, 100)) ?>...</p>
                                    <p class="card-text fw-bold">
                                        Desde S/ <?= number_format($producto['precio_minimo'], 2) ?>
                                    </p>
                                    
                                    <a href="<?= $controller_url ?>?page=producto&id=<?= $producto['id_producto'] ?>" class="btn btn-dark w-100"> 
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

    <?php 
    // RUTA FOOTER CORREGIDA
    include BASE_PATH . '/src/Views/components/footer.php'; 
    ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" defer></script>

    <!-- Script del Asistente Inteligente IA -->
    <script>
    document.getElementById("iaSearchForm").addEventListener("submit", async function (e) {
      e.preventDefault();

      const query = document.getElementById("iaSearchInput").value.trim();
      const suggestionDiv = document.getElementById("iaSuggestion");

      if (!query) {
        suggestionDiv.innerHTML = "Por favor, escribe algo para buscar.";
        return;
      }

      // Mensaje inicial
      suggestionDiv.innerHTML = "🤖 Pensando en la mejor recomendación...";

      try {
                const res = await fetch("<?= $base_url ?>/deepseek_search.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ query })
                });

                if (!res.ok) {
                    const text = await res.text();
                    console.error("❌ Error HTTP al llamar al asistente:", res.status, text);
                    throw new Error('Error HTTP ' + res.status);
                }

                const data = await res.json();
                console.log("✅ Respuesta IA:", data);

        // Mostrar recomendación
        suggestionDiv.innerHTML = "<b>Recomendación IA:</b> " + (data.texto || "No se recibió explicación.");

                // Redirigir después de 10s si hay keyword (usar el controlador central)
                if (data.keyword) {
                    setTimeout(() => {
                        window.location.href = "<?= $controller_url ?>?page=products&buscar=" + encodeURIComponent(data.keyword);
                    }, 10000);
                }

      } catch (err) {
        console.error("❌ Error con el asistente:", err);
        suggestionDiv.innerHTML = "❌ Error con el asistente. Revisa la consola.";
      }
    });
  </script>

</body>
</html>