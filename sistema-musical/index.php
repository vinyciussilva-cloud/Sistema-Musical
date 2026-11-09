<?php
require_once __DIR__ . '/valida_sessao.php';
require_once __DIR__ . '/conexao.php';

$totalArtistas = (int)$conn->query('SELECT COUNT(*) AS total FROM artistas')->fetch_assoc()['total'];
$totalMusicas  = (int)$conn->query('SELECT COUNT(*) AS total FROM musicas')->fetch_assoc()['total'];
$flash = obterFlash();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Principal — Sistema Musical</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
    <div class="topbar">
        <form method="post" action="logout.php" class="logout-form">
            <?= campoCSRF() ?>
            <button type="submit" class="btn-back">🚪 Sair</button>
        </form>
    </div>

    <header class="page-header">
        <h1>🎵 Sistema Musical</h1>
        <p>Olá, <strong><?= e($_SESSION['usuario']) ?></strong>! O que deseja fazer?</p>
    </header>

    <?php if ($flash): ?>
        <div class="message <?= e($flash['tipo']) ?>"><?= e($flash['mensagem']) ?></div>
    <?php endif; ?>

    <section class="stats-grid">
        <div class="stat-card"><span class="stat-number"><?= $totalArtistas ?></span><span>Artistas cadastrados</span></div>
        <div class="stat-card"><span class="stat-number"><?= $totalMusicas ?></span><span>Músicas cadastradas</span></div>
    </section>

    <nav class="menu-grid">
        <div class="menu-card">
            <a href="cadastro_artista.php">
                <div class="menu-card-icon">🎤</div>
                <div class="menu-card-title">Gerenciar Artistas</div>
                <div class="menu-card-desc">Cadastre, edite e consulte artistas</div>
            </a>
        </div>
        <div class="menu-card">
            <a href="cadastro_musica.php">
                <div class="menu-card-icon">🎵</div>
                <div class="menu-card-title">Cadastrar Músicas</div>
                <div class="menu-card-desc">Adicione músicas e capas de álbuns</div>
            </a>
        </div>
        <div class="menu-card">
            <a href="listagem_musicas.php">
                <div class="menu-card-icon">🎼</div>
                <div class="menu-card-title">Biblioteca Musical</div>
                <div class="menu-card-desc">Consulte e filtre todas as músicas</div>
            </a>
        </div>
    </nav>
</div>
</body>
</html>
