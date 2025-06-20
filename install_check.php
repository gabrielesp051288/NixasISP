<?php
// /install_check.php

// Si el config.php no existe, es una instalación 100% nueva.
if (!file_exists(__DIR__ . '/config.php')) {
    $uri = $_SERVER['REQUEST_URI'];
    // Construye la ruta al instalador de forma dinámica
    $install_path = substr($uri, 0, strrpos($uri, '/')) . '/install/';
    // Asegurarse de que no haya dobles barras
    $install_path = str_replace('//', '/', $install_path);
    header('Location: ' . $install_path);
    exit();
}
?>