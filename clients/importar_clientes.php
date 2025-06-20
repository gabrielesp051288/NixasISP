<?php
// /clients/importar_clientes.php

session_start();
require_once __DIR__ . '/../includes/check_license.php';
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'administrador') {
    die("Acceso denegado.");
}

$reporte = ['exitosos' => 0, 'fallidos' => 0, 'errores' => []];

if (isset($_FILES['archivo_csv']) && $_FILES['archivo_csv']['error'] == 0) {
    $nombre_archivo = $_FILES['archivo_csv']['tmp_name'];
    
    if (($gestor = fopen($nombre_archivo, "r")) !== FALSE) {
        fgetcsv($gestor, 1000, ","); // Omitir cabeceras

        // Preparamos las sentencias que usaremos en el bucle
        $stmt_cliente = $conexion->prepare("INSERT INTO clientes (numero_de_cliente, nombre_completo, email, telefono, address, ciudad, provincia, codigo_postal, pais, estado, fecha_alta) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'activo', CURDATE())");
        $stmt_plan = $conexion->prepare("SELECT id, precio FROM planes WHERE nombre = ?");
        $stmt_servicio = $conexion->prepare("INSERT INTO cliente_servicios (cliente_id, plan_id, precio_pactado, fecha_activacion, estado) VALUES (?, ?, ?, CURDATE(), 'activo')");

        while (($datos = fgetcsv($gestor, 1000, ",")) !== FALSE) {
            $conexion->begin_transaction(); // Iniciamos una transacción por cada cliente
            try {
                // Asignamos cada columna a una variable
                $numero_cliente = $datos[0];
                $nombre_completo = $datos[1];
                $email = $datos[2];
                $telefono = $datos[3];
                $direccion = $datos[4];
                $ciudad = $datos[5];
                $provincia = $datos[6];
                $cp = $datos[7];
                $pais = $datos[8];
                $nombre_servicio = $datos[9] ?? null; // El nombre del servicio es la décima columna

                // 1. Insertar el cliente
                $stmt_cliente->bind_param("sssssssss", $numero_cliente, $nombre_completo, $email, $telefono, $direccion, $ciudad, $provincia, $cp, $pais);
                if (!$stmt_cliente->execute()) {
                    throw new Exception("Error al crear cliente (" . $stmt_cliente->error . ")");
                }
                
                $cliente_id = $conexion->insert_id; // Obtenemos el ID del cliente recién creado

                // 2. Si se especificó un servicio, buscarlo y asignarlo
                if (!empty($nombre_servicio)) {
                    $stmt_plan->bind_param("s", $nombre_servicio);
                    $stmt_plan->execute();
                    $resultado_plan = $stmt_plan->get_result();
                    
                    if ($plan = $resultado_plan->fetch_assoc()) {
                        // Si encontramos el plan, lo asignamos
                        $plan_id = $plan['id'];
                        $precio_pactado = $plan['precio'];
                        $stmt_servicio->bind_param("iid", $cliente_id, $plan_id, $precio_pactado);
                        if (!$stmt_servicio->execute()) {
                            throw new Exception("Cliente creado, pero falló la asignación del servicio (" . $stmt_servicio->error . ")");
                        }
                    } else {
                        // Si no encontramos el plan, lo reportamos como un error parcial
                        throw new Exception("Cliente creado, pero el servicio '" . htmlspecialchars($nombre_servicio) . "' no fue encontrado.");
                    }
                }
                
                $conexion->commit(); // Si todo fue bien, confirmamos
                $reporte['exitosos']++;

            } catch (Exception $e) {
                $conexion->rollback(); // Si algo falló, revertimos
                $reporte['fallidos']++;
                $reporte['errores'][] = "Fila con N° Cliente " . htmlspecialchars($numero_cliente) . ": " . $e->getMessage();
            }
        }
        fclose($gestor);
        $stmt_cliente->close();
        $stmt_plan->close();
        $stmt_servicio->close();
    }
}

$_SESSION['reporte_importacion'] = $reporte;
header("Location: index.php");
exit();
?>