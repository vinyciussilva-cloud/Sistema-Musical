<?php
require_once __DIR__ . '/valida_sessao.php';
require_once __DIR__ . '/conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValido()) {
        definirFlash('error', 'Sua sessão expirou. Atualize a página e tente novamente.');
        redirecionar('cadastro_artista.php');
    }

    $acao = $_POST['acao'] ?? '';

    if ($acao === 'excluir') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
        if ($id < 1) {
            definirFlash('error', 'Artista inválido.');
            redirecionar('cadastro_artista.php');
        }

        $stmt = $conn->prepare('SELECT imagem FROM artistas WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $artistaExcluir = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$artistaExcluir) {
            definirFlash('error', 'Artista não encontrado.');
            redirecionar('cadastro_artista.php');
        }

        $stmt = $conn->prepare('SELECT COUNT(*) AS total FROM musicas WHERE artista_id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $totalVinculadas = (int)$stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();

        if ($totalVinculadas > 0) {
            definirFlash('error', 'Este artista possui músicas cadastradas. Exclua ou transfira as músicas antes de remover o artista.');
            redirecionar('cadastro_artista.php');
        }

        $stmt = $conn->prepare('DELETE FROM artistas WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();

        apagarImagem($artistaExcluir['imagem']);
        definirFlash('success', 'Artista excluído com sucesso.');
        redirecionar('cadastro_artista.php');
    }

    if ($acao === 'salvar') {
        $id     = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
        $nome   = trim($_POST['nome'] ?? '');
        $genero = trim($_POST['genero'] ?? '');
        $pais   = trim($_POST['pais'] ?? '');

        if ($nome === '' || tamanhoTexto($nome) > 100) {
            definirFlash('error', 'Informe o nome do artista (máximo de 100 caracteres).');
            redirecionar($id ? 'cadastro_artista.php?edit_id=' . $id : 'cadastro_artista.php');
        }
        if (tamanhoTexto($genero) > 50 || tamanhoTexto($pais) > 50) {
            definirFlash('error', 'Gênero e país devem ter no máximo 50 caracteres.');
            redirecionar($id ? 'cadastro_artista.php?edit_id=' . $id : 'cadastro_artista.php');
        }

        $artistaAtual = null;
        if ($id > 0) {
            $stmt = $conn->prepare('SELECT imagem FROM artistas WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $artistaAtual = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$artistaAtual) {
                definirFlash('error', 'Artista não encontrado para edição.');
                redirecionar('cadastro_artista.php');
            }
        }

        $upload = salvarImagem($_FILES['imagem'] ?? []);
        if ($upload['erro']) {
            definirFlash('error', $upload['erro']);
            redirecionar($id ? 'cadastro_artista.php?edit_id=' . $id : 'cadastro_artista.php');
        }

        $imagemNova = $upload['caminho'];
        $imagemFinal = $imagemNova ?: ($artistaAtual['imagem'] ?? null);

        try {
            if ($id > 0) {
                $stmt = $conn->prepare('UPDATE artistas SET nome = ?, genero = ?, pais = ?, imagem = ? WHERE id = ?');
                $stmt->bind_param('ssssi', $nome, $genero, $pais, $imagemFinal, $id);
                $mensagem = 'Artista atualizado com sucesso.';
            } else {
                $stmt = $conn->prepare('INSERT INTO artistas (nome, genero, pais, imagem) VALUES (?, ?, ?, ?)');
                $stmt->bind_param('ssss', $nome, $genero, $pais, $imagemFinal);
                $mensagem = 'Artista cadastrado com sucesso.';
            }
            $stmt->execute();
            $stmt->close();

            if ($imagemNova && !empty($artistaAtual['imagem'])) {
                apagarImagem($artistaAtual['imagem']);
            }
            definirFlash('success', $mensagem);
            redirecionar('cadastro_artista.php');
        } catch (mysqli_sql_exception $e) {
            if ($imagemNova) {
                apagarImagem($imagemNova);
            }
            definirFlash('error', 'Não foi possível salvar o artista. Confira os dados e tente novamente.');
            redirecionar($id ? 'cadastro_artista.php?edit_id=' . $id : 'cadastro_artista.php');
        }
    }
}

