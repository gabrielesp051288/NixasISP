<?php
$page_title = "Instalación - Paso 1: Base de Datos";
$error = $_GET['error'] ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= $page_title ?></title>
    <link rel="stylesheet" href="../assets/css/estilos.css">
</head>
<body>
    <div class="login-container">
        <h1>Instalación del Sistema</h1>
        <p>Bienvenido. Por favor, introduce los datos de tu base de datos MySQL.</p>
        <?php if ($error): ?>
            <p style="color:red; font-weight:bold;">Error: <?= htmlspecialchars(urldecode($error)) ?></p>
        <?php endif; ?>
        <form action="procesar_instalacion.php" method="post">
            <label for="db_host">Servidor de la Base de Datos</label>
            <input type="text" name="db_host" id="db_host" value="localhost" required>
            <label for="db_name">Nombre de la Base de Datos</label>
            <input type="text" name="db_name" id="db_name" required>
            <label for="db_user">Usuario de la Base de Datos</label>
            <input type="text" name="db_user" id="db_user" required>
            <label for="db_pass">Contraseña</label>
            <input type="password" name="db_pass" id="db_pass">
            <button type="submit">Probar Conexión y Continuar</button>
        </form>
    </div>
</body>
</html>