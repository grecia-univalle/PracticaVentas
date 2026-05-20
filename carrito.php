<?php

session_start();
include("conexion.php"); // Conexión a tu base de datos local

error_reporting(E_ALL);
ini_set('display_errors', 1);

/*
|--------------------------------------------------------------------------
| CONTROL DE SESIÓN AUTOMÁTICA / INVITADO
|--------------------------------------------------------------------------
*/
// Si existe sesión usa el ID real, si no existe, usa el ID 2 temporalmente para que puedas testear sin ir al login
$id_usuario = isset($_SESSION['id_usuario']) ? intval($_SESSION['id_usuario']) : 2; 

/*
|--------------------------------------------------------------------------
| CARRITO
|--------------------------------------------------------------------------
*/

if(!isset($_SESSION['carrito'])){
    $_SESSION['carrito'] = [];
}

/*
|--------------------------------------------------------------------------
| HISTORIAL (Mantenido por compatibilidad)
|--------------------------------------------------------------------------
*/

if(!isset($_SESSION['historial'])){
    $_SESSION['historial'] = [];
}

$carrito = $_SESSION['carrito'];
$error_stock = ""; // Variable para almacenar los mensajes de error si se supera el stock

/*
|--------------------------------------------------------------------------
| ACTUALIZAR CARRITO
|--------------------------------------------------------------------------
*/

