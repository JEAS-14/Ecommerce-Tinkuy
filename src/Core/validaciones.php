<?php
// Validaciones backend centralizadas.

// Login
function validarDatosLogin($usuario, $clave) {
    if (empty($usuario) || empty($clave)) {
        return "Por favor, ingresa tu usuario y contraseña.";
    }
    if (strlen($usuario) < 4) {
        return "Error (ID 4/86): El usuario debe tener al menos 4 caracteres.";
    }
    if (strlen($usuario) > 20) {
        return "Error (ID 5/87): El usuario no debe exceder los 20 caracteres.";
    }
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $usuario)) {
        return "Error (ID 2): Formato de usuario no válido.";
    }
    if (strlen($clave) < 7) {
        return "Error (ID 8/92): La contraseña debe tener al menos 7 caracteres.";
    }
    if (strlen($clave) > 30) {
        return "Error (ID 9/93): La contraseña no debe exceder los 30 caracteres.";
    }
    return null;
}

// Perfil vendedor
function validarNombreApellido($valor) {
    if (empty($valor)) {
        return "Este campo es obligatorio.";
    }
    if (strlen($valor) < 2) {
        return "Debe tener mínimo 2 caracteres.";
    }
    if (strlen($valor) > 50) {
        return "Debe tener máximo 50 caracteres.";
    }
    if (!preg_match('/^[a-zA-ZÁ-úÑñ\s]+$/', $valor)) {
        return "Solo se permiten letras y espacios.";
    }
    return null;
}

function validarTelefono($telefono) {
    if (empty($telefono)) {
        return "El teléfono es obligatorio.";
    }
    if (!preg_match('/^[0-9]{9}$/', $telefono)) {
        return "El teléfono debe tener exactamente 9 dígitos.";
    }
    return null;
}

function validarNombreTienda($nombre) {
    if (empty($nombre)) {
        return "El nombre de la tienda es obligatorio.";
    }
    if (strlen($nombre) < 3) {
        return "El nombre de la tienda debe tener mínimo 3 caracteres.";
    }
    if (strlen($nombre) > 50) {
        return "El nombre de la tienda debe tener máximo 50 caracteres.";
    }
    if (!preg_match("/^[a-zA-Z0-9Á-úÑñ\s&'-]{3,50}$/", $nombre)) {
        return "El nombre de la tienda contiene caracteres no permitidos.";
    }
    return null;
}

// Producto
function validarNombreProducto($nombre) {
    if (empty($nombre)) {
        return "El nombre del producto es obligatorio.";
    }
    if (strlen($nombre) < 10) {
        return "El nombre debe tener mínimo 10 caracteres.";
    }
    if (strlen($nombre) > 60) {
        return "El nombre debe tener máximo 60 caracteres.";
    }
    if (!preg_match('/^[a-zA-Z0-9Á-úÑñ\s]+$/', $nombre)) {
        return "El nombre solo puede contener letras, números y espacios.";
    }
    return null;
}

function validarDescripcionProducto($desc) {
    if (empty($desc)) {
        return "La descripción es obligatoria.";
    }
    if (strlen($desc) < 10) {
        return "La descripción debe tener mínimo 10 caracteres.";
    }
    return null;
}

function validarPrecioProducto($precio) {
    if (!is_numeric($precio)) {
        return "El precio debe ser un número válido.";
    }
    if ($precio <= 0) {
        return "El precio debe ser mayor a 0.";
    }
    return null;
}

function validarStockProducto($stock) {
    if (!is_numeric($stock) || intval($stock) != $stock) {
        return "El stock debe ser un número entero.";
    }
    if ($stock < 1) {
        return "El stock debe ser mayor o igual a 1.";
    }
    return null;
}