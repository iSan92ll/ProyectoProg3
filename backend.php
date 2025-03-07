<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

$host = "localhost";
$user = "root";
$password = "";
$database = "tienda_db";

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(["error" => "Error de conexión: " . $conn->connect_error]));
}

$action = $_GET['action'] ?? '';

if ($action == "read") {
    $result = $conn->query("SELECT * FROM productos");
    echo json_encode($result->fetch_all(MYSQLI_ASSOC));
} elseif ($action == "create") {
    $stmt = $conn->prepare("INSERT INTO productos (producto, precio, disponibilidad) VALUES (?, ?, ?)");
    $stmt->bind_param("sdi", $_POST['producto'], $_POST['precio'], $_POST['disponibilidad']);
    $stmt->execute();
    echo json_encode(["message" => "Producto agregado"]);
} elseif ($action == "update") {
    $stmt = $conn->prepare("UPDATE productos SET producto=?, precio=?, disponibilidad=? WHERE id=?");
    $stmt->bind_param("sdii", $_POST['producto'], $_POST['precio'], $_POST['disponibilidad'], $_POST['id']);
    $stmt->execute();
    echo json_encode(["message" => "Producto actualizado"]);
} elseif ($action == "delete") {
    $stmt = $conn->prepare("DELETE FROM productos WHERE id=?");
    $stmt->bind_param("i", $_POST['id']);
    $stmt->execute();
    echo json_encode(["message" => "Producto eliminado"]);
}

$conn->close();
?>
