<?php
// /activacion.php (Página completa)
session_start();
define('BASE_PATH', '/gestorisp/');
$page_title = 'Activación del Sistema';

// Obtenemos el mensaje de error de la URL si existe
$error = $_GET['error'] ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= $page_title ?></title>
    <link rel="stylesheet" href="<?= BASE_PATH ?>assets/css/estilos.css">
</head>
<body>
    <div class="login-container">
        <h1>Activación del Sistema</h1>
        <p>Por favor, introduce el DNI del titular y la clave de licencia para activar el sistema.</p>

        <?php if ($error == 'invalida'): ?>
            <p style="color:red; font-weight:bold;">La clave de licencia o el DNI son incorrectos.</p>
        <?php elseif ($error == 'db'): ?>
             <p style="color:red; font-weight:bold;">Error al contactar la base de datos.</p>
        <?php endif; ?>

        <form action="procesar_activacion.php" method="post">
            <label for="dni">DNI del Titular de la Licencia:</label>
            <input type="text" name="dni" id="dni" required>

            <label for="license_key">Clave de Licencia:</label>
            <input type="text" name="license_key" id="license_key" placeholder="XXXX-XXXX-XXXX-XXXX" required>

            <button type="submit">Activar Sistema</button>
        </form>
    </div>
</body>
</html>