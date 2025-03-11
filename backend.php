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
    die(json_encode(["error" => "Conexión fallida: " . $e->getMessage()]));
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action == "read") {
    $sql = "SELECT p.id_productos,
                   r.id_ropa, r.prenda, r.talla, r.precio, r.disponibilidad,
                   c.id_comida, c.producto, c.precio, c.disponibilidad
            FROM productos p
            LEFT JOIN ropa r ON p.id_productos = r.id_ropa
            LEFT JOIN comida c ON p.id_productos = c.id_comida";
    $result = $conn->query($sql);
    
    $productos = [];
    while ($row = $result->fetch_assoc()) {
        if ($row['id_ropa']) {
            $productos[] = [
                "id_productos" => $row['id_productos'],
                "id_ropa" => $row['id_ropa'],
                "producto" => $row['prenda'],
                "precio" => $row['precio'],
                "disponibilidad" => $row['disponibilidad'],
                "talla" => $row['talla']
            ];
        } elseif ($row['id_comida']) {
            $productos[] = [
                "id_productos" => $row['id_productos'],
                "id_comida" => $row['id_comida'],
                "producto" => $row['producto'],
                "precio" => $row['precio'],
                "disponibilidad" => $row['disponibilidad']
            ];
        }
    }
    
    echo json_encode($productos);
}

if ($action == "create") {
    $tipo = $_POST['tipo'];
    $nombre = $_POST['nombre'];
    $precio = $_POST['precio'];
    $disponibilidad = $_POST['disponibilidad'];
    $talla = isset($_POST['talla']) ? $_POST['talla'] : null;

    $sql = "INSERT INTO productos (precio, disponibilidad) VALUES ('$precio', '$disponibilidad')";
    if ($conn->query($sql)) {
        $id_productos = $conn->insert_id;

        if ($tipo == "ropa") {
            $sql = "INSERT INTO ropa (id_ropa, prenda, talla) VALUES ('$id_productos', '$nombre', '$talla')";
        } elseif ($tipo == "comida") {
            $sql = "INSERT INTO comida (id_comida, producto) VALUES ('$id_productos', '$nombre')";
        }

        if ($conn->query($sql)) {
            echo json_encode(["message" => "Producto agregado"]);
        } else {
            echo json_encode(["error" => "Error al insertar en ropa/comida: " . $conn->error]);
        }
    } else {
        echo json_encode(["error" => "Error al insertar en productos: " . $conn->error]);
    }
}

if ($action == "update") {
    $id_productos = $_POST['id_productos'];
    $tipo = $_POST['tipo'];
    $nombre = $_POST['nombre'];
    $precio = $_POST['precio'];
    $disponibilidad = $_POST['disponibilidad'];
    $talla = isset($_POST['talla']) ? $_POST['talla'] : null;

    $sql = "UPDATE productos SET precio='$precio', disponibilidad='$disponibilidad' WHERE id_productos='$id_productos'";
    
    if ($conn->query($sql)) {
        if ($tipo == "ropa") {
            $sql = "UPDATE ropa SET prenda='$nombre', talla='$talla' WHERE id_ropa='$id_productos'";
        } elseif ($tipo == "comida") {
            $sql = "UPDATE comida SET producto='$nombre' WHERE id_comida='$id_productos'";
        }

        if ($conn->query($sql)) {
            echo json_encode(["message" => "Producto actualizado"]);
        } else {
            echo json_encode(["error" => "Error al actualizar ropa/comida: " . $conn->error]);
        }
    } else {
        echo json_encode(["error" => "Error al actualizar productos: " . $conn->error]);
    }
}

if ($action == "delete") {
    $id_productos = $_POST['id_productos'];
    $tipo = $_POST['tipo'];

    if ($tipo == "ropa") {
        $sql = "DELETE FROM ropa WHERE id_ropa='$id_productos'";
    } elseif ($tipo == "comida") {
        $sql = "DELETE FROM comida WHERE id_comida='$id_productos'";
    }

    if ($conn->query($sql)) {
        $sql = "DELETE FROM productos WHERE id_productos='$id_productos'";
        if ($conn->query($sql)) {
            echo json_encode(["message" => "Producto eliminado"]);
        } else {
            echo json_encode(["error" => "Error al eliminar de productos: " . $conn->error]);
        }
    } else {
        echo json_encode(["error" => "Error al eliminar de ropa/comida: " . $conn->error]);
    }
}

$conn = null;
?>
