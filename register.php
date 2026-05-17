<?php
include("conexion.php");

$mensaje = "";

if(isset($_POST['registrar'])){

    $nombre = trim($_POST['nombre']);
    $correo = trim($_POST['correo']);
    $password = trim($_POST['password']);
    $confirmar = trim($_POST['confirmar']);

    // VALIDAR CONTRASEÑA
    if($password != $confirmar){

        $mensaje = "Las contraseñas no coinciden";

    }elseif(
        strlen($password) < 8 ||
        !preg_match('/[A-Z]/', $password) ||
        !preg_match('/[a-z]/', $password) ||
        !preg_match('/[0-9]/', $password)
    ){

        $mensaje = "La contraseña debe tener mínimo 8 caracteres, una mayúscula, una minúscula y un número";

    }else{

        // VERIFICAR SI EL CORREO YA EXISTE
        $verificar = "SELECT * FROM usuario WHERE correo='$correo'";
        $resultado = $conn->query($verificar);

        if($resultado->num_rows > 0){

            $mensaje = "El correo ya está registrado";

        }else{

            // ENCRIPTAR CONTRASEÑA
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $sql = "INSERT INTO usuario(nombre, correo, contraseña)
                    VALUES('$nombre','$correo','$passwordHash')";

            if($conn->query($sql)){

                header("Location: index.php");
                exit();

            }else{

                $mensaje = "Error al registrar usuario";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Registro</title>

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
    min-height:100vh;
    display:flex;
    flex-direction:column;
    justify-content:center;
    align-items:center;
    color:white;
}

.container-register{
    width:100%;
    max-width:500px;
    background:#2a2a2a;
    border-radius:25px;
    padding:40px;
    box-shadow:0 0 20px rgba(0,0,0,.5);
    border:1px solid #3a3a3a;
}

.logo{
    text-align:center;
    margin-bottom:20px;
}

.logo h1{
    font-size:40px;
    letter-spacing:3px;
}

.title{
    text-align:center;
    font-size:30px;
    margin-bottom:25px;
    font-weight:bold;
}

.input-box{
    position:relative;
    margin-bottom:20px;
}

.input-box input{
    width:100%;
    padding:15px 20px 15px 50px;
    background:#333;
    border:1px solid #444;
    border-radius:35px;
    color:white;
    font-size:16px;
    outline:none;
}

.input-box input:focus{
    border-color:#ff9800;
}

.input-box i{
    position:absolute;
    left:18px;
    top:50%;
    transform:translateY(-50%);
    color:#aaa;
}

.password-rules{
    font-size:14px;
    color:#bbb;
    margin-bottom:20px;
}

.password-rules ul{
    padding-left:20px;
}

.register-btn{
    width:100%;
    padding:15px;
    border:none;
    border-radius:35px;
    background:#c2701d;
    color:white;
    font-size:20px;
    cursor:pointer;
    transition:.3s;
}

.register-btn:hover{
    background:#dd841f;
}

.login-link{
    text-align:center;
    margin-top:20px;
}

.login-link a{
    color:#ff9800;
    text-decoration:none;
}

.footer{
    margin-top:25px;
    color:#aaa;
    font-size:14px;
}

.alert{
    border-radius:15px;
}

</style>

</head>
<body>

<div class="container-register">

    <div class="logo">
        <h1>REGISTER</h1>
    </div>

    <div class="title">
        Crear Cuenta
    </div>

    <?php if($mensaje != ""): ?>

        <div class="alert alert-warning">
            <?php echo $mensaje; ?>
        </div>

    <?php endif; ?>

    <form method="POST">

        <div class="input-box">
            <i class="fa-solid fa-user"></i>

            <input type="text"
            name="nombre"
            placeholder="Nombre completo"
            required>
        </div>

        <div class="input-box">
            <i class="fa-solid fa-envelope"></i>

            <input type="email"
            name="correo"
            placeholder="Correo electrónico"
            required>
        </div>

        <div class="input-box">
            <i class="fa-solid fa-lock"></i>

            <input type="password"
            name="password"
            id="password"
            placeholder="Contraseña"
            required>
        </div>

        <div class="password-rules">

            La contraseña debe contener:

            <ul>
                <li>Mínimo 8 caracteres</li>
                <li>Una mayúscula</li>
                <li>Una minúscula</li>
                <li>Un número</li>
            </ul>

        </div>

        <div class="input-box">
            <i class="fa-solid fa-lock"></i>

            <input type="password"
            name="confirmar"
            id="confirmar"
            placeholder="Confirmar contraseña"
            required>
        </div>

        <div class="form-check mb-4">

            <input class="form-check-input"
            type="checkbox"
            onclick="mostrarPassword()"
            id="showPass">

            <label class="form-check-label" for="showPass">
                Mostrar contraseña
            </label>

        </div>

        <button type="submit"
        name="registrar"
        class="register-btn">
            REGISTRARSE
        </button>

    </form>

    <div class="login-link">
        ¿Ya tienes cuenta?
        <a href="index.php">Inicia sesión</a>
    </div>

</div>

<footer class="footer">
    © 2025 Sistema Web Seguro
</footer>

<script>

function mostrarPassword(){

    let pass = document.getElementById("password");
    let confirmar = document.getElementById("confirmar");

    if(pass.type === "password"){

        pass.type = "text";
        confirmar.type = "text";

    }else{

        pass.type = "password";
        confirmar.type = "password";
    }
}

</script>

</body>
</html>