$artistaEdicao = null;
if (isset($_GET['edit_id'])) {
    $idEdicao = filter_input(INPUT_GET, 'edit_id', FILTER_VALIDATE_INT) ?: 0;
    if ($idEdicao > 0) {
        $stmt = $conn->prepare('SELECT * FROM artistas WHERE id = ?');
        $stmt->bind_param('i', $idEdicao);
        $stmt->execute();
        $artistaEdicao = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
    if (!$artistaEdicao) {
        definirFlash('error', 'Artista não encontrado.');
        redirecionar('cadastro_artista.php');
    }
}

$artistas = $conn->query(
    'SELECT a.*, (SELECT COUNT(*) FROM musicas m WHERE m.artista_id = a.id) AS total_musicas
     FROM artistas a ORDER BY a.nome'
);
$flash = obterFlash();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $artistaEdicao ? 'Editar Artista' : 'Novo Artista' ?> — Sistema Musical</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
    <div class="page-header-bar">
        <h2><?= $artistaEdicao ? '✏️ Editar Artista' : '🎤 Gerenciar Artistas' ?></h2>
        <div class="header-actions">
            <a href="index.php" class="btn-back">⬅ Voltar ao Menu</a>
        </div>
    </div>

    <?php if ($flash): ?>
        <div class="message <?= e($flash['tipo']) ?>"><?= e($flash['mensagem']) ?></div>
    <?php endif; ?>

    <form method="post" action="cadastro_artista.php" enctype="multipart/form-data">
        <?= campoCSRF() ?>
        <input type="hidden" name="acao" value="salvar">
        <input type="hidden" name="id" value="<?= e($artistaEdicao['id'] ?? '') ?>">

        <div class="form-grid">
            <div class="form-group">
                <label for="nome">Nome do Artista <span class="required">*</span></label>
                <input type="text" id="nome" name="nome" maxlength="100" required
                       value="<?= e($artistaEdicao['nome'] ?? '') ?>" placeholder="Ex: Djavan, Marisa Monte…">
            </div>
            <div class="form-group">
                <label for="genero">Gênero Musical</label>
                <input type="text" id="genero" name="genero" maxlength="50"
                       value="<?= e($artistaEdicao['genero'] ?? '') ?>" placeholder="Ex: MPB, Rock, Samba…">
            </div>
            <div class="form-group">
                <label for="pais">País</label>
                <input type="text" id="pais" name="pais" maxlength="50"
                       value="<?= e($artistaEdicao['pais'] ?? '') ?>" placeholder="Ex: Brasil, EUA…">
            </div>
            <div class="form-group">
                <label for="imagem">Foto do Artista</label>
                <input type="file" id="imagem" name="imagem" accept=".jpg,.jpeg,.png,image/jpeg,image/png">
                <small>JPG ou PNG · Máximo 5 MB · A imagem será redimensionada para até 300 × 300 px.</small>
                <?php if (!empty($artistaEdicao['imagem'])): ?>
                    <div class="current-image">
                        <img src="<?= e($artistaEdicao['imagem']) ?>" alt="Foto atual de <?= e($artistaEdicao['nome']) ?>">
                        <small>Foto atual. Deixe o campo vazio para mantê-la.</small>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary"><?= $artistaEdicao ? '💾 Salvar Alterações' : '➕ Cadastrar Artista' ?></button>
            <?php if ($artistaEdicao): ?>
                <a href="cadastro_artista.php" class="btn-back">Cancelar edição</a>
            <?php endif; ?>
        </div>
    </form>

    <h3>📋 Artistas Cadastrados</h3>
    <div class="table-container">
        <table>
            <thead>
                <tr><th class="col-foto">Foto</th><th>Nome</th><th>Gênero</th><th>País</th><th>Músicas</th><th class="col-acoes">Ações</th></tr>
            </thead>
            <tbody>
            <?php if ($artistas->num_rows > 0): ?>
                <?php while ($artista = $artistas->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <?php if (!empty($artista['imagem'])): ?>
                                <img src="<?= e($artista['imagem']) ?>" alt="Foto de <?= e($artista['nome']) ?>" class="table-thumbnail" loading="lazy">
                            <?php else: ?>
                                <div class="no-image">Sem<br>foto</div>
                            <?php endif; ?>
                        </td>
                        <td><?= e($artista['nome']) ?></td>
                        <td><?= e($artista['genero']) ?: '—' ?></td>
                        <td><?= e($artista['pais']) ?: '—' ?></td>
                        <td><?= (int)$artista['total_musicas'] ?></td>
                        <td>
                            <div class="action-buttons">
                                <a href="cadastro_artista.php?edit_id=<?= (int)$artista['id'] ?>" class="btn-edit">✏️ Editar</a>
                                <a href="cadastro_musica.php?artista_id=<?= (int)$artista['id'] ?>" class="btn-music">🎵 Músicas</a>
                                <form method="post" action="cadastro_artista.php" class="inline-form" onsubmit="return confirm('Excluir <?= e($artista['nome']) ?>?');">
                                    <?= campoCSRF() ?>
                                    <input type="hidden" name="acao" value="excluir">
                                    <input type="hidden" name="id" value="<?= (int)$artista['id'] ?>">
                                    <button type="submit" class="btn-delete" <?= (int)$artista['total_musicas'] > 0 ? 'disabled title="Exclua primeiro as músicas vinculadas."' : '' ?>>🗑️</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6" class="empty-cell">Nenhum artista cadastrado ainda.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
