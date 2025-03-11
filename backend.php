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
    if ($action == "read") {
    $sql = "SELECT p.id_productos, p.tipo,
                   r.id_ropa, r.prenda, r.talla, r.precio AS ropa_precio, r.disponibilidad AS ropa_disponibilidad,
                   c.id_comida, c.producto, c.precio AS comida_precio, c.disponibilidad AS comida_disponibilidad
            FROM productos p
            LEFT JOIN ropa r ON p.id_productos = r.id_ropa
            LEFT JOIN comida c ON p.id_productos = c.id_comida";

    $stmt = $conn->query($sql);
    $productos = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($row['id_ropa']) {
            $productos[] = [
                "id_productos" => $row['id_productos'],
                "id_ropa" => $row['id_ropa'],
                "tipo" => $row['tipo'],
                "producto" => $row['prenda'],
                "precio" => $row['ropa_precio'],
                "disponibilidad" => $row['ropa_disponibilidad'],
                "talla" => $row['talla']
            ];
        } elseif ($row['id_comida']) {
            $productos[] = [
                "id_productos" => $row['id_productos'],
                "id_comida" => $row['id_comida'],
                "tipo" => $row['tipo'],
                "producto" => $row['producto'],
                "precio" => $row['comida_precio'],
                "disponibilidad" => $row['comida_disponibilidad']
            ];
        }
    }
    echo json_encode($productos);
    }
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

        $id = $_POST['id'] ?? $_GET['id'] ?? null;
        $tipo = $_POST['tipo'] ?? $_GET['tipo'] ?? null;
        $producto = $_POST['producto'] ?? $_GET['producto'] ?? null;
        $precio = $_POST['precio'] ?? $_GET['precio'] ?? null;
        $disponibilidad = $_POST['disponibilidad'] ?? $_GET['disponibilidad'] ?? null;
        $talla = $_POST['talla'] ?? $_GET['talla'] ?? null;
        
        $sql = "UPDATE productos SET tipo=:tipo, precio=:precio, disponibilidad=:disponibilidad WHERE id_productos=:id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':tipo' => $tipo, ':precio' => $precio, ':disponibilidad' => $disponibilidad, ':id' => $id_productos]);

        if ($tipo == "ropa") {
        $sql = "UPDATE ropa SET prenda=:prenda, talla=:talla, precio=:precio, disponibilidad=:disponibilidad WHERE id_ropa=:id_ropa";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['prenda' => $producto, 'talla' => $talla, 'id_ropa' => $id_productos, 'precio' => $precio, 'disponibilidad' => $disponibilidad]);
        } elseif ($tipo == "comida") {
        $sql = "UPDATE comida SET producto=:producto, precio=:precio, disponibilidad=:disponibilidad WHERE id_comida=:id_comida";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['producto' => $producto, 'id_comida' => $id_productos, 'precio' => $precio, 'disponibilidad' => $disponibilidad]);
        }
        
        if ($stmt->rowCount() == 0) {
            throw new Exception("No se encontró el producto");
        }
        
        echo json_encode(["message" => "Producto actualizado"]);
    }

    if ($action == "delete") {
        if (!isset($_POST['id'])) {
            throw new Exception("ID no proporcionado");
        }
        
        $id = $_POST['id'] ?? $_GET['id'] ?? null;
        $tipo = $_POST['tipo'] ?? $_GET['tipo'] ?? null;
        
        $sql = "DELETE FROM productos WHERE id_productos=:id_productos";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':id_productos' => $id_productos]);
        
        if ($tipo == "ropa") {
        $sql = "DELETE FROM ropa WHERE id_ropa=:id_ropa";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['id_ropa' => $id_productos]);
        } elseif ($tipo == "comida") {
        $sql = "DELETE FROM comida WHERE id_comida=:id_comida";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['id_comida' => $id_productos]);
        }
        
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
