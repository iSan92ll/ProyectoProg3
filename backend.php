<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// 🔹 Datos de la base de datos en Render PostgreSQL
$host = "dpg-cv5nejjqf0us73epn15g-a";
$port = "5432";
$user = "tienda_db_31ib_user";
$password = "FnGynAoGsAX729pDUasq2pRgjdAsAwyQ";
$dbname = "tienda_db_31ib";

// 🔹 Conectar a PostgreSQL
$conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");
postgresql://tienda_db_31ib_user:FnGynAoGsAX729pDUasq2pRgjdAsAwyQ@dpg-cv5nejjqf0us73epn15g-a/tienda_db_31ib

if (!$conn) {
    die(json_encode(["error" => "Error de conexión: " . pg_last_error()]));
}

// 🔹 Determinar la acción del CRUD
$action = $_GET['action'] ?? '';

if ($action == "read") {
    $result = pg_query($conn, "SELECT * FROM productos");

    if (!$result) {
        echo json_encode(["error" => "Error en la consulta"]);
        exit;
    }

    $data = pg_fetch_all($result);
    
    // 🔹 Si no hay productos, devolver un array vacío en JSON
    if (!$data) {
        $data = [];
    }

    echo json_encode($data);
    exit;
} elseif ($action == "create") {
    $json = file_get_contents("php://input");
    $data = json_decode($json, true);

    if (!$data) {
        die(json_encode(["error" => "No se recibieron datos válidos"]));
    }

    $producto = $data['producto'] ?? null;
    $precio = $data['precio'] ?? null;
    $disponibilidad = $data['disponibilidad'] ?? null;

    if (!$producto || !$precio || !$disponibilidad) {
        die(json_encode(["error" => "Todos los campos son obligatorios"]));
    }

    $query = "INSERT INTO productos (producto, precio, disponibilidad) VALUES ($1, $2, $3)";
    $stmt = pg_prepare($conn, "insert_producto", $query);
    pg_execute($conn, "insert_producto", [$producto, $precio, $disponibilidad]);

    echo json_encode(["message" => "Producto agregado"]);
} elseif ($action == "update") {
    $id = $_POST['id'];
    $producto = $_POST['producto'];
    $precio = $_POST['precio'];
    $disponibilidad = $_POST['disponibilidad'];

    $query = "UPDATE productos SET producto='$producto', precio=$precio, disponibilidad=$disponibilidad WHERE id=$id";
    pg_query($conn, $query);
    echo json_encode(["message" => "Producto actualizado"]);
} elseif ($action == "delete") {
    $id = $_POST['id'];
    $query = "DELETE FROM productos WHERE id=$id";
    pg_query($conn, $query);
    echo json_encode(["message" => "Producto eliminado"]);
}

pg_close($conn);
?>
