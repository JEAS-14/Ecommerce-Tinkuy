🧩 Cómo instalar dependencias (vendor/) en tu proyecto Ecommerce-Tinkuy
🧠 Requisitos previos

Antes de empezar, asegúrate de tener instalado:

PHP (versión 8.1 o superior)
Puedes usar XAMPP, Laragon o WAMP.
👉 Descargar XAMPP

Composer (gestor de dependencias PHP)
👉 Descargar Composer

Para comprobar si ya lo tienes instalado, abre la terminal y ejecuta:

composer -V


Deberías ver algo como:

Composer version 2.x.x

🧩 Paso 1 — Clonar el proyecto

Abre tu terminal (CMD o PowerShell) y ejecuta:

git clone https://github.com/JEAS-14/Ecommerce-Tinkuy.git


Luego entra al directorio:

cd Ecommerce-Tinkuy

🧩 Paso 2 — Instalar las dependencias

Ejecuta este comando dentro de la carpeta del proyecto:

composer install


Esto descargará automáticamente todas las librerías en la carpeta /vendor/, incluyendo:

PHPMailer (para envío de correos)

El autoloader de Composer

📦 Verás aparecer la carpeta vendor/ al finalizar el proceso.

🧩 Paso 3 — Configurar tu entorno local

Crea una base de datos en phpMyAdmin llamada:

tinkuy_db


Importa el archivo SQL incluido en el proyecto (tinkuy_db.sql).

Ajusta los datos de conexión en:

assets/admin/db.php
Paso 4 — Probar el proyecto

Ejecuta tu servidor local (XAMPP o similar) y abre en el navegador:

http://localhost/Ecommerce-Tinkuy/


Ya deberías ver la tienda funcionando, con:

Login de usuarios y administradores

Recuperación de contraseña (vía Mailtrap)

Simulación de pasarela de pagos

Gestión de pedidos y perfiles

🧩 Paso 5 — (Opcional) Configurar Mailtrap

Si deseas probar el envío de correos (simulado), crea una cuenta en:

👉 https://mailtrap.io

Luego copia tus credenciales SMTP y colócalas en:

assets/admin/mailer_config.php
