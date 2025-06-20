 
<?php
session_start();
require_once __DIR__ . '/../includes/check_license.php';
require('../includes/fpdf.php'); // Requerimos la librería que descargamos

if (!isset($_SESSION['usuario_id'])) die("Acceso denegado.");

// --- OBTENER TODOS LOS DATOS NECESARIOS ---
$factura_id = $_GET['id'] ?? 0;
// Datos de la factura y del cliente
$stmt_factura = $conexion->prepare("SELECT f.*, c.nombre_completo, c.numero_de_cliente, c.address, c.ciudad, c.provincia FROM facturas f JOIN clientes c ON f.cliente_id = c.id WHERE f.id = ?");
$stmt_factura->bind_param("i", $factura_id);
$stmt_factura->execute();
$factura = $stmt_factura->get_result()->fetch_assoc();
if (!$factura) die("Factura no encontrada.");

// Datos de tu empresa desde la configuración
$configuracion_db = $conexion->query("SELECT clave, valor FROM configuracion");
$settings = [];
while($row = $configuracion_db->fetch_assoc()) { $settings[$row['clave']] = $row['valor']; }

// Items de la factura
$stmt_items = $conexion->prepare("SELECT * FROM facturas_items WHERE factura_id = ?");
$stmt_items->bind_param("i", $factura_id);
$stmt_items->execute();
$items = $stmt_items->get_result();

// --- CLASE PDF PERSONALIZADA PARA TENER HEADER Y FOOTER ---
class PDF extends FPDF {
    private $company_data;
    function __construct($company_data = []) {
        parent::__construct();
        $this->company_data = $company_data;
    }
    // Cabecera de página
    function Header() {
        // Logo
        if (!empty($this->company_data['company_logo'])) {
            $this->Image('../assets/uploads/' . $this->company_data['company_logo'], 10, 8, 33);
        }
        // Arial bold 15
        $this->SetFont('Arial','B',15);
        $this->Cell(80); // Moverse a la derecha
        $this->Cell(30,10,'FACTURA',1,0,'C'); // Título
        $this->Ln(20); // Salto de línea
    }
    // Pie de página
    function Footer() {
        $this->SetY(-15); // Posición a 1.5 cm del final
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,utf8_decode('Página ') . $this->PageNo() . '/{nb}',0,0,'C');
    }
}

// --- CREACIÓN DEL DOCUMENTO PDF ---
$pdf = new PDF($settings);
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial','',12);

// --- INFO DE LA EMPRESA Y FACTURA ---
$pdf->SetFont('Arial','B',11);
$pdf->Cell(100, 7, utf8_decode($settings['company_name']), 0, 1, 'L');
$pdf->SetFont('Arial','',11);
$pdf->MultiCell(100, 7, utf8_decode($settings['company_address']), 0, 'L');
$pdf->Cell(100, 7, 'CUIT: ' . utf8_decode($settings['company_tax_id']), 0, 1, 'L');
$pdf->Cell(100, 7, utf8_decode('Teléfono: ') . utf8_decode($settings['company_phone']), 0, 1, 'L');

$pdf->SetY(40);
$pdf->SetX(-70);
$pdf->SetFont('Arial','B',11);
$pdf->Cell(60, 7, 'Factura Nro: ' . $factura['id'], 1, 1, 'C');
$pdf->SetX(-70);
$pdf->Cell(60, 7, 'Fecha Emision: ' . date("d/m/Y", strtotime($factura['fecha_emision'])), 1, 1, 'C');
$pdf->SetX(-70);
$pdf->Cell(60, 7, 'Fecha Vencimiento: ' . date("d/m/Y", strtotime($factura['fecha_vencimiento'])), 1, 1, 'C');

// --- INFO DEL CLIENTE ---
$pdf->SetY(80);
$pdf->SetFont('Arial','B',11);
$pdf->Cell(0, 7, 'Facturado a:', 0, 1, 'L');
$pdf->SetFont('Arial','',11);
$pdf->Cell(0, 7, utf8_decode($factura['nombre_completo']), 0, 1, 'L');
$pdf->Cell(0, 7, 'Nro. Cliente: ' . utf8_decode($factura['numero_de_cliente']), 0, 1, 'L');
$pdf->Cell(0, 7, utf8_decode($factura['address']), 0, 1, 'L');
$pdf->Ln(10);

// --- TABLA DE ITEMS ---
$pdf->SetFont('Arial','B',12);
$pdf->Cell(150, 10, utf8_decode('Descripción'), 1, 0, 'C', true);
$pdf->Cell(40, 10, 'Importe', 1, 1, 'C', true);

$pdf->SetFont('Arial','',11);
while($item = $items->fetch_assoc()) {
    $pdf->Cell(150, 10, utf8_decode($item['descripcion']), 1, 0, 'L');
    $pdf->Cell(40, 10, '$' . number_format($item['subtotal'], 2, ',', '.'), 1, 1, 'R');
}

// --- TOTALES ---
$pdf->SetFont('Arial','B',12);
$pdf->Cell(150, 10, 'TOTAL A PAGAR', 1, 0, 'R');
$pdf->Cell(40, 10, '$' . number_format($factura['total'], 2, ',', '.'), 1, 1, 'R');

// --- SALIDA DEL PDF ---
// 'I' para mostrarlo en el navegador, 'D' para forzar la descarga
$pdf->Output('I', 'Factura-'.$factura['id'].'.pdf'); 
?>