<?php
require_once __DIR__ . '/valida_sessao.php';
require_once __DIR__ . '/conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValido()) {
        definirFlash('error', 'Sua sessão expirou. Atualize a página e tente novamente.');
        redirecionar('cadastro_musica.php');
    }

    $acao = $_POST['acao'] ?? '';

    if ($acao === 'excluir') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
        if ($id < 1) {
            definirFlash('error', 'Música inválida.');
            redirecionar('cadastro_musica.php');
        }

        $stmt = $conn->prepare('SELECT imagem_capa FROM musicas WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $musicaExcluir = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$musicaExcluir) {
            definirFlash('error', 'Música não encontrada.');
            redirecionar('cadastro_musica.php');
        }

        $stmt = $conn->prepare('DELETE FROM musicas WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        apagarImagem($musicaExcluir['imagem_capa']);

        definirFlash('success', 'Música excluída com sucesso.');
        $voltar = !empty($_POST['artista_id']) ? 'cadastro_musica.php?artista_id=' . (int)$_POST['artista_id'] : 'cadastro_musica.php';
        redirecionar($voltar);
    }

    if ($acao === 'salvar') {
        $id           = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
        $artistaId    = filter_input(INPUT_POST, 'artista_id', FILTER_VALIDATE_INT) ?: 0;
        $titulo       = trim($_POST['titulo'] ?? '');
        $album        = trim($_POST['album'] ?? '');
        $genero       = trim($_POST['genero'] ?? '');
        $youtubeLink  = trim($_POST['youtube_link'] ?? '');
        $letra        = trim($_POST['letra'] ?? '');
        $retornoArtista = $artistaId > 0 ? '?artista_id=' . $artistaId : '';

        $destinoErro = $id > 0
            ? 'cadastro_musica.php?edit_id=' . $id
            : 'cadastro_musica.php' . $retornoArtista;

        if ($artistaId < 1 || $titulo === '' || tamanhoTexto($titulo) > 100) {
            definirFlash('error', 'Selecione um artista e informe o título da música (máximo de 100 caracteres).');
            redirecionar($destinoErro);
        }
        if (tamanhoTexto($album) > 100 || tamanhoTexto($genero) > 50) {
            definirFlash('error', 'Álbum deve ter até 100 caracteres e gênero até 50.');
            redirecionar($destinoErro);
        }
        if (!youtubeVideoId($youtubeLink)) {
            definirFlash('error', 'Insira um link válido de um vídeo do YouTube.');
            redirecionar($destinoErro);
        }

        $stmt = $conn->prepare('SELECT id FROM artistas WHERE id = ?');
        $stmt->bind_param('i', $artistaId);
        $stmt->execute();
        $artistaExiste = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$artistaExiste) {
            definirFlash('error', 'O artista selecionado não existe.');
            redirecionar($destinoErro);
        }

        $musicaAtual = null;
        if ($id > 0) {
            $stmt = $conn->prepare('SELECT imagem_capa FROM musicas WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $musicaAtual = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$musicaAtual) {
                definirFlash('error', 'Música não encontrada para edição.');
                redirecionar('cadastro_musica.php');
            }
        }

        $upload = salvarImagem($_FILES['imagem_capa'] ?? []);
        if ($upload['erro']) {
            definirFlash('error', $upload['erro']);
            redirecionar($destinoErro);
        }

        $imagemNova = $upload['caminho'];
        $imagemFinal = $imagemNova ?: ($musicaAtual['imagem_capa'] ?? null);

        try {
            if ($id > 0) {
                $stmt = $conn->prepare(
                    'UPDATE musicas
                     SET artista_id = ?, titulo = ?, album = ?, genero = ?, youtube_link = ?, letra = ?, imagem_capa = ?
                     WHERE id = ?'
                );
                $stmt->bind_param('issssssi', $artistaId, $titulo, $album, $genero, $youtubeLink, $letra, $imagemFinal, $id);
                $mensagem = 'Música atualizada com sucesso.';
            } else {
                $stmt = $conn->prepare(
                    'INSERT INTO musicas (artista_id, titulo, album, genero, youtube_link, letra, imagem_capa)
                     VALUES (?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->bind_param('issssss', $artistaId, $titulo, $album, $genero, $youtubeLink, $letra, $imagemFinal);
                $mensagem = 'Música cadastrada com sucesso.';
            }
            $stmt->execute();
            $stmt->close();

            if ($imagemNova && !empty($musicaAtual['imagem_capa'])) {
                apagarImagem($musicaAtual['imagem_capa']);
            }
            definirFlash('success', $mensagem);
            redirecionar('cadastro_musica.php?artista_id=' . $artistaId);
        } catch (mysqli_sql_exception $e) {
            if ($imagemNova) {
                apagarImagem($imagemNova);
            }
            definirFlash('error', 'Não foi possível salvar a música. Confira os dados e tente novamente.');
            redirecionar($destinoErro);
        }
    }
}

