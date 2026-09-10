<?php
// Proxy a limpiar.php en la raiz
if (file_exists(__DIR__ . '/../limpiar.php')) {
    require_once __DIR__ . '/../limpiar.php';
} else {
    echo "No se encontro limpiar.php";
}
