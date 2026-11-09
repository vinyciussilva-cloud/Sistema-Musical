<?php
/*
 * Conexão central do sistema.
 * Ajuste as credenciais abaixo conforme o MySQL do seu computador.
 */
if (!extension_loaded('mysqli')) {
    http_response_code(500);
    exit('A extensão mysqli do PHP não está habilitada. Ative-a no php.ini e reinicie o servidor.');
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$servername = getenv('DB_HOST') ?: 'localhost';
$username   = getenv('DB_USER') ?: 'root';
$password   = getenv('DB_PASS') !== false ? getenv('DB_PASS') : 'Senai@118';
$dbname     = getenv('DB_NAME') ?: 'sistema_musical_v2';

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    exit(
        'Não foi possível conectar ao MySQL. Confira as credenciais em conexao.php, ' .
        'importe bd.sql e confirme se o serviço MySQL está ligado.'
    );
}

$diretorioImagens = __DIR__ . DIRECTORY_SEPARATOR . 'img';
if (!is_dir($diretorioImagens) && !mkdir($diretorioImagens, 0755, true) && !is_dir($diretorioImagens)) {
    http_response_code(500);
    exit('Não foi possível criar a pasta img/. Verifique as permissões da pasta do projeto.');
}