$musicaEdicao = null;
if (isset($_GET['edit_id'])) {
    $idEdicao = filter_input(INPUT_GET, 'edit_id', FILTER_VALIDATE_INT) ?: 0;
    if ($idEdicao > 0) {
        $stmt = $conn->prepare('SELECT * FROM musicas WHERE id = ?');
        $stmt->bind_param('i', $idEdicao);
        $stmt->execute();
        $musicaEdicao = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
    if (!$musicaEdicao) {
        definirFlash('error', 'Música não encontrada.');
        redirecionar('cadastro_musica.php');
    }
}

$artistas = $conn->query('SELECT id, nome FROM artistas ORDER BY nome');
$artistaSelecionado = (int)($_GET['artista_id'] ?? ($musicaEdicao['artista_id'] ?? 0));
if ($musicaEdicao) {
    $artistaSelecionado = (int)$musicaEdicao['artista_id'];
}

if ($artistaSelecionado > 0) {
    $stmt = $conn->prepare(
        'SELECT m.*, a.nome AS artista_nome
         FROM musicas m INNER JOIN artistas a ON a.id = m.artista_id
         WHERE m.artista_id = ? ORDER BY m.titulo'
    );
    $stmt->bind_param('i', $artistaSelecionado);
    $stmt->execute();
    $musicas = $stmt->get_result();
    $stmt->close();
} else {
    $musicas = $conn->query(
        'SELECT m.*, a.nome AS artista_nome
         FROM musicas m INNER JOIN artistas a ON a.id = m.artista_id
         ORDER BY a.nome, m.titulo'
    );
}
$flash = obterFlash();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $musicaEdicao ? 'Editar Música' : 'Nova Música' ?> — Sistema Musical</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
    <div class="page-header-bar">
        <h2><?= $musicaEdicao ? '✏️ Editar Música' : '🎵 Cadastrar Músicas' ?></h2>
        <div class="header-actions">
            <a href="cadastro_artista.php" class="btn-back">⬅ Voltar para Artistas</a>
            <a href="index.php" class="btn-back">Menu</a>
        </div>
    </div>

    <?php if ($flash): ?>
        <div class="message <?= e($flash['tipo']) ?>"><?= e($flash['mensagem']) ?></div>
    <?php endif; ?>

    <?php if ($artistas->num_rows === 0): ?>
        <div class="message error">Cadastre pelo menos um artista antes de adicionar músicas.</div>
        <a href="cadastro_artista.php" class="btn-primary">Cadastrar primeiro artista</a>
    <?php else: ?>
    <form method="post" action="cadastro_musica.php" enctype="multipart/form-data">
        <?= campoCSRF() ?>
        <input type="hidden" name="acao" value="salvar">
        <input type="hidden" name="id" value="<?= e($musicaEdicao['id'] ?? '') ?>">

        <div class="form-grid">
            <div class="form-group">
                <label for="artista_id">Artista <span class="required">*</span></label>
                <select name="artista_id" id="artista_id" required>
                    <option value="">Selecione um artista</option>
                    <?php
                    $artistas->data_seek(0);
                    while ($artista = $artistas->fetch_assoc()):
                    ?>
                        <option value="<?= (int)$artista['id'] ?>" <?= $artistaSelecionado === (int)$artista['id'] ? 'selected' : '' ?>>
                            <?= e($artista['nome']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="titulo">Título <span class="required">*</span></label>
                <input type="text" id="titulo" name="titulo" maxlength="100" required
                       value="<?= e($musicaEdicao['titulo'] ?? '') ?>" placeholder="Ex: Oceano, Aquarela…">
            </div>
            <div class="form-group">
                <label for="album">Álbum</label>
                <input type="text" id="album" name="album" maxlength="100"
                       value="<?= e($musicaEdicao['album'] ?? '') ?>" placeholder="Nome do álbum (opcional)">
            </div>
            <div class="form-group">
                <label for="genero">Gênero</label>
                <input type="text" id="genero" name="genero" maxlength="50"
                       value="<?= e($musicaEdicao['genero'] ?? '') ?>" placeholder="Ex: MPB, Rock…">
            </div>
            <div class="form-group">
                <label for="youtube_link">Link do YouTube <span class="required">*</span></label>
                <input type="url" id="youtube_link" name="youtube_link" required
                       value="<?= e($musicaEdicao['youtube_link'] ?? '') ?>"
                       placeholder="https://www.youtube.com/watch?v=…">
                <small>Cole a URL de um vídeo, não de um canal ou playlist.</small>
            </div>
            <div class="form-group">
                <label for="imagem_capa">Capa do Álbum</label>
                <input type="file" id="imagem_capa" name="imagem_capa" accept=".jpg,.jpeg,.png,image/jpeg,image/png">
                <small>JPG ou PNG · Máximo 5 MB · Redimensionamento automático para até 300 × 300 px.</small>
                <?php if (!empty($musicaEdicao['imagem_capa'])): ?>
                    <div class="current-image">
                        <img src="<?= e($musicaEdicao['imagem_capa']) ?>" alt="Capa atual">
                        <small>Capa atual. Deixe o campo vazio para mantê-la.</small>
                    </div>
                <?php endif; ?>
            </div>
            <div class="form-group full">
                <label for="letra">Letra da Música</label>
                <textarea id="letra" name="letra" placeholder="Cole a letra aqui (opcional)…"><?= e($musicaEdicao['letra'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary"><?= $musicaEdicao ? '💾 Salvar Alterações' : '➕ Cadastrar Música' ?></button>
            <?php if ($musicaEdicao): ?>
                <a href="cadastro_musica.php<?= $artistaSelecionado ? '?artista_id=' . $artistaSelecionado : '' ?>" class="btn-back">Cancelar edição</a>
            <?php endif; ?>
        </div>
    </form>
    <?php endif; ?>

    <div class="filter-toolbar">
        <form method="get" action="cadastro_musica.php" class="artist-filter-form">
            <label for="filtro_artista">Exibir músicas de</label>
            <select name="artista_id" id="filtro_artista" onchange="this.form.submit()">
                <option value="">Todos os artistas</option>
                <?php
                $artistas->data_seek(0);
                while ($artista = $artistas->fetch_assoc()):
                ?>
                    <option value="<?= (int)$artista['id'] ?>" <?= $artistaSelecionado === (int)$artista['id'] ? 'selected' : '' ?>>
                        <?= e($artista['nome']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </form>
    </div>

    <h3>🎵 Músicas Cadastradas</h3>
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
                                <form method="post" action="cadastro_musica.php" class="inline-form" onsubmit="return confirm('Excluir <?= e($musica['titulo']) ?>?');">
                                    <?= campoCSRF() ?>
                                    <input type="hidden" name="acao" value="excluir">
                                    <input type="hidden" name="id" value="<?= (int)$musica['id'] ?>">
                                    <input type="hidden" name="artista_id" value="<?= (int)$musica['artista_id'] ?>">
                                    <button type="submit" class="btn-delete">🗑️</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="7" class="empty-cell">Nenhuma música encontrada para este filtro.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
