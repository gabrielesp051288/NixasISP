<?php
// /includes/header.php (Código Completo)

if (!defined('BASE_PATH')) {
    define('BASE_PATH', '/gestorisp/');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Sistema de Gestión ISP' ?></title>
    <link rel="stylesheet" href="<?= BASE_PATH ?>assets/css/estilos.css">
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include __DIR__ . '/../nav.php'; ?>
    <main class="container">