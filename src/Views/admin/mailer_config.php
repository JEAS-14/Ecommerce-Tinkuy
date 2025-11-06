<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../../vendor/autoload.php';

// ⚙️ CONFIGURACIÓN DE MAILTRAP
define('MAIL_HOST', 'sandbox.smtp.mailtrap.io');
define('MAIL_PORT', 2525);
define('MAIL_SMTP_AUTH', true);
define('MAIL_SMTP_SECURE', PHPMailer::ENCRYPTION_STARTTLS); // 👈 usar constante oficial PHPMailer
define('MAIL_USERNAME', '347476dcabe2f0'); // tu username real
define('MAIL_PASSWORD', 'cbc95dd346b27c'); // tu password real
define('MAIL_FROM', 'no-reply@tinkuy.com');
define('MAIL_FROM_NAME', 'Tinkuy');
define('BASE_URL', 'http://localhost:8086/Ecommerce-Tinkuy');

function send_mail($to, $subject, $body_html, $body_text = '') {
    $mail = new PHPMailer(true);

    try {
        // Configuración del servidor SMTP
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = MAIL_SMTP_AUTH;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = MAIL_SMTP_SECURE;
        $mail->Port       = MAIL_PORT;

        // Configuración del remitente y destinatario
        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($to);

        // Contenido del correo
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body_html;
        $mail->AltBody = $body_text ?: strip_tags($body_html);

        // Enviar correo
        $mail->send();
        return true;
    } catch (Exception $e) {
        echo "<pre>❌ Error al enviar correo: {$mail->ErrorInfo}</pre>";
        return false;
    }
}
