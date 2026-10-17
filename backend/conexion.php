
<?php
$host = "localhost";
$puerto = 3306;
$base_datos = "db_minimarket_pro413";
$usuario = "root";
$contrasena = "";

try {
    $conexion = new PDO(
        "mysql:host=$host;port=$puerto;dbname=$base_datos;charset=utf8mb4",
        $usuario,
        $contrasena,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode([
        "error" => "No se pudo conectar con la base de datos."
    ]);
    exit;
}
