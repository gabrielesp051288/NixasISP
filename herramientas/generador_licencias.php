<?php
require_once __DIR__ . '/../includes/check_license.php';

session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'administrador') {
    die("Acceso denegado.");
}

$page_title = 'Generador de Licencias';
include '../includes/header.php';

$dni_cliente = '';
$clave_generada = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['dni'])) {
    $dni_cliente = trim($_POST['dni']);
    
    // Usamos la constante del archivo de configuración que ya fue cargado por check_license.php
    $string_a_hashear = $dni_cliente . LICENSE_SALT;
    $hash = md5($string_a_hashear);

    $clave_generada = strtoupper(
        substr($hash, 0, 4) . '-' . substr($hash, 4, 4) . '-' .
        substr($hash, 8, 4) . '-' . substr($hash, 12, 4)
    );
}
?>
<h1>Generador de Claves de Licencia</h1>

<div class="dashboard-block">
    <h2>Generar Nueva Licencia</h2>
    <p>Introduce el DNI del titular de la licencia para generar su clave única.</p>
    <form action="generador_licencias.php" method="post">
        <label for="dni">DNI del Cliente:</label>
        <input type="text" name="dni" id="dni" value="<?= htmlspecialchars($dni_cliente) ?>" required>
        <button type="submit">Generar Clave</button>
    </form>

    <?php if ($clave_generada): ?>
        <div style="margin-top: 20px;">
            <h3>Clave Generada para DNI <?= htmlspecialchars($dni_cliente) ?>:</h3>
            <pre class="log-output" style="text-align: center; font-size: 1.2em;"><?= $clave_generada ?></pre>
            <p>Copia esta clave y entrégasela a tu cliente junto con su DNI.</p>
        </div>
    <?php endif; ?>
</div>

<?php 
$conexion->close();
include '../includes/footer.php'; 
?>