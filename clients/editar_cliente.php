<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit();
}
require_once __DIR__ . '/../includes/check_license.php';
$page_title = "Editar Cliente";
include '../includes/header.php';

$id = $_GET['id'] ?? 0;
$stmt = $conexion->prepare("SELECT * FROM clientes WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$cliente = $stmt->get_result()->fetch_assoc();

if (!$cliente) {
    echo "<h1>Cliente no encontrado.</h1>";
    include '../includes/footer.php';
    exit();
}
?>
<h1>Editar Cliente: <?= htmlspecialchars($cliente['nombre_completo']) ?></h1>
<div class="dashboard-block">
    <form action="guardar_cliente.php" method="post">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" value="<?= $cliente['id'] ?>">
        
        <label for="numero_de_cliente">Número de Cliente:</label>
        <input type="text" id="numero_de_cliente" value="<?= htmlspecialchars($cliente['numero_de_cliente']) ?>" readonly>
        <small>(El número de cliente no se puede cambiar)</small><br><br>

        <label for="nombre_completo">Nombre Completo:</label>
        <input type="text" id="nombre_completo" name="nombre_completo" value="<?= htmlspecialchars($cliente['nombre_completo']) ?>" required>

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($cliente['email']) ?>">

        <label for="telefono">Teléfono:</label>
        <input type="text" id="telefono" name="telefono" value="<?= htmlspecialchars($cliente['telefono']) ?>">

        <label for="address">Dirección:</label>
        <input type="text" id="address" name="address" value="<?= htmlspecialchars($cliente['address']) ?>">

        <label for="ciudad">Ciudad:</label>
        <input type="text" id="ciudad" name="ciudad" value="<?= htmlspecialchars($cliente['ciudad']) ?>">

        <label for="provincia">Provincia:</label>
        <input type="text" id="provincia" name="provincia" value="<?= htmlspecialchars($cliente['provincia']) ?>">

        <label for="codigo_postal">Código Postal:</label>
        <input type="text" id="codigo_postal" name="codigo_postal" value="<?= htmlspecialchars($cliente['codigo_postal']) ?>">

        <label for="pais">País:</label>
        <input type="text" id="pais" name="pais" value="<?= htmlspecialchars($cliente['pais']) ?>">

        <label for="estado">Estado:</label>
        <select id="estado" name="estado" required>
            <option value="activo" <?= ($cliente['estado'] == 'activo') ? 'selected' : '' ?>>Activo</option>
            <option value="suspendido" <?= ($cliente['estado'] == 'suspendido') ? 'selected' : '' ?>>Suspendido</option>
            <option value="baja" <?= ($cliente['estado'] == 'baja') ? 'selected' : '' ?>>Baja</option>
        </select><br><br>

        <button type="submit">Actualizar Cliente</button>
        <a href="index.php" style="margin-left: 15px;">Cancelar</a>
    </form>
</div>
<?php 
$stmt->close();
$conexion->close();
include '../includes/footer.php'; 
?>