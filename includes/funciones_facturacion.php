<?php
// /includes/funciones_facturacion.php (Versión de prueba con logo desactivado)

require_once __DIR__ . '/fpdf.php'; // Requerimos la librería FPDF

/**
 * Crea el registro de una factura en la base de datos.
 * Lee el día de vencimiento desde la tabla de configuración.
 * @return array Un array con el resultado.
 */
function crearFactura($conexion, $cliente_id, $cliente_servicio_id) {
    // --- 0. OBTENER CONFIGURACIÓN ---
    $resultado_conf = $conexion->query("SELECT valor FROM configuracion WHERE clave = 'dia_vencimiento_factura' LIMIT 1");
    $dia_de_vencimiento = $resultado_conf->fetch_assoc()['valor'] ?? 10;

    // --- 1. OBTENER DATOS ---
    $stmt_servicio = $conexion->prepare("SELECT * FROM cliente_servicios WHERE id = ?");
    $stmt_servicio->bind_param("i", $cliente_servicio_id);
    $stmt_servicio->execute();
    $servicio = $stmt_servicio->get_result()->fetch_assoc();
    if (!$servicio) return ['exito' => false, 'mensaje' => 'Servicio no válido.'];

    $stmt_plan = $conexion->prepare("SELECT nombre FROM planes WHERE id = ?");
    $stmt_plan->bind_param("i", $servicio['plan_id']);
    $stmt_plan->execute();
    $plan = $stmt_plan->get_result()->fetch_assoc();
    if (!$plan) return ['exito' => false, 'mensaje' => 'El plan asociado al servicio no fue encontrado.'];

    // --- 2. VALIDACIÓN DE DUPLICADOS ---
    $periodo_mes = date('m');
    $periodo_anho = date('Y');
    
    $stmt_check = $conexion->prepare("SELECT id FROM facturas WHERE cliente_servicio_id = ? AND MONTH(fecha_emision) = ? AND YEAR(fecha_emision) = ?");
    $stmt_check->bind_param("iss", $cliente_servicio_id, $periodo_mes, $periodo_anho);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) {
        return ['exito' => false, 'mensaje' => 'Ya existe una factura para este servicio en el período actual.'];
    }

    // --- 3. CREACIÓN DE LA FACTURA (TRANSACCIÓN) ---
    $conexion->begin_transaction();
    try {
        $fecha_emision = date('Y-m-d');
        $fecha_obj = new DateTime($fecha_emision);
        $fecha_obj->modify('first day of next month');
        $dias_a_sumar = $dia_de_vencimiento - 1;
        $fecha_obj->modify('+' . $dias_a_sumar . ' days');
        $fecha_vencimiento = $fecha_obj->format('Y-m-d'); 

        $total = $servicio['precio_pactado'];
        $estado = 'pendiente';

        $stmt_factura = $conexion->prepare("INSERT INTO facturas (cliente_id, cliente_servicio_id, fecha_emision, fecha_vencimiento, total, estado) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_factura->bind_param("iissds", $cliente_id, $cliente_servicio_id, $fecha_emision, $fecha_vencimiento, $total, $estado);
        $stmt_factura->execute();
        $factura_id = $conexion->insert_id;

        $nombre_mes_es = ['January'=>'Enero', 'February'=>'Febrero', 'March'=>'Marzo', 'April'=>'Abril', 'May'=>'Mayo', 'June'=>'Junio', 'July'=>'Julio', 'August'=>'Agosto', 'September'=>'Septiembre', 'October'=>'Octubre', 'November'=>'Noviembre', 'December'=>'Diciembre'];
        $nombre_mes_en = date('F', strtotime($fecha_emision));
        $periodo_facturacion = $nombre_mes_es[$nombre_mes_en] . " " . date('Y', strtotime($fecha_emision));
        $descripcion_item = "Servicio " . $plan['nombre'] . " - Período " . $periodo_facturacion;

        $stmt_item = $conexion->prepare("INSERT INTO facturas_items (factura_id, plan_id, descripcion, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, 1, ?, ?)");
        $stmt_item->bind_param("iisdd", $factura_id, $servicio['plan_id'], $descripcion_item, $total, $total);
        $stmt_item->execute();
        
        $conexion->commit();
        return ['exito' => true, 'factura_id' => $factura_id, 'mensaje' => 'Factura creada con éxito.'];
    } catch (Exception $e) {
        $conexion->rollback();
        return ['exito' => false, 'mensaje' => 'Error de base de datos: ' . $e->getMessage()];
    }
}

