<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

$host = "localhost";
$user = "root";
$password = "";
$database = "tienda_db";

$conn = new mysqli($host, $user, $password, $database);

$action = isset($_GET['action']) ? $_GET['action'] : ''; 

switch ($action) {
    case 'read':
        $result = $conn->query("SELECT * FROM tienda");
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        echo json_encode($data);
        break;

    case 'create':
        $producto = $_POST['producto'];
        $precio = $_POST['precio'];
        $disponibilidad = $_POST['disponibilidad'];
        $conn->query("INSERT INTO tienda (producto, precio, disponibilidad) VALUES ('$producto', '$precio', '$disponibilidad')");
        break;

    case 'update':
        $id = $_POST['id'];
        $producto = $_POST['producto'];
        $precio = $_POST['precio'];
        $disponibilidad = $_POST['disponibilidad'];
        $conn->query("UPDATE tienda SET producto='$producto', precio='$precio', disponibilidad='$disponibilidad' WHERE id=$id");
        break;

    case 'delete':
        $id = $_POST['id'];
        $conn->query("DELETE FROM tienda WHERE id=$id");
        break;
}

$conn->close();
?>
