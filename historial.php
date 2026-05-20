<?php
session_start();
include("conexion.php"); // Conexión a tu base de datos local

error_reporting(E_ALL);
ini_set('display_errors', 1);

/*
|--------------------------------------------------------------------------
| CONTROL DE SESIÓN AUTOMÁTICA
|--------------------------------------------------------------------------
*/
$id_usuario = isset($_SESSION['id_usuario']) ? intval($_SESSION['id_usuario']) : 2; 

/*
|--------------------------------------------------------------------------
| PROCESAR FILTROS Y BÚSQUEDA
|--------------------------------------------------------------------------
*/
$buscar_nombre = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$orden = isset($_GET['order']) ? $_GET['order'] : 'recientes';

// Definir el orden en SQL
$sql_order = "ORDER BY v.fecha DESC"; // Por defecto: más recientes
if ($orden === 'antiguos') {
    $sql_order = "ORDER BY v.fecha ASC";
}

/*
|--------------------------------------------------------------------------
| CONSULTA PRINCIPAL
|--------------------------------------------------------------------------
| Une las tablas venta, detalle_venta y producto para traer el historial
| filtrado por el usuario actual y la búsqueda si existe.
*/
$sql_historial = "
    SELECT 
        v.id_venta,
        v.fecha,
        v.total,
        v.estado_venta,
        dv.cantidad,
        dv.subtotal,
        p.nombre AS juego_nombre,
        p.imagen AS juego_imagen,
        p.precio AS juego_precio
    FROM venta v
    INNER JOIN detalle_venta dv ON v.id_venta = dv.id_venta
    INNER JOIN producto p ON dv.id_producto = p.id_producto
    WHERE v.id_usuario = $id_usuario
";

if (!empty($buscar_nombre)) {
    $sql_historial .= " AND p.nombre LIKE '%$buscar_nombre%' ";
}

$sql_historial .= " $sql_order";

$res_historial = $conn->query($sql_historial);

if (!$res_historial) {
    die("<div class='p-5 bg-red-600 text-white rounded-xl m-5'>Error en la base de datos: " . $conn->error . "</div>");
}

// Agrupar los productos por cada venta para mostrarlos ordenados en la interfaz
$ventas = [];
while ($row = $res_historial->fetch_assoc()) {
    $id_v = $row['id_venta'];
    if (!isset($ventas[$id_v])) {
        $ventas[$id_v] = [
            'fecha' => $row['fecha'],
            'total' => $row['total'],
            'estado' => $row['estado_venta'],
            'items' => []
        ];
    }
    $ventas[$id_v]['items'][] = $row;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Compras</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <style>
        body {
            background: #050816;
            color: white;
            font-family: Arial, sans-serif;
        }
    </style>
</head>
<body>

<div class="min-h-screen p-8">

    <div class="flex justify-between items-center mb-10">
        <div>
            <h1 class="text-5xl font-black flex items-center gap-4">
                <i class="fa-solid fa-history text-purple-500"></i>
                Historial
            </h1>
            <p class="text-gray-400 mt-2">Tus compras registradas en la tienda</p>
        </div>
        <div>
            <a href="carrito.php" class="bg-[#131c35] hover:bg-purple-600 px-6 py-3 rounded-2xl transition-all">
                Volver al carrito
            </a>
        </div>
    </div>

    <form method="GET" class="flex flex-col md:flex-row gap-4 mb-10">
        <div class="relative flex-1">
            <i class="fa-solid fa-magnifying-glass absolute left-5 top-1/2 -translate-y-1/2 text-gray-400"></i>
            <input 
                type="text" 
                name="search" 
                value="<?php echo htmlspecialchars($buscar_nombre); ?>"
                placeholder="Buscar por nombre de videojuego..." 
                class="w-full bg-[#0b1020] border border-purple-900/20 rounded-2xl py-4 pl-14 pr-5 outline-none focus:border-purple-500 text-white placeholder-gray-500"
            >
        </div>
        
        <div class="flex gap-4">
            <select 
                name="order" 
                class="bg-[#0b1020] border border-purple-900/20 rounded-2xl px-5 py-4 outline-none focus:border-purple-500 text-white cursor-pointer"
            >
                <option value="recientes" <?php echo $orden === 'recientes' ? 'selected' : ''; ?>>📅 Más recientes</option>
                <option value="antiguos" <?php echo $orden === 'antiguos' ? 'selected' : ''; ?>>📅 Más antiguos</option>
            </select>

            <button type="submit" class="bg-purple-600 hover:bg-purple-700 font-bold px-8 py-4 rounded-2xl transition-all">
                Buscar
            </button>
        </div>
    </form>

    <div class="space-y-8">
        <?php if (empty($ventas)): ?>
            <div class="bg-[#0b1020] rounded-[30px] p-20 border border-purple-900/20 text-center">
                <i class="fa-solid fa-database text-7xl text-purple-500 mb-5 block"></i>
                <h2 class="text-2xl text-gray-400 font-medium">
                    No se encontraron compras registradas en el historial.
                </h2>
            </div>
        <?php else: ?>
            <?php foreach ($ventas as $id_v => $datos_venta): ?>
                <div class="bg-[#0b1020] rounded-[30px] overflow-hidden border border-purple-900/20 p-6">
                    
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center pb-4 mb-4 border-b border-purple-900/20 gap-2">
                        <div>
                            <span class="text-xs font-bold uppercase tracking-wider bg-purple-900/40 text-purple-400 px-3 py-1 rounded-full">
                                ID Venta: #<?php echo $id_v; ?>
                            </span>
                            <span class="text-gray-400 text-sm ml-2">
                                📅 <?php echo date("d/m/Y H:i", strtotime($datos_venta['fecha'])); ?>
                            </span>
                        </div>
                        <div class="text-right">
                            <span class="text-gray-400 text-sm block">Total de la compra</span>
                            <span class="text-green-400 font-black text-2xl">Bs. <?php echo number_format($datos_venta['total'], 2); ?></span>
                        </div>
                    </div>

                    <div class="divide-y divide-purple-900/10">
                        <?php foreach ($datos_venta['items'] as $item): 
                            $array_imgs = explode(",", $item['juego_imagen']);
                            $imagen_mostrar = !empty($array_imgs[0]) ? $array_imgs[0] : 'https://via.placeholder.com/300x260';
                        ?>
                            <div class="flex flex-col sm:flex-row justify-between items-center py-4 gap-4">
                                <div class="flex items-center gap-4 w-full sm:w-auto">
                                    <img src="<?php echo $imagen_mostrar; ?>" class="w-16 h-16 rounded-xl object-cover flex-shrink-0">
                                    <div>
                                        <h3 class="font-bold text-lg text-white"><?php echo htmlspecialchars($item['juego_nombre']); ?></h3>
                                        <p class="text-sm text-gray-400">Precio Unitario: Bs. <?php echo number_format($item['juego_precio'], 2); ?></p>
                                    </div>
                                </div>
                                <div class="flex justify-between sm:justify-end items-center w-full sm:w-auto gap-8">
                                    <div class="text-center sm:text-right">
                                        <span class="text-xs text-gray-500 block">Cantidad</span>
                                        <span class="font-bold text-gray-300">x<?php echo $item['cantidad']; ?></span>
                                    </div>
                                    <div class="text-right min-w-[100px]">
                                        <span class="text-xs text-gray-500 block">Subtotal</span>
                                        <span class="font-bold text-white text-lg">Bs. <?php echo number_format($item['subtotal'], 2); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

</body>
</html>