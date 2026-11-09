<?php
/*
 * Funções compartilhadas: sessão, CSRF, mensagens, escape e imagens.
 */

function iniciarSessaoSegura(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        $https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $https,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function e($valor): string
{
    return htmlspecialchars((string)($valor ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function tamanhoTexto(string $texto): int
{
    return function_exists('mb_strlen') ? mb_strlen($texto, 'UTF-8') : strlen($texto);
}

function csrfToken(): string
{
    iniciarSessaoSegura();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfValido(): bool
{
    iniciarSessaoSegura();
    $token = $_POST['csrf_token'] ?? '';
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function campoCSRF(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

function definirFlash(string $tipo, string $mensagem): void
{
    iniciarSessaoSegura();
    $_SESSION['flash'] = ['tipo' => $tipo, 'mensagem' => $mensagem];
}

function obterFlash(): ?array
{
    iniciarSessaoSegura();
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function redirecionar(string $destino): void
{
    header('Location: ' . $destino);
    exit;
}

/**
 * Valida e salva uma imagem JPG/PNG, redimensionando-a para caber
 * em uma caixa de até 300 x 300 px, sem distorcer a proporção.
 *
 * Retorna ['caminho' => 'img/arquivo.jpg', 'erro' => null]
 * ou ['caminho' => null, 'erro' => 'mensagem'].
 */
function salvarImagem(array $arquivo): array
{
    if (!isset($arquivo['error']) || $arquivo['error'] === UPLOAD_ERR_NO_FILE) {
        return ['caminho' => null, 'erro' => null];
    }

    if ($arquivo['error'] !== UPLOAD_ERR_OK) {
        return ['caminho' => null, 'erro' => 'Não foi possível receber a imagem. Tente novamente.'];
    }

    if (!isset($arquivo['tmp_name'], $arquivo['size']) || !is_uploaded_file($arquivo['tmp_name'])) {
        return ['caminho' => null, 'erro' => 'Arquivo enviado inválido.'];
    }

    if ((int)$arquivo['size'] > 5 * 1024 * 1024) {
        return ['caminho' => null, 'erro' => 'Imagem muito grande. O limite é 5 MB.'];
    }

    $info = @getimagesize($arquivo['tmp_name']);
    if ($info === false || empty($info['mime'])) {
        return ['caminho' => null, 'erro' => 'O arquivo enviado não é uma imagem válida.'];
    }

    $mime = $info['mime'];
    if (!in_array($mime, ['image/jpeg', 'image/png'], true)) {
        return ['caminho' => null, 'erro' => 'Apenas imagens JPG e PNG são aceitas.'];
    }

    if (!extension_loaded('gd')) {
        return ['caminho' => null, 'erro' => 'A extensão GD do PHP precisa estar habilitada para processar imagens.'];
    }

    $imagemOriginal = @imagecreatefromstring(file_get_contents($arquivo['tmp_name']));
    if ($imagemOriginal === false) {
        return ['caminho' => null, 'erro' => 'Não foi possível processar essa imagem.'];
    }

    $larguraOriginal = imagesx($imagemOriginal);
    $alturaOriginal  = imagesy($imagemOriginal);
    if ($larguraOriginal < 1 || $alturaOriginal < 1 || ($larguraOriginal * $alturaOriginal) > 20000000) {
        imagedestroy($imagemOriginal);
        return ['caminho' => null, 'erro' => 'As dimensões da imagem são inválidas.'];
    }

    $escala = min(300 / $larguraOriginal, 300 / $alturaOriginal, 1);
    $larguraNova = max(1, (int)round($larguraOriginal * $escala));
    $alturaNova  = max(1, (int)round($alturaOriginal * $escala));

    $imagemNova = imagecreatetruecolor($larguraNova, $alturaNova);
    if ($mime === 'image/png') {
        imagealphablending($imagemNova, false);
        imagesavealpha($imagemNova, true);
        $transparente = imagecolorallocatealpha($imagemNova, 0, 0, 0, 127);
        imagefill($imagemNova, 0, 0, $transparente);
    }

    imagecopyresampled(
        $imagemNova,
        $imagemOriginal,
        0, 0, 0, 0,
        $larguraNova, $alturaNova,
        $larguraOriginal, $alturaOriginal
    );

    $nome = bin2hex(random_bytes(16)) . ($mime === 'image/png' ? '.png' : '.jpg');
    $caminhoAbsoluto = __DIR__ . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . $nome;
    $salvou = $mime === 'image/png'
        ? imagepng($imagemNova, $caminhoAbsoluto, 7)
        : imagejpeg($imagemNova, $caminhoAbsoluto, 88);

    imagedestroy($imagemOriginal);
    imagedestroy($imagemNova);

    if (!$salvou) {
        return ['caminho' => null, 'erro' => 'Não foi possível salvar a imagem no servidor.'];
    }

    return ['caminho' => 'img/' . $nome, 'erro' => null];
}

function apagarImagem(?string $caminho): void
{
    if (!$caminho || !str_starts_with($caminho, 'img/')) {
        return;
    }
    $nome = basename($caminho);
    if ($nome === '' || $nome === '.' || $nome === '..') {
        return;
    }
    $arquivo = __DIR__ . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . $nome;
    if (is_file($arquivo)) {
        @unlink($arquivo);
    }
}

function youtubeVideoId(string $url): ?string
{
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return null;
    }

    $partes = parse_url($url);
    $host = strtolower($partes['host'] ?? '');
    $host = preg_replace('/^www\./', '', $host);

    $id = null;
    if (in_array($host, ['youtube.com', 'm.youtube.com', 'music.youtube.com', 'youtube-nocookie.com'], true)) {
        parse_str($partes['query'] ?? '', $query);
        if (!empty($query['v'])) {
            $id = $query['v'];
        } elseif (!empty($partes['path'])) {
            if (preg_match('~^/(?:embed|v|shorts)/([^/?]+)~', $partes['path'], $m)) {
                $id = $m[1];
            }
        }
    } elseif ($host === 'youtu.be') {
        $id = trim($partes['path'] ?? '', '/');
        $id = explode('/', $id)[0] ?? '';
    }

    return is_string($id) && preg_match('/^[A-Za-z0-9_-]{11}$/', $id) ? $id : null;
}
