<?php
session_start();

// PROTEGER RUTA
if(!isset($_SESSION['autenticado'])){

    header("Location: index.php");
    exit();
}

$usuario = $_SESSION['usuario'];
$rol = $_SESSION['rol'];
?>

<!DOCTYPE html>
<html lang="es">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Panel</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family: Arial, Helvetica, sans-serif;
}

body{
    background:#1f1f1f;
    color:white;
    min-height:100vh;
    display:flex;
    flex-direction:column;
}

.navbar{
    background:#2a2a2a;
    padding:15px 30px;
    border-bottom:1px solid #444;
}

.navbar-brand{
    color:#ff9800 !important;
    font-size:28px;
    font-weight:bold;
}

.nav-link{
    color:white !important;
    margin-left:15px;
}

.main{
    flex:1;
    display:flex;
    justify-content:center;
    align-items:center;
    padding:40px;
}

.panel-box{
    width:100%;
    max-width:700px;
    background:#2a2a2a;
    padding:40px;
    border-radius:25px;
    box-shadow:0 0 20px rgba(0,0,0,.5);
    text-align:center;
}

.panel-box h1{
    font-size:40px;
    margin-bottom:20px;
}

.panel-box p{
    font-size:20px;
    color:#ccc;
    margin-bottom:15px;
}

.role{
    color:#ff9800;
    font-weight:bold;
}

.btn-logout{
    margin-top:25px;
    padding:12px 30px;
    border:none;
    border-radius:35px;
    background:#c2701d;
    color:white;
    font-size:18px;
    text-decoration:none;
    transition:.3s;
    display:inline-block;
}

.btn-logout:hover{
    background:#dd841f;
}

.footer{
    text-align:center;
    padding:20px;
    color:#aaa;
    border-top:1px solid #333;
}

</style>

</head>
<body>

<nav class="navbar navbar-expand-lg">

<div class="container-fluid">

    <a class="navbar-brand" href="#">
        SISTEMA WEB
    </a>

    <div>

        <a class="nav-link d-inline" href="#">
            Inicio
        </a>

        <a class="nav-link d-inline" href="#">
            Productos
        </a>

        <a class="nav-link d-inline" href="#">
            Ventas
        </a>

    </div>

</div>

</nav>

<div class="main">

    <div class="panel-box">

        <h1>
            Bienvenido <?php echo $usuario; ?>
        </h1>

        <p>
            Has iniciado sesión correctamente.
        </p>

        <p>
            Rol:
            <span class="role">
                <?php echo strtoupper($rol); ?>
            </span>
        </p>

        <p>
            Sistema protegido con autenticación 2FA.
        </p>

        <a href="logout.php"
        class="btn-logout">
            <i class="fa-solid fa-right-from-bracket"></i>
            Cerrar Sesión
        </a>

    </div>

</div>

<footer class="footer">
    © 2025 Sistema Web Seguro | Panel Administrativo
</footer>

</body>
</html>