<?php
/* Integrante da equipe: Vinycius Lopes Monteiro da Silva */

require_once __DIR__ . '/funcoes.php';
iniciarSessaoSegura();

if (!empty($_SESSION['usuario_id'])) {
    redirecionar('index.php');
}

require_once __DIR__ . '/conexao.php';

$erro = '';
$usuarioDigitado = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuarioDigitado = trim($_POST['usuario'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (!csrfValido()) {
        $erro = 'Sua sessão expirou. Atualize a página e tente novamente.';
    } elseif ($usuarioDigitado === '' || $senha === '') {
        $erro = 'Preencha o usuário e a senha.';
    } else {
        $stmt = $conn->prepare('SELECT id, usuario, senha FROM usuarios WHERE usuario = ? LIMIT 1');
        $stmt->bind_param('s', $usuarioDigitado);
        $stmt->execute();
        $usuarioBanco = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($usuarioBanco && password_verify($senha, $usuarioBanco['senha'])) {
            session_regenerate_id(true);
            $_SESSION['usuario_id'] = (int)$usuarioBanco['id'];
            $_SESSION['usuario'] = $usuarioBanco['usuario'];
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            redirecionar('index.php');
        }

        $erro = 'Usuário ou senha incorretos.';
    }
}

$flash = obterFlash();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Sistema Musical</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body class="login-page">
    <main class="login-card">
        <header class="login-header">
            <h1>🎵 Sistema Musical</h1>
            <p>Entre com suas credenciais</p>
        </header>

        <?php if ($erro !== ''): ?>
            <div class="message error">⚠️ <?= e($erro) ?></div>
        <?php endif; ?>
        <?php if ($flash): ?>
            <div class="message <?= e($flash['tipo']) ?>"><?= e($flash['mensagem']) ?></div>
        <?php endif; ?>
        <?php if (($_GET['logout'] ?? '') === '1'): ?>
            <div class="message success">✅ Você saiu do sistema.</div>
        <?php endif; ?>

        <form method="post" action="login.php">
            <?= campoCSRF() ?>
            <div class="form-group">
                <label for="usuario">Usuário</label>
                <input type="text" id="usuario" name="usuario"
                       value="<?= e($usuarioDigitado) ?>" required
                       autocomplete="username" placeholder="Seu usuário" autofocus>
            </div>
            <div class="form-group">
                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" required
                       autocomplete="current-password" placeholder="Sua senha">
            </div>
            <button type="submit" class="btn-primary">Entrar</button>
        </form>

        <div class="login-hint">
            <strong>Credenciais iniciais:</strong><br>
            Usuário: <code>admin</code> &nbsp;·&nbsp; Senha: <code>123</code>
        </div>
    </main>
</body>
</html>
