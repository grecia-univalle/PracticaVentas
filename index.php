<?php
session_start();
include("conexion.php");

require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$error = "";

if(isset($_POST['login'])){

    $correo = trim($_POST['correo']);
    $password = trim($_POST['password']);

    // BUSCAR USUARIO (Se sugiere cambiar a consultas preparadas más adelante para mayor seguridad)
    $sql = "SELECT * FROM usuario WHERE correo='$correo'";
    $resultado = $conn->query($sql);

    if($resultado->num_rows > 0){

        $usuario = $resultado->fetch_assoc();

        // VERIFICAR CONTRASEÑA
        if(password_verify($password, $usuario['contraseña'])){

            // GENERAR CÓDIGO 2FA
            $codigo = rand(100000,999999);

            // GUARDAR SESIONES
            $_SESSION['codigo'] = $codigo;
            $_SESSION['usuario'] = $usuario['nombre'];
            $_SESSION['rol'] = $usuario['rol'];
            $_SESSION['correo'] = $correo;

            // CONFIGURAR PHPMailer
            $mail = new PHPMailer(true);

            try{

                // CONFIGURACIÓN SMTP
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;

                // TU GMAIL DEL SISTEMA
                $mail->Username = 'leongamer742@gmail.com';

                // CONTRASEÑA DE APLICACIÓN
                $mail->Password = 'acqv cjhe fbme demz';

                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                $mail->CharSet = 'UTF-8';

                // QUIÉN ENVÍA
                $mail->setFrom('leongamer742@gmail.com', 'Sistema Web');

                // A QUIÉN SE ENVÍA
                $mail->addAddress($correo);

                // FORMATO HTML
                $mail->isHTML(true);

                // ASUNTO
                $mail->Subject = 'Codigo de Verificacion';

                // MENSAJE
                $mail->Body = "
                <div style='font-family:Arial;padding:20px;'>

                    <h2>Verificación de Inicio de Sesión</h2>

                    <p>Tu código de acceso es:</p>

                    <h1 style='color:#ff9800;'>
                        $codigo
                    </h1>

                    <p>No compartas este código.</p>

                </div>
                ";

                // ENVIAR CORREO
                $mail->send();

                // IR A VERIFICAR
                header('Location: verificar.php');
                exit();

            }catch(Exception $e){

                $error = 'Error SMTP: ' . $mail->ErrorInfo;
            }

        }else{

            $error = 'Contraseña incorrecta';
        }

    }else{

        $error = 'Correo no encontrado';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Login</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:Arial, Helvetica, sans-serif;
}

body{
    background:#1f1f1f;
    min-height:100vh;
    display:flex;
    flex-direction:column;
    justify-content:center;
    align-items:center;
    color:white;
    padding: 20px 0;
}

.container-login{
    width:100%;
    max-width:450px;
    background:#2a2a2a;
    padding:40px;
    border-radius:25px;
    box-shadow:0 0 20px rgba(0,0,0,.5);
}

.logo{
    text-align:center;
    margin-bottom:20px;
}

.logo h1{
    font-size:40px;
}

.title{
    text-align:center;
    font-size:30px;
    margin-bottom:30px;
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
    outline:none;
}

.input-box i{
    position:absolute;
    left:18px;
    top:50%;
    transform:translateY(-50%);
    color:#aaa;
}

.login-btn{
    width:100%;
    padding:15px;
    border:none;
    border-radius:35px;
    background:#c2701d;
    color:white;
    font-size:20px;
    transition:.3s;
    cursor:pointer;
}

.login-btn:hover{
    background:#dd841f;
}

/* SECCIÓN SOCIAL LOGIN */
.social-separator {
    text-align: center;
    margin: 25px 0;
    color: #888;
    position: relative;
}

.social-separator::before, .social-separator::after {
    content: "";
    position: absolute;
    top: 50%;
    width: 35%;
    height: 1px;
    background: #444;
}

.social-separator::before { left: 0; }
.social-separator::after { right: 0; }

.social-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 15px;
    width: 100%;
    padding: 12px;
    margin-bottom: 12px;
    border: 1px solid #444;
    border-radius: 35px;
    color: white;
    text-decoration: none;
    font-size: 16px;
    font-weight: bold;
    transition: background 0.3s, transform 0.2s;
    background: #222;
}

.social-btn:hover {
    background: #333;
    color: white;
    transform: translateY(-2px);
}

.social-btn i {
    font-size: 20px;
    width: 24px;
    text-align: center;
}

.btn-facebook i { color: #1877f2; }
.btn-google i { color: #ea4335; }
.btn-steam i { color: #00adee; }

.register-link{
    text-align:center;
    margin-top:25px;
}

.register-link a{
    color:#ff9800;
    text-decoration:none;
}

.footer{
    margin-top:25px;
    color:#aaa;
}

.alert{
    border-radius:15px;
}

</style>

</head>
<body>

<div class="container-login">

    <div class="logo">
        <h1>LOGIN</h1>
    </div>

    <div class="title">
        Bienvenido
    </div>

    <?php if($error != ""): ?>

        <div class="alert alert-danger">
            <?php echo $error; ?>
        </div>

    <?php endif; ?>

    <form method="POST">

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
            placeholder="Contraseña"
            required>

        </div>

        <button type="submit"
        name="login"
        class="login-btn">
            INICIAR SESIÓN
        </button>

    </form>

    <div class="social-separator">o</div>

    <div class="social-login-container">
        <a href="oauth/google.php" class="social-btn btn-google">
            <i class="fa-brands fa-google"></i> Continuar con Google
        </a>

        <a href="oauth/facebook.php" class="social-btn btn-facebook">
            <i class="fa-brands fa-facebook"></i> Continuar con Facebook
        </a>

        
    </div>

    <div class="register-link">
        ¿No tienes cuenta?
        <a href="register.php">Crear cuenta</a>
    </div>

</div>

<footer class="footer">
    © 2025 Sistema Web Seguro
</footer>

</body>
</html>