// --- DEFINICIÓN DE LA CLASE PDF (FUERA DE LA FUNCIÓN) ---
if (!class_exists('PDF')) {
    class PDF extends FPDF {
        private $company_data;
        function __construct($company_data) { parent::__construct(); $this->company_data = $company_data; }
        
        function Header() {
            // ==========================================================
            // === LÍNEAS DEL LOGO TEMPORALMENTE DESACTIVADAS PARA LA PRUEBA ===
            /*
            if (!empty($this->company_data['company_logo'])) {
                $logoPath = __DIR__ . '/../assets/uploads/' . $this->company_data['company_logo'];
                if(file_exists($logoPath)) $this->Image($logoPath, 10, 8, 40);
            }
            */
            // ==========================================================

            $this->SetFont('Arial','B',18);
            $this->Cell(80);
            $this->Cell(30,10,'FACTURA',0,0,'C');
            $this->Ln(25);
        }
        
        function Footer() {
            $this->SetY(-15); 
            $this->SetFont('Arial','I',8);
            $this->Cell(0,10,utf8_decode('Página ') . $this->PageNo(),0,0,'C');
        }
    }
}

// --- FUNCIÓN PARA GENERAR Y GUARDAR EL PDF ---
function generarYGuardarPdfFactura($conexion, $factura_id, $config_empresa) {
    // Obtener datos de la factura
    $sql = "SELECT f.*, c.nombre_completo, c.numero_de_cliente, c.address, c.ciudad, c.provincia FROM facturas f JOIN clientes c ON f.cliente_id = c.id WHERE f.id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $factura_id);
    $stmt->execute();
    $factura = $stmt->get_result()->fetch_assoc();
    if (!$factura) return false;

    // Obtener items de la factura
    $stmt_items = $conexion->prepare("SELECT * FROM facturas_items WHERE factura_id = ?");
    $stmt_items->bind_param("i", $factura_id);
    $stmt_items->execute();
    $items = $stmt_items->get_result();
    
    // Crear una instancia de la clase PDF
    $pdf = new PDF($config_empresa);
    $pdf->AliasNbPages();
    $pdf->AddPage();
    
    // --- DIBUJAR EL CONTENIDO DEL PDF ---
    $pdf->SetFont('Arial','B',11);
    $pdf->Cell(100, 7, utf8_decode($config_empresa['company_name']), 0, 1, 'L');
    $pdf->SetFont('Arial','',11);
    $pdf->MultiCell(100, 7, utf8_decode($config_empresa['company_address']), 0, 'L');
    $pdf->Cell(100, 7, 'CUIT: ' . utf8_decode($config_empresa['company_tax_id']), 0, 1, 'L');
    $pdf->Cell(100, 7, utf8_decode('Teléfono: ') . utf8_decode($config_empresa['company_phone']), 0, 1, 'L');

    $pdf->SetY(40); $pdf->SetX(-70);
    $pdf->SetFont('Arial','B',11);
    $pdf->Cell(60, 7, 'Factura Nro: ' . $factura['id'], 1, 1, 'C');
    $pdf->SetX(-70);
    $pdf->Cell(60, 7, 'Fecha Emision: ' . date("d/m/Y", strtotime($factura['fecha_emision'])), 1, 1, 'C');
    $pdf->SetX(-70);
    $pdf->Cell(60, 7, 'Fecha Vencimiento: ' . date("d/m/Y", strtotime($factura['fecha_vencimiento'])), 1, 1, 'C');

    $pdf->SetY(80); $pdf->SetFont('Arial','B',11);
    $pdf->Cell(0, 7, 'Facturado a:', 0, 1, 'L');
    $pdf->SetFont('Arial','',11);
    $pdf->Cell(0, 7, utf8_decode($factura['nombre_completo']), 0, 1, 'L');
    $pdf->Cell(0, 7, 'Nro. Cliente: ' . utf8_decode($factura['numero_de_cliente']), 0, 1, 'L');
    $pdf->Ln(10);

    $pdf->SetFont('Arial','B',12);
    $pdf->SetFillColor(230,230,230);
    $pdf->Cell(150, 10, utf8_decode('Descripción'), 1, 0, 'C', true);
    $pdf->Cell(40, 10, 'Importe', 1, 1, 'C', true);

    $pdf->SetFont('Arial','',11);
    while($item = $items->fetch_assoc()) {
        $pdf->Cell(150, 10, utf8_decode($item['descripcion']), 1, 0, 'L');
        $pdf->Cell(40, 10, '$' . number_format($item['subtotal'], 2, ',', '.'), 1, 1, 'R');
    }

    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(150, 10, 'TOTAL A PAGAR', 1, 0, 'R');
    $pdf->Cell(40, 10, '$' . number_format($factura['total'], 2, ',', '.'), 1, 1, 'R');

    // --- GUARDAR EL PDF EN EL SERVIDOR ---
    $directorio_pdf = __DIR__ . '/../assets/temp_pdf/';
    if (!file_exists($directorio_pdf)) {
        mkdir($directorio_pdf, 0777, true);
    }
    $nombre_archivo = 'Factura-' . $factura_id . '-' . time() . '.pdf';
    $ruta_completa = $directorio_pdf . $nombre_archivo;
    
    $pdf->Output('F', $ruta_completa); 
    
    // Devolvemos la ruta y nombre del archivo si se creó con éxito
    if(file_exists($ruta_completa)) {
        return ['ruta' => $ruta_completa, 'nombre' => 'Factura-'.$factura['id'].'.pdf'];
    } else {
        return false;
    }
}
?>