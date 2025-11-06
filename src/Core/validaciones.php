<?php
// assets/admin/validaciones.php

/**
 * Valida los datos de entrada del formulario de login.
 * Devuelve un string con el mensaje de error si algo falla.
 * Devuelve null si la validación es exitosa.
 */
function validarDatosLogin($usuario, $clave) {
    
    // IDs 3, 12, 13 (Campos vacíos)
    if (empty($usuario) || empty($clave)) {
        return "Por favor, ingresa tu usuario y contraseña.";
    }
    
    // IDs 4, 86 (Usuario min 4)
    if (strlen($usuario) < 4) {
        return "Error (ID 4/86): El usuario debe tener al menos 4 caracteres.";
    }
    
    // IDs 5, 87 (Usuario max 20)
    if (strlen($usuario) > 20) {
        return "Error (ID 5/87): El usuario no debe exceder los 20 caracteres.";
    }
    
    // ID 2 (Usuario caracteres)
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $usuario)) {
        return "Error (ID 2): Formato de usuario no válido.";
    }
    
    // IDs 8, 92 (Clave min 7)
    if (strlen($clave) < 7) {
        return "Error (ID 8/92): La contraseña debe tener al menos 7 caracteres.";
    }
    
    // IDs 9, 93 (Clave max 30)
    if (strlen($clave) > 30) {
        return "Error (ID 9/93): La contraseña no debe exceder los 30 caracteres.";
    }

    // Si todo está bien, no hay error
    return null; 
}