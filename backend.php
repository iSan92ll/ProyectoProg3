<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

$dsn = "pgsql:host=dpg-cv5nejjqf0us73epn15g-a.oregon-postgres.render.com;port=5432;dbname=tienda_db_31ib";
$username = "tienda_db_31ib_user";
$password = "FnGynAoGsAX729pDUasq2pRgjdAsAwyQ";

try {
    $conn = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    echo json_encode(["error" => "Conexión fallida: " . $e->getMessage()]);
    exit();
}

$action = $_GET['action'] ?? '';

try {
    if ($action == "create") {
        if (!isset($_POST['tipo'], $_POST['producto'], $_POST['precio'])) {
            throw new Exception("Datos incompletos");
        }
        
        $tipo = $_POST['tipo'];
        $producto = $_POST['producto'];
        $precio = $_POST['precio'];
        $disponibilidad = $_POST['disponibilidad'] ?? 1;
        $talla = $_POST['talla'] ?? null;
        
        $sql = "INSERT INTO productos (tipo, precio, disponibilidad) VALUES (:tipo, :precio, :disponibilidad)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':tipo' => $tipo, ':precio' => $precio, ':disponibilidad' => $disponibilidad]);
        $id_productos = $conn->lastInsertId();
        
        if (!$id_productos) {
            throw new Exception("Error al insertar producto");
        }
        
        if ($tipo == "ropa") {
            $sql = "INSERT INTO ropa (id_ropa, prenda, talla, precio, disponibilidad) VALUES (:id_ropa, :prenda, :talla, :precio, :disponibilidad)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':id_ropa' => $id_productos, ':prenda' => $producto, ':talla' => $talla, ':precio' => $precio, ':disponibilidad' => $disponibilidad]);
        } elseif ($tipo == "comida") {
            $sql = "INSERT INTO comida (id_comida, producto, precio, disponibilidad) VALUES (:id_comida, :producto, :precio, :disponibilidad)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':id_comida' => $id_productos, ':producto' => $producto, ':precio' => $precio, ':disponibilidad' => $disponibilidad]);
        }
        
        echo json_encode(["message" => "Producto agregado", "id" => $id_productos]);
    }

    if ($action == "update") {
        if (!isset($_POST['id'], $_POST['tipo'], $_POST['producto'], $_POST['precio'])) {
            throw new Exception("Datos incompletos");
        }
        
        $id_productos = $_POST['id'];
        $tipo = $_POST['tipo'];
        $producto = $_POST['producto'];
        $precio = $_POST['precio'];
        $disponibilidad = $_POST['disponibilidad'] ?? 1;
        $talla = $_POST['talla'] ?? null;
        
        $sql = "UPDATE productos SET tipo=:tipo, precio=:precio, disponibilidad=:disponibilidad WHERE id_productos=:id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':tipo' => $tipo, ':precio' => $precio, ':disponibilidad' => $disponibilidad, ':id' => $id_productos]);
        
        if ($stmt->rowCount() == 0) {
            throw new Exception("No se encontró el producto");
        }
        
        echo json_encode(["message" => "Producto actualizado"]);
    }

    if ($action == "delete") {
        if (!isset($_POST['id'])) {
            throw new Exception("ID no proporcionado");
        }
        
        $id_productos = $_POST['id'];
        
        $sql = "DELETE FROM productos WHERE id_productos=:id_productos";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':id_productos' => $id_productos]);
        
        if ($stmt->rowCount() == 0) {
            throw new Exception("Producto no encontrado");
        }
        
        echo json_encode(["message" => "Producto eliminado"]);
    }
} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}

$conn = null;
?>
