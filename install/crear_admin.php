<?php
// /install/crear_admin.php
$page_title = "Paso 2: Crear Administrador";
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
        <h1>Crear Cuenta de Administrador</h1>
        <p>Esta será la cuenta principal para gestionar el sistema.</p>
        <form action="procesar_admin.php" method="post">
            <label for="username">Nombre de Usuario:</label>
            <input type="text" name="username" required>
            <label for="email">Email:</label>
            <input type="email" name="email" required>
            <label for="password">Contraseña:</label>
            <input type="password" name="password" required>
            <button type="submit">Crear Administrador y Continuar</button>
        </form>
    </div>
</body>
</html>