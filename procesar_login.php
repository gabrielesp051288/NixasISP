<?php
session_start();
require_once __DIR__ . '/includes/check_license.php';

// Validamos que los datos hayan sido enviados por POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Preparamos la consulta para buscar al usuario por SU NOMBRE DE USUARIO
    $stmt = $conexion->prepare("SELECT id, nombre_completo, username, password, rol FROM usuarios WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 1) {
        // El usuario existe, ahora verificamos la contraseña
        $usuario = $resultado->fetch_assoc();

        if (password_verify($password, $usuario['password'])) {
            // La contraseña es correcta. Creamos la sesión.
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_username'] = $usuario['username']; 
            $_SESSION['usuario_nombre'] = $usuario['nombre_completo'];
            $_SESSION['usuario_rol'] = $usuario['rol'];

            // --- ¡AQUÍ ESTÁ LA CORRECCIÓN! ---
            // Usamos la ruta absoluta del proyecto para la redirección.
            header("Location: /gestorisp/dashboard.php");
            exit();
        }
    }

    // Si el usuario no existe o la contraseña es incorrecta, redirigimos al login con un error.
    header("Location: /gestorisp/index.php?error=1");
    exit();

} else {
    // Si alguien intenta acceder al archivo directamente sin enviar datos, lo redirigimos.
    header("Location: /gestorisp/index.php");
    exit();
}
?>