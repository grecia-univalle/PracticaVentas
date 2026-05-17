<?php
session_start();

if(!isset($_SESSION['codigo'])){
    header("Location: index.php");
    exit();
}

$error = "";
$ingreso_exitoso = false;
$redirigir_a = "";

if(isset($_POST['verificar'])){

    $codigo = $_POST['codigo'];

    if($codigo == $_SESSION['codigo']){

        $_SESSION['autenticado'] = true;
        $ingreso_exitoso = true; // Bandera para activar el mensaje

        // VALIDAR EL ROL PARA SABER A DÓNDE REDIRIGIR
        // Nota: Asegúrate de que en tu base de datos los roles se llamen exactamente 'admin' y 'usuario' (o cámbiador por 'Administrador', etc.)
        if(isset($_SESSION['rol']) && $_SESSION['rol'] == 'admin'){
            $redirigir_a = "admin.php";
        } else {
            $redirigir_a = "usuario.php";
        }

    }else{
        $error = "Código incorrecto";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body{
            background:#1f1f1f;
            min-height:100vh;
            display:flex;
            justify-content:center;
            align-items:center;
            color:white;
        }
        .box{
            background:#2a2a2a;
            padding:40px;
            border-radius:20px;
            width:100%;
            max-width:400px;
        }
    </style>
</head>
<body>

<div class="box">

    <h2 class="text-center mb-4">Verificación 2FA</h2>

    <?php if($error != ""): ?>
        <div class="alert alert-danger">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <?php if($ingreso_exitoso): ?>
        <div class="alert alert-success text-center">
            Ingreso confirmado. Redirigiendo...
        </div>
        
        <script>
            alert("¡Ingreso confirmado!");
            setTimeout(function(){
                window.location.href = "<?php echo $redirigir_a; ?>";
            }, 1000); // 1000 milisegundos = 1 segundo de espera antes de ir a la página
        </script>
    <?php endif; ?>

    <?php if(!$ingreso_exitoso): ?>
        <form method="POST">
            <input type="text"
            name="codigo"
            class="form-control mb-3"
            placeholder="Ingrese el código"
            required>

            <button type="submit"
            name="verificar"
            class="btn btn-warning w-100">
                Verificar
            </button>
        </form>
    <?php endif; ?>

</div>

</body>
</html>