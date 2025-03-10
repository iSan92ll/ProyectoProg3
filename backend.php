<?php
header("Content-Type: application/json; charset=UTF-8");// Allow from any origin
if (isset($_SERVER['HTTP_ORIGIN'])) {
    // Decide if the origin in $_SERVER['HTTP_ORIGIN'] is one
    // you want to allow, and if so:
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');    // cache for 1 day
}

// Access-Control headers are received during OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
        // may also be using PUT, PATCH, HEAD etc
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

    exit(0);
}


$servername = "dpg-cv5nejjqf0us73epn15g-a";
$username = "tienda_db_31ib_user";
$password = "FnGynAoGsAX729pDUasq2pRgjdAsAwyQ";
$dbname = "tienda_db_31ib";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(["error" => "Conexión fallida: " . $conn->connect_error]));
}

$action = $_GET['action'] ?? '';

if ($action === "read") {
    $sql = "SELECT p.id_productos, p.precio, p.disponibilidad,
                   r.id_ropa, r.prenda, r.talla,
                   c.id_comida, c.producto
            FROM productos p
            LEFT JOIN ropa r ON p.id_productos = r.id_ropa
            LEFT JOIN comida c ON p.id_productos = c.id_comida";

    $result = $conn->query($sql);
    $productos = [];

    while ($row = $result->fetch_assoc()) {
        if ($row['id_ropa']) {
            $productos[] = [
                "id_productos" => $row['id_productos'],
                "tipo" => "ropa",
                "producto" => $row['prenda'],
                "precio" => $row['precio'],
                "disponibilidad" => $row['disponibilidad'],
                "talla" => $row['talla']
            ];
        } elseif ($row['id_comida']) {
            $productos[] = [
                "id_productos" => $row['id_productos'],
                "tipo" => "comida",
                "producto" => $row['producto'],
                "precio" => $row['precio'],
                "disponibilidad" => $row['disponibilidad']
            ];
        }
    }

    echo json_encode($productos);
}

if ($action === "create") {
    $tipo = $_POST['tipo'];
    $nombre = $_POST['producto'];
    $precio = $_POST['precio'];
    $disponibilidad = $_POST['disponibilidad'];
    $talla = $_POST['talla'] ?? null;

    $sql = "INSERT INTO productos (precio, disponibilidad) VALUES ('$precio', '$disponibilidad')";
    if ($conn->query($sql)) {
        $id_productos = $conn->insert_id;

        if ($tipo === "ropa") {
            $sql = "INSERT INTO ropa (id_ropa, prenda, talla) VALUES ('$id_productos', '$nombre', '$talla')";
        } elseif ($tipo === "comida") {
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

$conn->close();
?>
