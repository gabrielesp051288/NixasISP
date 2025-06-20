<?php
// /nav.php

// Esta constante es definida en config.php, que se carga muy al principio del sistema.
// La variable $conexion también está disponible globalmente si se necesita aquí.

// Preparamos el texto del usuario para mostrarlo en el enlace de logout.
$nombre_usuario_display = '';
if (isset($_SESSION['usuario_nombre']) && !empty($_SESSION['usuario_nombre'])) {
    $nombre_usuario_display = ' (' . htmlspecialchars($_SESSION['usuario_nombre']) . ')';
}
?>
<nav>
    <a href="<?= BASE_PATH ?>dashboard.php" style="font-weight:bold;">Dashboard</a>
    <a href="<?= BASE_PATH ?>clients/">Clientes</a>
    <a href="<?= BASE_PATH ?>invoices/">Facturación</a>
    <?php if (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'administrador'): ?>
        <a href="<?= BASE_PATH ?>plans/">Planes de Servicios</a>
        <a href="<?= BASE_PATH ?>reports/">Reportes</a>
        <a href="<?= BASE_PATH ?>settings/">Configuración</a>
    <?php endif; ?>
    <a href="<?= BASE_PATH ?>logout.php" style="color:red; margin-left: auto;">Cerrar Sesión<?= $nombre_usuario_display ?></a>
</nav>
<hr>