<?php
// Configurações do banco de dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'sistema_veiculos');
define('DB_USER', 'root');
define('DB_PASS', '');

// Configurações de sessão (apenas se a sessão ainda não estiver ativa)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Mude para 1 se usar HTTPS
}

if (!empty($_SESSION['timezone'])) {
    @date_default_timezone_set($_SESSION['timezone']);
} else {
    date_default_timezone_set('America/Sao_Paulo');
}

// Conexão com o banco de dados
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch(PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}
?>
