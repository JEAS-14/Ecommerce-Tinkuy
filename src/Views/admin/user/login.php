<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login Admin | Tinkuy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h3 class="card-title text-center mb-4">Panel de Administración</h3>

                        <?php 
                        // El error ahora se lee desde la URL (lo envía el controlador)
                        if (isset($_GET['error'])): 
                            $error_msg = ($_GET['error'] == 'pass') ? "Contraseña incorrecta." : "Usuario no encontrado.";
                        ?>
                            <div class="alert alert-danger"><?= $error_msg ?></div>
                        <?php endif; ?>

                        <form method="POST" action="?page=admin_procesar_login">
                            <div class="mb-3">
                                <label for="usuario" class="form-label">Usuario</label>
                                <input type="text" name="usuario" id="usuario" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label for="clave" class="form-label">Contraseña</label>
                                <input type="password" name="clave" id="clave" class="form-control" required>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Iniciar Sesión</button>
                        </form>
                    </div>
                </div>
                <p class="text-center mt-3"><a href="?page=index">← Volver a la tienda</a></p>
            </div>
        </div>
    </div>
</body>
</html>