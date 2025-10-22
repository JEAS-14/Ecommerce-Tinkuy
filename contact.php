<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Contacto | Tinkuy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="contact-page">
    <?php include 'assets/component/navbar.php'; ?>

    <section class="contact-section container">
        <div class="text-center mb-5">
            <h2><i class="bi bi-envelope-paper-fill text-primary me-2"></i>Contáctanos</h2>
            <p class="text-muted">¿Tienes alguna duda, sugerencia o necesitas ayuda? Completa el siguiente
                formulario:
            </p>
        </div>

        <form class="row g-4" action="" method="POST" >
            <div class="col-md-6">
                <label for="nombre" class="form-label">Nombre *</label>
                <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Tu nombre completo"
                required pattern="^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+$" title="Solo se permiten letras y espacios">
            </div>

            <div class="col-md-6">
                <label for="email" class="form-label">Correo electrónico *</label>
                <input type="email" class="form-control" id="email" name="email" placeholder="ejemplo@correo.com"
                required pattern="^[^@\s]+@[^@\s]+\.[^@\s]+$" title="El correo debe tener un @ y un dominio, como ejemplo@correo.com">
            </div>

            <div class="col-md-6">
                <label for="tel" class="form-label">Teléfono *</label>
                <input type="tel" class="form-control" id="tel" name="telefono" placeholder="+51 987 654 321" 
                required pattern="^\+?\d{7,15}$" title="Solo números (puede incluir + al inicio)">
            </div>

            <div class="col-md-6">
                <label for="asunto" class="form-label">Asunto</label>
                <input type="text" class="form-control" id="asunto" name="asunto" placeholder="Motivo del mensaje">
            </div>

            <div class="col-md-12">
                <label for="mensaje" class="form-label">Mensaje *</label>
                <textarea class="form-control" id="mensaje" name="mensaje" rows="5"
                    placeholder="Escribe tu mensaje aquí..." required></textarea>
            </div>

            <div class="col-md-12">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="terminos" name="terminos" required>
                    <label class="form-check-label" for="terminos">
                        *Acepto los términos y condiciones</a>
                    </label>
                </div>
            </div>

            <div class="col-md-12 text-end">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-send-fill"></i> Enviar mensaje
                </button>
            </div>
        </form>
    </section>

    <?php include 'assets/component/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>