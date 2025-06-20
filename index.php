<?php
// /index.php (Versión Final Corregida)

// 1. Primero, siempre verificamos si el sistema necesita ser instalado.
require_once __DIR__ . '/install_check.php';

// 2. Segundo, cargamos la configuración principal (esto define BASE_PATH).
// Usamos file_exists para evitar errores si el archivo aún no se ha creado durante la instalación.
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
} else {
    // Si config.php no existe, el install_check ya nos debería haber redirigido.
    // Si por alguna extraña razón llegamos aquí, detenemos la ejecución.
    die("Error: El archivo de configuración 'config.php' no se encuentra. Por favor, inicia el proceso de instalación accediendo a la carpeta /install.");
}

// 3. Tercero, iniciamos la sesión.
session_start();

// 4. Finalmente, comprobamos si el usuario ya está logueado y lo redirigimos.
// Ahora, la constante BASE_PATH ya existe y la redirección funcionará.
if (isset($_SESSION['usuario_id'])) {
    header("Location: " . BASE_PATH . "dashboard.php");
    exit();
}

$page_title = 'Login - Sistema de Gestión';
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
        <h1>Iniciar Sesión</h1>
        <?php if ($error): ?>
            <p style="color:red; text-align:center;">Usuario o contraseña incorrectos.</p>
        <?php endif; ?>
        <form action="procesar_login.php" method="post">
            <label for="username">Nombre de Usuario:</label>
            <input type="text" id="username" name="username" required>
            <label for="password">Contraseña:</label>
            <input type="password" id="password" name="password" required>
            <button type="submit">Entrar</button>
        </form>
    </div>
</body>
</html>