<?php
ob_start();
set_time_limit(300); 
chdir(dirname(__FILE__));
require_once __DIR__ . '/../includes/check_license.php';
include '../includes/funciones_facturacion.php';
include '../includes/funciones_email.php';

// Obtenemos la configuración una sola vez
$config_db = $conexion->query("SELECT clave, valor FROM configuracion");
$config = [];
while($row = $config_db->fetch_assoc()) $config[$row['clave']] = $row['valor'];
$smtp_pass_result = $conexion->query("SELECT valor FROM configuracion WHERE clave = 'email_smtp_pass'");
$config['email_smtp_pass'] = $smtp_pass_result->fetch_assoc()['valor'];

$script_name = 'generacion_facturas';
$estado_final = 'Exitoso';

echo "--------------------------------------------------\n";
echo "INICIO DE PROCESO AUTOMÁTICO\n";
echo "Fecha y Hora: " . date('Y-m-d H:i:s') . "\n";
echo "--------------------------------------------------\n\n";

try {
    echo "FASE 1: Buscando facturas vencidas...\n";
    $hoy_fecha = date('Y-m-d');
    $sql_vencidas = "UPDATE facturas SET estado = 'vencida' WHERE fecha_vencimiento < ? AND estado = 'pendiente'";
    $stmt_vencidas = $conexion->prepare($sql_vencidas);
    $stmt_vencidas->bind_param("s", $hoy_fecha);
    $stmt_vencidas->execute();
    echo " -> Se actualizaron " . $stmt_vencidas->affected_rows . " facturas al estado 'Vencida'.\n\n";
    $stmt_vencidas->close();

    echo "FASE 2: Buscando servicios para facturar...\n";
    $servicios_activos = $conexion->query("SELECT cs.*, c.nombre_completo, c.email FROM cliente_servicios cs JOIN clientes c ON cs.cliente_id = c.id WHERE cs.estado = 'activo'");
    
    if ($servicios_activos->num_rows === 0) {
        echo " -> No hay servicios activos para procesar.\n";
    } else {
        $facturas_creadas = 0;
        while ($servicio = $servicios_activos->fetch_assoc()) {
            $cliente_id = $servicio['cliente_id'];
            $servicio_id = $servicio['id'];
            $fecha_activacion = new DateTime($servicio['fecha_activacion']);
            $dia_facturacion = (int)$fecha_activacion->format('d');
            $hoy = new DateTime();
            $dia_de_hoy = (int)$hoy->format('d');

            echo "Procesando Servicio #$servicio_id para Cliente #$cliente_id... \n";

            if ($dia_de_hoy >= $dia_facturacion) {
                echo "  -> Día de facturación OK. Creando factura...\n";
                $resultado_creacion = crearFactura($conexion, $cliente_id, $servicio_id);

                if ($resultado_creacion['exito']) {
                    $factura_id_creada = $resultado_creacion['factura_id'];
                    echo "  -> ¡ÉXITO! Factura #$factura_id_creada creada.\n";
                    $facturas_creadas++;
                    
                    if (!empty($servicio['email'])) {
                        echo "  -> Generando PDF para Factura #$factura_id_creada...\n";
                        $pdf_info = generarYGuardarPdfFactura($conexion, $factura_id_creada, $config);

                        if ($pdf_info && file_exists($pdf_info['ruta'])) {
                            echo "  -> PDF generado. Enviando email a " . $servicio['email'] . "...\n";
                            $asunto = "Tu factura de " . $config['company_name'] . " (Nro. " . $factura_id_creada . ")";
                            $cuerpo_html = "Hola " . $servicio['nombre_completo'] . ",<br><br>Te adjuntamos tu nueva factura correspondiente al período actual. Puedes verla en el archivo adjunto.<br><br>Gracias por confiar en nosotros.<br><br>Atentamente,<br>" . $config['company_name'];
                            
                            if(enviarEmail($config, $servicio['email'], $servicio['nombre_completo'], $asunto, $cuerpo_html, $pdf_info['ruta'], $pdf_info['nombre'])) {
                                echo "  -> ¡ÉXITO! Email enviado correctamente.\n";
                            } else {
                                echo "  -> ERROR: No se pudo enviar el email.\n";
                            }
                            unlink($pdf_info['ruta']);
                        } else {
                            echo "  -> ERROR: No se pudo generar el archivo PDF.\n";
                        }
                    } else {
                        echo "  -> INFO: El cliente no tiene un email registrado para notificar.\n";
                    }
                } else {
                    echo "  -> INFO: " . $resultado_creacion['mensaje'] . "\n";
                }
            }
            echo "\n";
        }
        echo " -> Total de facturas nuevas creadas: " . $facturas_creadas . "\n";
    }

} catch (Exception $e) {
    $estado_final = 'Fallido';
    echo "¡¡¡ERROR FATAL!!! El proceso fue interrumpido.\n";
    echo "Mensaje de error: " . $e->getMessage() . "\n";
}

echo "\n--------------------------------------------------\n";
echo "PROCESO FINALIZADO.\n";
echo "--------------------------------------------------\n";

$resumen_ejecucion = ob_get_clean();
echo $resumen_ejecucion;

if ($conexion && $conexion->ping()) {
    $fecha_actual = date('Y-m-d H:i:s');
    $stmt_log = $conexion->prepare("INSERT INTO cron_log (script_name, fecha_ejecucion, estado, resumen) VALUES (?, ?, ?, ?)");
    $stmt_log->bind_param("ssss", $script_name, $fecha_actual, $estado_final, $resumen_ejecucion);
    $stmt_log->execute();
    $conexion->close();
}
?>