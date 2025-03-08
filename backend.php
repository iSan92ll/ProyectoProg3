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
    $producto = $_POST['producto'];
    $precio = $_POST['precio'];
    $disponibilidad = $_POST['disponibilidad'];

    $query = "INSERT INTO productos (producto, precio, disponibilidad) VALUES ('$producto', $precio, $disponibilidad)";
    pg_query($conn, $query);
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
