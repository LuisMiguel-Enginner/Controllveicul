<?php
$host = "localhost";
$user = "root";
$pass = "";
$db = "sistema_veiculos";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Erro na conexão");
}
?>
