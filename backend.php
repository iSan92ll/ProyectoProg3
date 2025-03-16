<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Configuración de conexión a PostgreSQL (ajusta estos valores)
$host     = "dpg-cv5nejjqf0us73epn15g-a.oregon-postgres.render.com";           // o la dirección de tu host en Render
$dbname   = "tienda_db_31ib";
$dbuser   = "tienda_db_31ib_user";
$dbpassword = "FnGynAoGsAX729pDUasq2pRgjdAsAwyQ";

try {
    $dsn = "pgsql:host=$host;dbname=$dbname";
    $pdo = new PDO($dsn, $dbuser, $dbpassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(["success" => false, "message" => "Error de conexión: " . $e->getMessage()]);
    exit;
}

// Determinar la acción
$action = isset($_GET["action"]) ? $_GET["action"] : "";

switch ($action) {

    // ----------------------------------------------------------------
    // 1. Leer todos los productos (incluyendo ropa, comida y tecnología)
    // ----------------------------------------------------------------
    case "read":
        try {
            $sql = "(
                SELECT p.id_productos as id, 'comida' as tipo, c.producto, p.precio, p.disponibilidad, NULL as talla
                FROM productos p JOIN comida c ON p.id_productos = c.id_comida
            ) UNION ALL (
                SELECT p.id_productos as id, 'ropa' as tipo, r.prenda, p.precio, p.disponibilidad, r.talla
                FROM productos p JOIN ropa r ON p.id_productos = r.id_ropa
            ) UNION ALL (
                SELECT p.id_productos as id, 'tecnologia' as tipo, t.producto, p.precio, p.disponibilidad, NULL as talla
                FROM productos p JOIN tecnologia t ON p.id_productos = t.id_tecnologia
            ) ORDER BY id";
            $stmt = $pdo->query($sql);
            $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($productos);
        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => "Error al leer productos: " . $e->getMessage()]);
        }
        break;

    // ----------------------------------------------------------------
    // 2. Crear un nuevo producto (acción para Admin)
    // ----------------------------------------------------------------
    case "create":
        // Se esperan los siguientes parámetros via POST: tipo, producto, precio, disponibilidad y opcionalmente talla (para ropa)
        $tipo           = $_POST["tipo"] ?? "";
        $producto       = $_POST["producto"] ?? "";
        $precio         = $_POST["precio"] ?? 0;
        $disponibilidad = $_POST["disponibilidad"] ?? 0;
        $talla          = $_POST["talla"] ?? "";

        if (!$tipo || !$producto || !$precio || !$disponibilidad) {
            echo json_encode(["success" => false, "message" => "Faltan datos obligatorios."]);
            exit;
        }
        try {
            $pdo->beginTransaction();
            // Insertar en la tabla central de productos
            $stmt = $pdo->prepare("INSERT INTO productos (tipo, precio, disponibilidad) VALUES (:tipo, :precio, :disponibilidad) RETURNING id_productos");
            $stmt->execute([
                ":tipo" => $tipo,
                ":precio" => $precio,
                ":disponibilidad" => $disponibilidad
            ]);
            $id_producto = $stmt->fetchColumn();

            // Insertar en la tabla correspondiente según el tipo
            if ($tipo == "ropa") {
                $stmt = $pdo->prepare("INSERT INTO ropa (prenda, precio, disponibilidad, talla, id_ropa) VALUES (:producto, :precio, :disponibilidad, :talla, :id_productos)");
                $stmt->execute([
                    ":producto" => $producto,
                    ":precio" => $precio,
                    ":disponibilidad" => $disponibilidad,
                    ":talla" => $talla,
                    ":id_productos" => $id_producto
                ]);
            } elseif ($tipo == "comida") {
                $stmt = $pdo->prepare("INSERT INTO comida (producto, precio, disponibilidad, id_comida) VALUES (:producto, :precio, :disponibilidad, :id_productos)");
                $stmt->execute([
                    ":producto" => $producto,
                    ":precio" => $precio,
                    ":disponibilidad" => $disponibilidad,
                    ":id_productos" => $id_producto
                ]);
            } elseif ($tipo == "tecnologia") {
                $stmt = $pdo->prepare("INSERT INTO tecnologia (producto, precio, disponibilidad, id_tecnologia) VALUES (:producto, :precio, :disponibilidad, :id_productos)");
                $stmt->execute([
                    ":producto" => $producto,
                    ":precio" => $precio,
                    ":disponibilidad" => $disponibilidad,
                    ":id_productos" => $id_producto
                ]);
            }
            $pdo->commit();
            echo json_encode(["success" => true, "message" => "Producto creado exitosamente"]);
        } catch (PDOException $e) {
            $pdo->rollBack();
            echo json_encode(["success" => false, "message" => "Error al crear producto: " . $e->getMessage()]);
        }
        break;

    // ----------------------------------------------------------------
    // 3. Actualizar un producto existente
    // ----------------------------------------------------------------
    case "update":
        // Parámetros esperados: id, tipo, producto, precio, disponibilidad y opcionalmente talla
        $id             = $_POST["id"] ?? "";
        $tipo           = $_POST["tipo"] ?? "";
        $producto       = $_POST["producto"] ?? "";
        $precio         = $_POST["precio"] ?? 0;
        $disponibilidad = $_POST["disponibilidad"] ?? 0;
        $talla          = $_POST["talla"] ?? "";

        if (!$id || !$tipo || !$producto || !$precio || !$disponibilidad) {
            echo json_encode(["success" => false, "message" => "Faltan datos obligatorios."]);
            exit;
        }
        try {
            $pdo->beginTransaction();
            // Actualizar tabla central
            $stmt = $pdo->prepare("UPDATE productos SET precio = :precio, disponibilidad = :disponibilidad WHERE id_productos = :id");
            $stmt->execute([
                ":precio" => $precio,
                ":disponibilidad" => $disponibilidad,
                ":id" => $id
            ]);

            // Actualizar tabla específica según el tipo
            if ($tipo == "ropa") {
                $stmt = $pdo->prepare("UPDATE ropa SET prenda = :producto, talla = :talla WHERE id_ropa = :id");
                $stmt->execute([
                    ":producto" => $producto,
                    ":talla" => $talla,
                    ":id" => $id
                ]);
            } elseif ($tipo == "comida") {
                $stmt = $pdo->prepare("UPDATE comida SET producto = :producto WHERE id_comida = :id");
                $stmt->execute([
                    ":producto" => $producto,
                    ":id" => $id
                ]);
            } elseif ($tipo == "tecnologia") {
                $stmt = $pdo->prepare("UPDATE tecnologia SET producto = :producto WHERE id_tecnologia = :id");
                $stmt->execute([
                    ":producto" => $producto,
                    ":id" => $id
                ]);
            }
            $pdo->commit();
            echo json_encode(["success" => true, "message" => "Producto actualizado exitosamente"]);
        } catch (PDOException $e) {
            $pdo->rollBack();
            echo json_encode(["success" => false, "message" => "Error al actualizar producto: " . $e->getMessage()]);
        }
        break;

    // ----------------------------------------------------------------
    // 4. Eliminar un producto
    // ----------------------------------------------------------------
    case "delete":
        // Parámetros esperados: id y tipo
        $id   = $_POST["id"] ?? "";
        $tipo = $_POST["tipo"] ?? "";
        if (!$id || !$tipo) {
            echo json_encode(["success" => false, "message" => "Datos insuficientes"]);
            exit;
        }
        try {
            $pdo->beginTransaction();
            // Eliminar de la tabla específica primero
            if ($tipo == "ropa") {
                $stmt = $pdo->prepare("DELETE FROM ropa WHERE id_ropa = :id");
                $stmt->execute([":id" => $id]);
            } elseif ($tipo == "comida") {
                $stmt = $pdo->prepare("DELETE FROM comida WHERE id_comida = :id");
                $stmt->execute([":id" => $id]);
            } elseif ($tipo == "tecnologia") {
                $stmt = $pdo->prepare("DELETE FROM tecnologia WHERE id_tecnologia = :id");
                $stmt->execute([":id" => $id]);
            }
            // Eliminar de la tabla central
            $stmt = $pdo->prepare("DELETE FROM productos WHERE id_productos = :id");
            $stmt->execute([":id" => $id]);
            $pdo->commit();
            echo json_encode(["success" => true, "message" => "Producto eliminado exitosamente"]);
        } catch (PDOException $e) {
            $pdo->rollBack();
            echo json_encode(["success" => false, "message" => "Error al eliminar producto: " . $e->getMessage()]);
        }
        break;

    // ----------------------------------------------------------------
    // 5. Login de usuario
    // ----------------------------------------------------------------
    case "login":
        // Se esperan: username y password via POST
        $username = $_POST["username"] ?? "";
        $password = $_POST["password"] ?? "";
        if (!$username || !$password) {
            echo json_encode(["success" => false, "message" => "Datos insuficientes"]);
            exit;
        }
        try {
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE username = :username");
            $stmt->execute([":username" => $username]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($usuario) {
                // Para mayor seguridad se recomienda almacenar contraseñas encriptadas y usar password_verify
                if ($password === $usuario["password"]) {
                    echo json_encode([
                        "success"   => true,
                        "id_usuario"=> $usuario["id_usuario"],
                        "username"  => $usuario["username"],
                        "rol"       => $usuario["rol"]
                    ]);
                } else {
                    echo json_encode(["success" => false, "message" => "Contraseña incorrecta"]);
                }
            } else {
                echo json_encode(["success" => false, "message" => "Usuario no encontrado"]);
            }
        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => "Error en login: " . $e->getMessage()]);
        }
        break;

    // ----------------------------------------------------------------
    // 6. Registro de usuario
    // ----------------------------------------------------------------
    case "register":
        // Se esperan: username y password via POST
        $username = $_POST["username"] ?? "";
        $password = $_POST["password"] ?? "";
        if (!$username || !$password) {
            echo json_encode(["success" => false, "message" => "Datos insuficientes"]);
            exit;
        }
        try {
            // Verificar si el usuario ya existe
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE username = :username");
            $stmt->execute([":username" => $username]);
            if ($stmt->fetch(PDO::FETCH_ASSOC)) {
                echo json_encode(["success" => false, "message" => "El usuario ya existe"]);
                exit;
            }
            // Se recomienda encriptar la contraseña con password_hash
            $stmt = $pdo->prepare("INSERT INTO usuarios (username, password, rol) VALUES (:username, :password, 'usuario')");
            $stmt->execute([
                ":username" => $username,
                ":password" => $password
            ]);
            echo json_encode(["success" => true, "message" => "Usuario registrado exitosamente"]);
        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => "Error en registro: " . $e->getMessage()]);
        }
        break;

    // ----------------------------------------------------------------
    // 7. Agregar producto al carrito
    // ----------------------------------------------------------------
    case "addToCart":
        // Se esperan: id_usuario, id_producto y cantidad via POST
        $id_usuario  = $_POST["id_usuario"] ?? "";
        $id_producto = $_POST["id_producto"] ?? "";
        $cantidad    = $_POST["cantidad"] ?? 1;
        if (!$id_usuario || !$id_producto) {
            echo json_encode(["success" => false, "message" => "Datos insuficientes"]);
            exit;
        }
        try {
            // Verificar si el producto ya existe en el carrito del usuario
            $stmt = $pdo->prepare("SELECT * FROM carrito WHERE id_usuario = :id_usuario AND id_productos = :id_producto");
            $stmt->execute([
                ":id_usuario" => $id_usuario,
                ":id_producto" => $id_producto
            ]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($item) {
                // Si existe, actualizamos la cantidad
                $newCantidad = $item["cantidad"] + $cantidad;
                $stmt = $pdo->prepare("UPDATE carrito SET cantidad = :cantidad WHERE id_carrito = :id_carrito");
                $stmt->execute([
                    ":cantidad" => $newCantidad,
                    ":id_carrito" => $item["id_carrito"]
                ]);
            } else {
                // Si no, insertamos un nuevo registro
                $stmt = $pdo->prepare("INSERT INTO carrito (id_usuario, id_productos, cantidad) VALUES (:id_usuario, :id_producto, :cantidad)");
                $stmt->execute([
                    ":id_usuario" => $id_usuario,
                    ":id_producto" => $id_producto,
                    ":cantidad" => $cantidad
                ]);
            }
            echo json_encode(["success" => true, "message" => "Producto agregado al carrito"]);
        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => "Error al agregar al carrito: " . $e->getMessage()]);
        }
        break;

    // ----------------------------------------------------------------
    // 8. Obtener items del carrito de un usuario
    // ----------------------------------------------------------------
    case "getCart":
        // Se espera id_usuario vía GET
        $id_usuario = $_GET["id_usuario"] ?? "";
        if (!$id_usuario) {
            echo json_encode(["success" => false, "message" => "Datos insuficientes"]);
            exit;
        }
        try {
            $sql = "SELECT carrito.id_carrito, carrito.id_productos, carrito.cantidad, p.precio,
                       COALESCE(r.prenda, c.producto, t.producto) AS producto
                    FROM carrito
                    JOIN productos p ON carrito.id_productos = p.id_productos
                    LEFT JOIN ropa r ON p.id_productos = r.id_ropa
                    LEFT JOIN comida c ON p.id_productos = c.id_comida
                    LEFT JOIN tecnologia t ON p.id_productos = t.id_tecnologia
                    WHERE carrito.id_usuario = :id_usuario";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([":id_usuario" => $id_usuario]);
            $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($cartItems);
        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => "Error al obtener el carrito: " . $e->getMessage()]);
        }
        break;

    // ----------------------------------------------------------------
    // 9. Eliminar un item del carrito
    // ----------------------------------------------------------------
    case "removeFromCart":
        // Se espera id_carrito vía POST
        $id_carrito = $_POST["id_carrito"] ?? "";
        if (!$id_carrito) {
            echo json_encode(["success" => false, "message" => "Datos insuficientes"]);
            exit;
        }
        try {
            $stmt = $pdo->prepare("DELETE FROM carrito WHERE id_carrito = :id_carrito");
            $stmt->execute([":id_carrito" => $id_carrito]);
            echo json_encode(["success" => true, "message" => "Item eliminado del carrito"]);
        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => "Error al eliminar del carrito: " . $e->getMessage()]);
        }
        break;

    // ----------------------------------------------------------------
    // 10. Vaciar el carrito de un usuario
    // ----------------------------------------------------------------
    case "clearCart":
        // Se espera id_usuario vía POST
        $id_usuario = $_POST["id_usuario"] ?? "";
        if (!$id_usuario) {
            echo json_encode(["success" => false, "message" => "Datos insuficientes"]);
            exit;
        }
        try {
            $stmt = $pdo->prepare("DELETE FROM carrito WHERE id_usuario = :id_usuario");
            $stmt->execute([":id_usuario" => $id_usuario]);
            echo json_encode(["success" => true, "message" => "Carrito vaciado"]);
        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => "Error al vaciar el carrito: " . $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(["success" => false, "message" => "Acción no reconocida"]);
        break;
}
?>
