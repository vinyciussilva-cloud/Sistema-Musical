<?php
require_once __DIR__ . '/valida_sessao.php';
require_once __DIR__ . '/conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'excluir') {
    if (!csrfValido()) {
        definirFlash('error', 'Sua sessão expirou. Atualize a página e tente novamente.');
        redirecionar('listagem_musicas.php');
    }

    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
    if ($id > 0) {
        $stmt = $conn->prepare('SELECT imagem_capa FROM musicas WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $imagem = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $stmt = $conn->prepare('DELETE FROM musicas WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();

        if ($imagem) {
            apagarImagem($imagem['imagem_capa']);
            definirFlash('success', 'Música excluída com sucesso.');
        } else {
            definirFlash('error', 'Música não encontrada.');
        }
    }

    $queryVolta = trim($_POST['filtros'] ?? '');
    redirecionar('listagem_musicas.php' . ($queryVolta !== '' ? '?' . $queryVolta : ''));
}

$busca = trim($_GET['busca'] ?? '');
$artistaFiltro = filter_input(INPUT_GET, 'artista', FILTER_VALIDATE_INT) ?: 0;
$generoFiltro = trim($_GET['genero'] ?? '');

$stmt = $conn->prepare(
    "SELECT m.*, a.nome AS artista_nome
     FROM musicas m
     INNER JOIN artistas a ON a.id = m.artista_id
     WHERE (? = 0 OR a.id = ?)
       AND (? = '' OR m.genero LIKE ?)
       AND (? = '' OR (m.titulo LIKE ? OR COALESCE(m.album, '') LIKE ? OR a.nome LIKE ?))
     ORDER BY a.nome, m.titulo"
);
$buscaLike = '%' . $busca . '%';
$generoLike = '%' . $generoFiltro . '%';
$stmt->bind_param(
    'iissssss',
    $artistaFiltro,
    $artistaFiltro,
    $generoFiltro,
    $generoLike,
    $busca,
    $buscaLike,
    $buscaLike,
    $buscaLike
);
$stmt->execute();
$musicas = $stmt->get_result();

$artistas = $conn->query('SELECT id, nome FROM artistas ORDER BY nome');
$generos = $conn->query("SELECT DISTINCT genero FROM musicas WHERE genero IS NOT NULL AND genero <> '' ORDER BY genero");
$filtrosAtivos = http_build_query([
    'busca' => $busca,
    'artista' => $artistaFiltro ?: '',
    'genero' => $generoFiltro,
]);
$flash = obterFlash();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biblioteca Musical — Sistema Musical</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
    <div class="page-header-bar">
        <h2>🎼 Biblioteca Musical</h2>
        <div class="header-actions">
            <a href="index.php" class="btn-back">⬅ Voltar ao Menu</a>
            <a href="cadastro_musica.php" class="btn-primary">➕ Nova Música</a>
        </div>
    </div>

    <?php if ($flash): ?>
        <div class="message <?= e($flash['tipo']) ?>"><?= e($flash['mensagem']) ?></div>
    <?php endif; ?>

    <section class="filters">
        <form method="get" action="listagem_musicas.php" class="filter-form">
            <div class="filter-group">
                <div class="filter-item">
                    <label for="busca">Busca geral</label>
                    <input type="text" name="busca" id="busca" value="<?= e($busca) ?>" placeholder="Título, álbum ou artista…">
                </div>
                <div class="filter-item">
                    <label for="artista">Artista</label>
                    <select name="artista" id="artista">
                        <option value="">Todos os artistas</option>
                        <?php while ($artista = $artistas->fetch_assoc()): ?>
                            <option value="<?= (int)$artista['id'] ?>" <?= $artistaFiltro === (int)$artista['id'] ? 'selected' : '' ?>>
                                <?= e($artista['nome']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="filter-item">
                    <label for="genero">Gênero</label>
                    <select name="genero" id="genero">
                        <option value="">Todos os gêneros</option>
                        <?php while ($genero = $generos->fetch_assoc()): ?>
                            <option value="<?= e($genero['genero']) ?>" <?= $generoFiltro === $genero['genero'] ? 'selected' : '' ?>>
                                <?= e($genero['genero']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn-primary">🔍 Filtrar</button>
                <a href="listagem_musicas.php" class="btn-back">✖ Limpar</a>
            </div>
        </form>
    </section>

    <div class="table-container">
        <table>
            <thead>
                <tr><th class="col-capa">Capa</th><th>Título</th><th>Artista</th><th>Álbum</th><th>Gênero</th><th class="col-youtube">YouTube</th><th class="col-acoes">Ações</th></tr>
            </thead>
            <tbody>
            <?php if ($musicas->num_rows > 0): ?>
                <?php while ($musica = $musicas->fetch_assoc()): ?>
                    <?php $videoId = youtubeVideoId($musica['youtube_link'] ?? ''); ?>
                    <tr>
                        <td>
                            <?php if (!empty($musica['imagem_capa'])): ?>
                                <img src="<?= e($musica['imagem_capa']) ?>" alt="Capa de <?= e($musica['titulo']) ?>" class="table-thumbnail" loading="lazy">
                            <?php else: ?><div class="no-image">Sem<br>capa</div><?php endif; ?>
                        </td>
                        <td><?= e($musica['titulo']) ?></td>
                        <td><?= e($musica['artista_nome']) ?></td>
                        <td><?= e($musica['album']) ?: '—' ?></td>
                        <td><?= e($musica['genero']) ?: '—' ?></td>
                        <td>
                            <?php if ($videoId): ?>
                                <a href="https://www.youtube.com/watch?v=<?= e($videoId) ?>" target="_blank" rel="noopener noreferrer" class="btn-youtube">▶ Assistir</a>
                            <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a href="cadastro_musica.php?edit_id=<?= (int)$musica['id'] ?>" class="btn-edit">✏️ Editar</a>
                                <form method="post" action="listagem_musicas.php" class="inline-form" onsubmit="return confirm('Excluir <?= e($musica['titulo']) ?>?');">
                                    <?= campoCSRF() ?>
                                    <input type="hidden" name="acao" value="excluir">
                                    <input type="hidden" name="id" value="<?= (int)$musica['id'] ?>">
                                    <input type="hidden" name="filtros" value="<?= e($filtrosAtivos) ?>">
                                    <button type="submit" class="btn-delete">🗑️</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" class="empty-cell">
                        <?= $busca !== '' || $artistaFiltro > 0 || $generoFiltro !== ''
                            ? 'Nenhuma música encontrada com esses filtros.'
                            : 'Nenhuma música cadastrada ainda.' ?>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
