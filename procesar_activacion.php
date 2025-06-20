<?php
// /procesar_activacion.php (Versión para Cliente Final)

// Incluimos los archivos necesarios.
include 'conexion.php';
include 'config.php'; 
$conexion = getConexion();

// Verificamos que se haya accedido a través del formulario POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Obtenemos los datos del formulario y los limpiamos
    $dni_ingresado = trim($_POST['dni']);
    $clave_ingresada = trim(strtoupper($_POST['license_key']));

    $es_valida = false;

    // --- LÓGICA DE VALIDACIÓN (SIN LICENCIA MAESTRA) ---
    // Directamente calculamos la licencia que debería corresponder al DNI ingresado.
    
    // Usamos la constante secreta de nuestro archivo config.php
    $string_a_hashear = $dni_ingresado . LICENSE_SALT;
    $hash = md5($string_a_hashear);
    
    // Formateamos el hash para que tenga el formato XXXX-XXXX-XXXX-XXXX
    $clave_calculada = strtoupper(
        substr($hash, 0, 4) . '-' .
        substr($hash, 4, 4) . '-' .
        substr($hash, 8, 4) . '-' .
        substr($hash, 12, 4)
    );

    // Comparamos la clave que calculamos con la que el usuario introdujo.
    if ($clave_ingresada === $clave_calculada) {
        $es_valida = true;
    }

    // --- ACCIÓN FINAL ---
    // Si la licencia es válida, la guardamos en la BD y desbloqueamos el sistema
    if ($es_valida) {
        $stmt1 = $conexion->prepare("UPDATE configuracion SET valor = 'licensed' WHERE clave = 'license_status'");
        $stmt2 = $conexion->prepare("UPDATE configuracion SET valor = ? WHERE clave = 'license_key'");
        $stmt3 = $conexion->prepare("UPDATE configuracion SET valor = ? WHERE clave = 'license_holder_id'");
        
        $stmt1->execute();
        
        $stmt2->bind_param("s", $clave_ingresada);
        $stmt2->execute();
        
        $stmt3->bind_param("s", $dni_ingresado);
        $stmt3->execute();

        // Éxito: Lo enviamos al dashboard para que pueda iniciar sesión.
        header("Location: dashboard.php");
        exit();
    } else {
        // Fracaso: Lo devolvemos a la página de activación con un mensaje de error.
        header("Location: activacion.php?error=invalida");
        exit();
    }
} else {
    // Si se intenta acceder al archivo directamente, redirigimos.
    header("Location: activacion.php");
    exit();
}
?>