if(isset($_POST['update_cart'])){

    foreach($_POST['cantidades'] as $id => $cantidad){

        $id = intval($id);
        $cantidad = intval($cantidad);

        if($cantidad <= 0){

            unset($_SESSION['carrito'][$id]);

        }else{

            $_SESSION['carrito'][$id] = $cantidad;
        }
    }

    header("Location: carrito.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| ELIMINAR PRODUCTO
|--------------------------------------------------------------------------
*/

if(isset($_GET['remove'])){

    $id = intval($_GET['remove']);

    unset($_SESSION['carrito'][$id]);

    header("Location: carrito.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| FINALIZAR COMPRA (CON VALIDACIÓN Y REDUCCIÓN DE STOCK)
|--------------------------------------------------------------------------
*/

if(isset($_POST['comprar']) && !empty($_SESSION['carrito'])){

    $fecha_actual = date("Y-m-d H:i:s"); // Formato estándar compatible con SQL datetime
    $total_final = 0;
    
    $productos_a_insertar = [];
    $hubo_error_stock = false;

    // 1. Validar el stock y calcular el total real consultando la base de datos primero
    foreach($_SESSION['carrito'] as $id => $cantidad){
        $id = intval($id);
        $cantidad = intval($cantidad);
        
        // Consultamos precio, stock y nombre del producto
        $sql_verificar = "SELECT nombre, precio, stock FROM producto WHERE id_producto = $id";
        $res_verificar = $conn->query($sql_verificar);
        
        if($game = $res_verificar->fetch_assoc()){
            $stock_disponible = intval($game['stock']);
            $nombre_producto = $game['nombre'];

            // VALIDACIÓN: ¿La cantidad solicitada supera el stock actual?
            if($cantidad > $stock_disponible){
                $hubo_error_stock = true;
                $error_stock .= "❌ No puedes comprar $cantidad unidades de '<b>" . htmlspecialchars($nombre_producto) . "</b>'. Solo quedan $stock_disponible en stock.<br>";
            }

            $precio = $game['precio'];
            $subtotal = $precio * $cantidad;
            
            $total_final += $subtotal;
            
            // Guardamos temporalmente para el segundo paso si todo está en orden
            $productos_a_insertar[] = [
                'id_producto' => $id,
                'cantidad' => $cantidad,
                'subtotal' => $subtotal,
                'nuevo_stock' => $stock_disponible - $cantidad // Cálculo del stock remanente
            ];
        }
    }

    // 2. Si no hubo ningún error de stock, procedemos a guardar la venta y restar las existencias
    if(!$hubo_error_stock && $total_final > 0) {
        // Insertar la cabecera en la tabla 'venta'
        $sql_insert_venta = "INSERT INTO venta (id_usuario, fecha, total, estado_venta) 
                             VALUES ($id_usuario, '$fecha_actual', $total_final, 'completado')";
        
        if($conn->query($sql_insert_venta)){
            // Obtenemos el ID de la venta que se acaba de generar
            $id_venta_generada = $conn->insert_id;
            
            // 3. Insertar cada artículo en la tabla 'detalle_venta' y actualizar inventario
            foreach($productos_a_insertar as $prod){
                $id_p = $prod['id_producto'];
                $cant = $prod['cantidad'];
                $subt = $prod['subtotal'];
                $nuevo_stock = $prod['nuevo_stock'];
                
                // Inserción de detalles
                $sql_detalle = "INSERT INTO detalle_venta (id_venta, id_producto, cantidad, subtotal) 
                                VALUES ($id_venta_generada, $id_p, $cant, $subt)";
                $conn->query($sql_detalle);

                // REDUCCIÓN DEL STOCK EN LA TABLA PRODUCTOS
                $sql_update_stock = "UPDATE producto SET stock = $nuevo_stock WHERE id_producto = $id_p";
                $conn->query($sql_update_stock);
            }
            
            // Limpiamos el carrito de la sesión tras el éxito
            $_SESSION['carrito'] = [];
            
            header("Location: historial.php");
            exit;
        } else {
            die("<div class='p-5 bg-red-600 text-white rounded-xl'>Error al registrar la venta: " . $conn->error . "<br>Por favor, asegúrate de que el usuario con ID " . $id_usuario . " exista en la tabla 'usuario'.</div>");
        }
    }
}

/*
|--------------------------------------------------------------------------
| TOTALES
|--------------------------------------------------------------------------
*/

$totalGeneral = 0;
$totalProductos = 0;

?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<meta
name="viewport"
content="width=device-width, initial-scale=1.0"
>

<title>Carrito Gamer</title>

<script src="https://cdn.tailwindcss.com"></script>

<link
rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
/>

<style>

body{
    background:#050816;
    color:white;
    font-family:Arial;
}

</style>

</head>

<body>

<div class="min-h-screen p-8">

<?php if(!empty($error_stock)){ ?>
    <div class="mb-8 p-5 bg-red-950/80 border-2 border-red-500 text-red-200 rounded-[20px] flex items-start gap-4 shadow-lg shadow-red-950/50">
        <i class="fa-solid fa-triangle-exclamation text-3xl text-red-500 mt-1"></i>
        <div>
            <h3 class="text-xl font-bold text-white mb-1">¡Límite de Stock Superado!</h3>
            <p class="text-gray-300"><?php echo $error_stock; ?></p>
        </div>
    </div>
<?php } ?>

<div class="flex justify-between items-center mb-10">

    <div>

        <h1 class="text-5xl font-black flex items-center gap-4">

            <i class="fa-solid fa-cart-shopping text-purple-500"></i>

            Carrito

        </h1>

        <p class="text-gray-400 mt-2">
            Tus videojuegos agregados
        </p>

    </div>

    <div class="flex gap-4">

        <a
        href="historial.php"
        class="bg-purple-600 hover:bg-purple-700 px-6 py-3 rounded-2xl transition-all"
        >
            Historial
        </a>

        <a
        href="usuario.php"
        class="bg-[#131c35] hover:bg-purple-600 px-6 py-3 rounded-2xl transition-all"
        >
            Volver
        </a>

    </div>

</div>

<form method="POST">

<div class="bg-[#0b1020] rounded-[30px] overflow-hidden border border-purple-900/20">

    <div class="grid grid-cols-5 gap-5 p-5 border-b border-purple-900/20 font-bold text-gray-400">

        <div>Juego</div>
        <div>Precio</div>
        <div>Cantidad</div>
        <div>Total</div>
        <div>Eliminar</div>

    </div>

    <?php

    if(empty($carrito)){

        echo "

        <div class='p-20 text-center'>

            <i class='fa-solid fa-cart-shopping text-7xl text-purple-500'></i>

            <h2 class='text-3xl font-bold mt-5'>
                Tu carrito está vacío
            </h2>

        </div>

        ";

    }else{

        foreach($carrito as $id => $cantidad){

            $id = intval($id);

            $sql_producto = "SELECT * FROM producto WHERE id_producto = $id";
            $res = $conn->query($sql_producto);

            if(!$res){
                die("<div class='p-5 bg-red-600 text-white rounded-xl m-5'>Error en la base de datos: " . $conn->error . "</div>");
            }

            if(!$game = $res->fetch_assoc()){
                continue; 
            }

            $array_imgs = explode(",", $game['imagen']);
            $imagen_mostrar = !empty($array_imgs[0]) ? $array_imgs[0] : 'https://via.placeholder.com/300x260';

            $precio = $game['precio'];
            $total = $precio * $cantidad;

            $totalGeneral += $total;
            $totalProductos += $cantidad;

    ?>

    <div class="grid grid-cols-5 gap-5 items-center p-5 border-b border-purple-900/10">

        <div class="flex items-center gap-4">

            <img
                src="<?php echo $imagen_mostrar; ?>"
                class="w-24 h-24 rounded-2xl object-cover"
            >

            <div>

                <h2 class="font-bold text-xl">

                    <?php echo htmlspecialchars($game['nombre']); ?>

                </h2>

                <p class="text-gray-400 text-sm">

                    🎮 <?php echo htmlspecialchars($game['categoria'] ?? ($game['id_categoria'] ?? 'General')); ?>

                </p>

            </div>

        </div>

        <div class="font-bold text-green-400 text-xl">

            Bs. <?php echo number_format($precio, 2); ?>

        </div>

        <div>

            <input
                type="number"
                min="0"
                name="cantidades[<?php echo $id; ?>]"
                value="<?php echo $cantidad; ?>"
                class="w-24 bg-[#131c35] text-center py-3 rounded-2xl text-xl font-bold outline-none border border-purple-500"
            >

        </div>

        <div class="font-black text-2xl">

            Bs. <?php echo number_format($total, 2); ?>

        </div>

        <div>

            <a

            href="?remove=<?php echo $id; ?>"

            class="bg-red-500 px-5 py-3 rounded-2xl hover:bg-red-600 transition-all"
            >

                Eliminar

            </a>

        </div>

    </div>

    <?php

        }

    }

    ?>

</div>

<?php if(!empty($carrito)){ ?>

<div class="flex gap-4 mt-6">

    <button
        type="submit"
        name="update_cart"
        class="bg-purple-600 hover:bg-purple-700 px-8 py-4 rounded-2xl font-bold text-lg transition-all"
    >

        Actualizar carrito

    </button>

</div>

<?php } ?>

</form>

<div class="mt-10 flex justify-end">

    <div class="w-full max-w-[500px] bg-[#0b1020] rounded-[30px] p-8 border border-purple-900/20">

        <h2 class="text-3xl font-black mb-8">

            Resumen del pedido

        </h2>

        <div class="flex justify-between mb-5 text-xl">

            <span class="text-gray-400">
                Total productos
            </span>

            <span class="font-bold">

                <?php echo $totalProductos; ?>

            </span>

        </div>

        <div class="flex justify-between mb-8 text-3xl font-black">

            <span>
                Total general
            </span>

            <span class="text-green-400">

                Bs. <?php echo number_format($totalGeneral, 2); ?>

            </span>

        </div>

        <?php if(!empty($carrito)){ ?>

        <form method="POST">

            <button

            type="submit"

            name="comprar"

            class="w-full py-4 rounded-2xl bg-gradient-to-r from-green-500 to-emerald-600 font-bold text-xl hover:scale-105 transition-all"
            >

                Finalizar Compra

            </button>

        </form>

        <?php } ?>

    </div>

</div>

</div>

</body>
</html>