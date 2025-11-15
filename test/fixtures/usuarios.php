<?php
// Fixture de usuarios para pruebas unitarias.
// Estructura pensada para casos de autenticación, permisos y sesiones.
// Cada entrada incluye un id numérico para facilitar asignación a $_SESSION.
return [
	'usuario_valido' => [
		'id' => 1,
		'usuario' => 'testuser',
		'clave_plana' => 'password123', // Puede usarse para tests de hashing
		'clave_hash' => password_hash('password123', PASSWORD_DEFAULT),
		'email' => 'test@tinkuy.com',
		'rol' => 'cliente',
		'activo' => true
	],
	'usuario_admin' => [
		'id' => 2,
		'usuario' => 'admintest',
		'clave_plana' => 'admin12345',
		'clave_hash' => password_hash('admin12345', PASSWORD_DEFAULT),
		'email' => 'admin@tinkuy.com',
		'rol' => 'admin',
		'activo' => true
	],
	'usuario_vendedor' => [
		'id' => 3,
		'usuario' => 'vendortest',
		'clave_plana' => 'vendor123',
		'clave_hash' => password_hash('vendor123', PASSWORD_DEFAULT),
		'email' => 'vendor@tinkuy.com',
		'rol' => 'vendedor',
		'activo' => true
	],
	'usuario_inactivo' => [
		'id' => 4,
		'usuario' => 'inactiveuser',
		'clave_plana' => 'inactive123',
		'clave_hash' => password_hash('inactive123', PASSWORD_DEFAULT),
		'email' => 'inactive@tinkuy.com',
		'rol' => 'cliente',
		'activo' => false
	],
];
?>
