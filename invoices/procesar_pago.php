<?php
session_start();
require_once __DIR__ . '/../includes/check_license.php';
include '../includes/funciones_email.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $factura_id = $_POST['factura_id'];
    $cliente_id = $_POST['cliente_id'];
    $monto_pagado = $_POST['monto'];
    $metodo_pago = $_POST['metodo_pago'];
    $fecha_pago = $_POST['fecha'];
    $descripcion = $_POST['descripcion'];

    // Obtenemos toda la configuración para pasarla a la función de email
    $config_db = $conexion->query("SELECT clave, valor FROM configuracion");
    $config = [];
    while($row = $config_db->fetch_assoc()) {
        $config[$row['clave']] = $row['valor'];
    }

    // Obtenemos el email y nombre del cliente
    $stmt_cliente = $conexion->prepare("SELECT email, nombre_completo FROM clientes WHERE id = ?");
    $stmt_cliente->bind_param("i", $cliente_id);
    $stmt_cliente->execute();
    $cliente = $stmt_cliente->get_result()->fetch_assoc();
    $stmt_cliente->close();

    $conexion->begin_transaction();
    try {
        // 1. Actualizar el estado de la factura a 'pagada'
        $stmt_factura = $conexion->prepare("UPDATE facturas SET estado = 'pagada' WHERE id = ?");
        $stmt_factura->bind_param("i", $factura_id);
        $stmt_factura->execute();
        $stmt_factura->close();

        // 2. Insertar el registro en la nueva tabla 'transacciones'
        $stmt_transaccion = $conexion->prepare("INSERT INTO transacciones (cliente_id, factura_id, fecha, descripcion, monto, metodo_pago) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_transaccion->bind_param("iisdss", $cliente_id, $factura_id, $fecha_pago, $descripcion, $monto_pagado, $metodo_pago);
        $stmt_transaccion->execute();
        $stmt_transaccion->close();

        $conexion->commit();

        // --- ENVIAR EMAIL DE CONFIRMACIÓN ---
        if ($cliente && !empty($cliente['email'])) {
            $asunto = "Confirmación de Pago - Factura #" . $factura_id;
            $cuerpo_html = "<h1>¡Gracias por tu pago!</h1>
                           <p>Hola " . htmlspecialchars($cliente['nombre_completo']) . ",</p>
                           <p>Hemos registrado exitosamente tu pago de $" . number_format($monto_pagado, 2, ',', '.') . " para la factura #$factura_id.</p>
                           <p>Gracias por ser parte de " . htmlspecialchars($config['company_name']) . ".</p>";
            
            // La contraseña de SMTP se obtiene de la BD, pero no se guarda en la variable $config para no exponerla innecesariamente
            $smtp_pass_result = $conexion->query("SELECT valor FROM configuracion WHERE clave = 'email_smtp_pass'");
            $config['email_smtp_pass'] = $smtp_pass_result->fetch_assoc()['valor'];

            enviarEmail($config, $cliente['email'], $cliente['nombre_completo'], $asunto, $cuerpo_html);
        }
        
        header("Location: index.php?exito=pago_registrado");
        exit();

    } catch (Exception $e) {
        $conexion->rollback();
        die("Error al procesar el pago: " . $e->getMessage());
    }
} else {
    header("Location: index.php");
    exit();
}
?>