<?php
// /herramientas/ejecutar_cron.php

session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'administrador') {
    header("HTTP/1.1 403 Forbidden");
    die("Acceso denegado.");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ejecutando Tarea de Facturación...</title>
    <link rel="stylesheet" href="../assets/css/estilos.css">
    <style>
        body { background-color: #000; }
        .log-output { 
            height: 100vh; 
            margin: 0; 
            border-radius: 0; 
            border: none;
            box-sizing: border-box;
        }
    </style>
</head>
<body>
    <pre class="log-output">
<?php
// --- ¡RUTA CORREGIDA! ---
// Ahora apunta a la carpeta /invoices/ donde tienes el archivo.
$ruta_objetivo = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'invoices' . DIRECTORY_SEPARATOR . 'generar_automatico.php';

include $ruta_objetivo;
?>
    </pre>
</body>
</html>