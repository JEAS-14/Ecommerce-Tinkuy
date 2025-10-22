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

<body>

  <?php include 'assets/component/navbar.php'; ?>

  <!-- Carrusel -->
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

  <!-- Buscador con IA -->
  <section class="container my-5">
    <div class="row align-items-center">
      <div class="col-md-6">
        <h4 class="mb-3">¿Buscas algo en especial?</h4>
        <p>Escribe lo que quieras encontrar y nuestro <strong>Asistente Inteligente (IA)</strong> te recomendará productos.</p>
      </div>
      <div class="col-md-6">
        <form id="iaSearchForm">
          <div class="input-group">
            <input type="text" id="iaSearchInput" class="form-control" placeholder="Buscar productos..."
              aria-label="Buscar productos">
            <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i> Buscar</button>
          </div>
        </form>
        <!-- Aquí aparecerá la recomendación de la IA -->
        <div id="iaSuggestion" class="mt-3 text-muted"></div>
      </div>
    </div>
  </section>

  <!-- Productos Destacados -->
  <section class="container my-5">
    <h2 class="text-center mb-4">Más Vendidos</h2>
    <div class="row">
      <!-- Producto 1 -->
      <div class="col-md-4 mb-4">
        <div class="card h-100 shadow-sm">
          <img src="assets/img/chompa-artesanal1.png" class="card-img-top producto-img" alt="Chompa de Alpaca">
          <div class="card-body text-center">
            <h5 class="card-title">Chompa Artesanal de Alpaca</h5>
            <p class="card-text">Tejida a mano con lana de alpaca 100%, diseño andino tradicional.</p>
            <p class="card-text fw-bold">S/ 180.00</p>
            <a href="producto.php?id=2" class="btn btn-dark w-100"><i class="bi bi-box-arrow-in-right"></i> Ver más</a>
          </div>
        </div>
      </div>
      <!-- Producto 2 -->
      <div class="col-md-4 mb-4">
        <div class="card h-100 shadow-sm">
          <img src="assets/img/gorro-artesanal-unixes.png" class="card-img-top producto-img" alt="Gorro Unisex">
          <div class="card-body text-center">
            <h5 class="card-title">Gorro Artesanal Unisex</h5>
            <p class="card-text">Diseño tradicional con orejeras, elaborado por artesanos cusqueños.</p>
            <p class="card-text fw-bold">S/ 45.00</p>
            <a href="producto.php?id=3" class="btn btn-dark w-100"><i class="bi bi-box-arrow-in-right"></i> Ver más</a>
          </div>
        </div>
      </div>
      <!-- Producto 3 -->
      <div class="col-md-4 mb-4">
        <div class="card h-100 shadow-sm">
          <img src="assets/img/poncho-andino-multicolor.jpg" class="card-img-top producto-img" alt="Poncho Multicolor">
          <div class="card-body text-center">
            <h5 class="card-title">Poncho Andino Multicolor</h5>
            <p class="card-text">Colorido y abrigador, tejido en telar por comunidades altoandinas.</p>
            <p class="card-text fw-bold">S/ 230.00</p>
            <a href="producto.php?id=4" class="btn btn-dark w-100"><i class="bi bi-box-arrow-in-right"></i> Ver más</a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <?php include 'assets/component/footer.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" defer></script>

  <script>
    document.getElementById("iaSearchForm").addEventListener("submit", async function (e) {
      e.preventDefault();

      const query = document.getElementById("iaSearchInput").value;
      const suggestionDiv = document.getElementById("iaSuggestion");

      // Mensaje inicial
      suggestionDiv.innerHTML = "🤖 Pensando en la mejor recomendación...";

      try {
        const res = await fetch("deepseek_search.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ query })
        });

        const data = await res.json();
        console.log("✅ Respuesta IA:", data);

        // Mostrar recomendación
        suggestionDiv.innerHTML = "<b>Recomendación IA:</b> " + (data.texto || "No se recibió explicación.");

        // Redirigir después de 3s si hay keyword
        if (data.keyword) {
          setTimeout(() => {
            window.location.href = "products.php?buscar=" + encodeURIComponent(data.keyword);
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