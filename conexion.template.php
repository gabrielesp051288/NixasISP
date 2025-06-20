<?php
// /conexion.template.php

// El cliente deberá ajustar la ruta base si instala el sistema en una subcarpeta.
// Por ejemplo: /sistema/
// Si está en el dominio principal, debe ser /
define('BASE_PATH', '/'); 

function getConexion() {
    static $conexion = null;
    if ($conexion === null) {
        // --- DATOS A COMPLETAR POR EL USUARIO FINAL ---
        $servidor = "localhost";
        $usuario = "USUARIO_DE_BD";
        $password = "CONTRASEÑA_DE_BD";
        $base_de_datos = "NOMBRE_DE_BD";
        // ---------------------------------------------

        @$conexion = new mysqli($servidor, $usuario, $password, $base_de_datos);

        if ($conexion->connect_error) {
            return false;
        }
        $conexion->set_charset("utf8mb4");
    }
    return $conexion;
}
?>