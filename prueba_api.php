
<?php
$url = "http://localhost/minimarket/backend/api/productos.php";

$producto = [
    "nombre" => "Arroz de prueba",
    "proveedor" => "Proveedor Central",
    "fecha_vencimiento" => "2027-12-31",
    "precio" => 12.50,
    "stock" => 30
];

$opciones = [
    "http" => [
        "method" => "POST",
        "header" => "Content-Type: application/json\r\n",
        "content" => json_encode($producto),
        "ignore_errors" => true
    ]
];

$contexto = stream_context_create($opciones);
$respuesta = file_get_contents($url, false, $contexto);

echo "<h2>Resultado de la prueba</h2>";

if ($respuesta === false) {
    echo "No se pudo conectar con la API.";
} else {
    echo "<pre>";
    echo htmlspecialchars($respuesta);
    echo "</pre>";
}

echo '<p><a href="backend/api/productos.php">Consultar productos</a></p>';
?>
