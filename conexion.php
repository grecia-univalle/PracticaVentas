<?php

$host = "localhost";
$user = "root";
$pass = "";
$db = "tienda_web";
$puerto = "3307";

$conn = new mysqli($host, $user, $pass, $db, $puerto);

if($conn->connect_error){
    die("Error de conexión: " . $conn->connect_error);
}

?>