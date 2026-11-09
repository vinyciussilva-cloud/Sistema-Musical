<?php
require_once __DIR__ . '/funcoes.php';
iniciarSessaoSegura();

if (empty($_SESSION['usuario_id']) || empty($_SESSION['usuario'])) {
    redirecionar('login.php');
}