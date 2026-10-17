
<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(204);
    exit;
}

require_once __DIR__ . "/../conexion.php";

$metodo = $_SERVER["REQUEST_METHOD"];

function responder($codigo, $datos) {
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

function obtenerDatos() {
    $datos = json_decode(file_get_contents("php://input"), true);
    if (!is_array($datos)) {
        responder(400, ["error" => "El JSON enviado no es válido."]);
    }
    return $datos;
}

function validarProducto($datos) {
    foreach (["nombre", "proveedor", "fecha_vencimiento", "precio", "stock"] as $campo) {
        if (!isset($datos[$campo]) || $datos[$campo] === "") {
            responder(400, ["error" => "El campo $campo es obligatorio."]);
        }
    }

    if (!is_string($datos["nombre"]) || trim($datos["nombre"]) === "" ||
        !is_string($datos["proveedor"]) || trim($datos["proveedor"]) === "") {
        responder(400, ["error" => "Nombre y proveedor deben contener texto."]);
    }

    if (strlen($datos["nombre"]) > 100 || strlen($datos["proveedor"]) > 100) {
        responder(400, ["error" => "Nombre y proveedor admiten máximo 100 caracteres."]);
    }

    $fecha = DateTime::createFromFormat("!Y-m-d", (string)$datos["fecha_vencimiento"]);
    if (!$fecha || $fecha->format("Y-m-d") !== $datos["fecha_vencimiento"]) {
        responder(400, ["error" => "La fecha debe tener el formato AAAA-MM-DD."]);
    }

    if (!is_numeric($datos["precio"]) || !is_finite((float)$datos["precio"]) ||
        (float)$datos["precio"] < 0 || (float)$datos["precio"] > 99999999.99) {
        responder(400, ["error" => "El precio no es válido."]);
    }

    if (!(is_int($datos["stock"]) ||
          (is_string($datos["stock"]) && ctype_digit($datos["stock"]))) ||
        (float)$datos["stock"] < 0 || (float)$datos["stock"] > 2147483647) {
        responder(400, ["error" => "El stock debe ser un entero no negativo."]);
    }
}

try {
    switch ($metodo) {

        // CONSULTAR productos activos
        case "GET":
            if (isset($_GET["id"])) {
                $id = filter_var($_GET["id"], FILTER_VALIDATE_INT);
                if (!$id || $id < 1) {
                    responder(400, ["error" => "ID no válido."]);
                }

                $consulta = $conexion->prepare(
                    "SELECT id, nombre, proveedor, fecha_vencimiento,
                            precio, stock
                     FROM Producto
                     WHERE id = ? AND eliminado = 0"
                );
                $consulta->execute([$id]);
                $producto = $consulta->fetch();

                if (!$producto) {
                    responder(404, ["error" => "Producto no encontrado."]);
                }

                responder(200, $producto);
            }

            $consulta = $conexion->query(
                "SELECT id, nombre, proveedor, fecha_vencimiento,
                        precio, stock
                 FROM Producto
                 WHERE eliminado = 0
                 ORDER BY id DESC"
            );
            responder(200, $consulta->fetchAll());

        // REGISTRAR producto
        case "POST":
            $datos = obtenerDatos();
            validarProducto($datos);

            $consulta = $conexion->prepare(
                "INSERT INTO Producto
                 (nombre, proveedor, fecha_vencimiento, precio, stock)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $consulta->execute([
                trim($datos["nombre"]),
                trim($datos["proveedor"]),
                $datos["fecha_vencimiento"],
                $datos["precio"],
                $datos["stock"]
            ]);

            responder(201, [
                "mensaje" => "Producto registrado correctamente.",
                "id" => (int)$conexion->lastInsertId()
            ]);

        // EDITAR producto
        case "PUT":
            $id = filter_var($_GET["id"] ?? null, FILTER_VALIDATE_INT);
            if (!$id || $id < 1) {
                responder(400, ["error" => "Debes indicar un ID válido."]);
            }

            $datos = obtenerDatos();
            validarProducto($datos);

            $consulta = $conexion->prepare(
                "UPDATE Producto
                 SET nombre = ?, proveedor = ?, fecha_vencimiento = ?,
                     precio = ?, stock = ?
                 WHERE id = ? AND eliminado = 0"
            );
            $consulta->execute([
                trim($datos["nombre"]),
                trim($datos["proveedor"]),
                $datos["fecha_vencimiento"],
                $datos["precio"],
                $datos["stock"],
                $id
            ]);

            if ($consulta->rowCount() === 0) {
                $verificar = $conexion->prepare(
                    "SELECT id FROM Producto WHERE id = ? AND eliminado = 0"
                );
                $verificar->execute([$id]);

                if (!$verificar->fetch()) {
                    responder(404, ["error" => "Producto no encontrado."]);
                }
            }

            responder(200, ["mensaje" => "Producto actualizado correctamente."]);

        // ELIMINAR lógicamente
        case "DELETE":
            $id = filter_var($_GET["id"] ?? null, FILTER_VALIDATE_INT);
            if (!$id || $id < 1) {
                responder(400, ["error" => "Debes indicar un ID válido."]);
            }

            $consulta = $conexion->prepare(
                "UPDATE Producto SET eliminado = 1
                 WHERE id = ? AND eliminado = 0"
            );
            $consulta->execute([$id]);

            if ($consulta->rowCount() === 0) {
                responder(404, ["error" => "Producto no encontrado o ya eliminado."]);
            }

            responder(200, ["mensaje" => "Producto eliminado lógicamente."]);

        default:
            header("Allow: GET, POST, PUT, DELETE, OPTIONS");
            responder(405, ["error" => "Método HTTP no permitido."]);
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    responder(500, ["error" => "Error al procesar la operación en la base de datos."]);
}
