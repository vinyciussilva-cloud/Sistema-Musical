<?php
// Execute no terminal: php geraSenha.php
// Altere a senha abaixo antes de gerar outro hash.
$senha = '123';
echo password_hash($senha, PASSWORD_BCRYPT) . PHP_EOL;