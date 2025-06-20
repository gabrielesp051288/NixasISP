<?php
// /includes/check_license.php (Versión Final Robusta)

if (defined('LICENCIA_VERIFICADA')) {
    return;
}

// Incluimos el archivo con la función de conexión una sola vez
require_once __DIR__ . '/../conexion.php';

// Obtenemos la conexión llamando a la función y la hacemos globalmente accesible
global $conexion;
$conexion = getConexion();

// Si la conexión falla, detenemos todo.
if (!$conexion) {
    die("Error crítico: No se pudo conectar a la base de datos para verificar la licencia.");
}

$pagina_actual = basename($_SERVER['PHP_SELF']);
if ($pagina_actual !== 'activacion.php' && $pagina_actual !== 'procesar_activacion.php') {
    
    $resultado = $conexion->query("SELECT valor FROM configuracion WHERE clave = 'license_status' LIMIT 1");
    if ($resultado) {
        $estado_licencia = $resultado->fetch_assoc()['valor'] ?? 'unlicensed';
        if ($estado_licencia !== 'licensed') {
            $base_path = defined('BASE_PATH') ? BASE_PATH : '/';
            header('Location: ' . $base_path . 'activacion.php');
            exit();
        }
    }
}

define('LICENCIA_VERIFICADA', true);
?>