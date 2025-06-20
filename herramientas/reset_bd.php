<?php
// /herramientas/reset_bd.php

require_once __DIR__ . '/../includes/check_license.php';
session_start();

// ¡MÁXIMA SEGURIDAD! Solo un administrador puede ejecutar esto.
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'administrador') {
    die("Acceso denegado. Esta función es solo para administradores.");
}

$errores = [];

// Usamos una transacción para asegurar que todo se complete con éxito
$conexion->begin_transaction();
try {
    // Desactivamos temporalmente la revisión de claves foráneas para poder truncar las tablas sin errores de orden.
    $conexion->query("SET FOREIGN_KEY_CHECKS = 0;");

    // Lista de tablas a vaciar (datos transaccionales)
    $tablas_a_vaciar = [
        'transacciones',
        'facturas_items',
        'facturas',
        'cliente_servicios',
        'clientes',
        'cron_log',
        'cliente_notas' // Añadimos la tabla de notas que creamos
    ];

    foreach ($tablas_a_vaciar as $tabla) {
        if ($conexion->query("TRUNCATE TABLE `$tabla`") === FALSE) {
            throw new Exception("Error al vaciar la tabla: $tabla. " . $conexion->error);
        }
    }

    // --- AHORA TAMBIÉN RESETEAMOS LA CONFIGURACIÓN A SUS VALORES POR DEFECTO ---
    
    // Configuración de la empresa
    $conexion->query("UPDATE configuracion SET valor = 'Nombre de tu Empresa' WHERE clave = 'company_name'");
    $conexion->query("UPDATE configuracion SET valor = '' WHERE clave = 'company_address'");
    $conexion->query("UPDATE configuracion SET valor = '' WHERE clave = 'company_tax_id'");
    $conexion->query("UPDATE configuracion SET valor = '' WHERE clave = 'company_phone'");
    $conexion->query("UPDATE configuracion SET valor = '' WHERE clave = 'company_logo'");
    
    // Configuración de facturación
    $conexion->query("UPDATE configuracion SET valor = '10' WHERE clave = 'dia_vencimiento_factura'");

    // --- NUEVO: Resetear la configuración de la licencia ---
    $conexion->query("UPDATE configuracion SET valor = 'unlicensed' WHERE clave = 'license_status'");
    $conexion->query("UPDATE configuracion SET valor = '' WHERE clave = 'license_key'");
    $conexion->query("UPDATE configuracion SET valor = '' WHERE clave = 'license_holder_id'");

    // Reactivamos la revisión de claves foráneas. ¡MUY IMPORTANTE!
    $conexion->query("SET FOREIGN_KEY_CHECKS = 1;");

    // Si todo fue bien, confirmamos los cambios
    $conexion->commit();
    $_SESSION['reset_exito'] = "¡Éxito! Todos los datos de clientes, facturas y configuración han sido reseteados.";

} catch (Exception $e) {
    // Si algo falló, revertimos todo
    $conexion->rollback();
    $_SESSION['reset_error'] = "Ocurrió un error durante el reseteo: " . $e->getMessage();
}

$conexion->close();

header("Location: ../dashboard.php");
exit();
?>