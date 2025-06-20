<?php
// /includes/funciones_email.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

// La función ahora acepta dos parámetros opcionales al final para el adjunto
function enviarEmail($config, $destinatario_email, $destinatario_nombre, $asunto, $cuerpo_html, $adjunto_ruta = null, $adjunto_nombre = null) {
    $mail = new PHPMailer(true);

    try {
        // Configuración del servidor
        $mail->isSMTP();
        $mail->Host       = $config['email_smtp_host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['email_smtp_user'];
        $mail->Password   = $config['email_smtp_pass'];
        $mail->SMTPSecure = $config['email_smtp_secure'];
        $mail->Port       = (int)$config['email_smtp_port'];
        $mail->CharSet    = 'UTF-8';

        // Remitente y destinatarios
        $mail->setFrom($config['email_from_address'], $config['email_from_name']);
        $mail->addAddress($destinatario_email, $destinatario_nombre);

        // --- LÓGICA AÑADIDA PARA ARCHIVOS ADJUNTOS ---
        if ($adjunto_ruta && file_exists($adjunto_ruta)) {
            $mail->addAttachment($adjunto_ruta, $adjunto_nombre);
        }

        // Contenido
        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body    = $cuerpo_html;
        $mail->AltBody = strip_tags($cuerpo_html);

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>