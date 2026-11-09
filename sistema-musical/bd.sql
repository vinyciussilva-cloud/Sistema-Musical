-- Sistema Musical - Fase 2
-- Execute este arquivo no phpMyAdmin ou no cliente MySQL.

CREATE DATABASE IF NOT EXISTS sistema_musical_v2
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE sistema_musical_v2;

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS artistas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    genero VARCHAR(50) DEFAULT NULL,
    pais VARCHAR(50) DEFAULT NULL,
    imagem VARCHAR(255) DEFAULT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS musicas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    artista_id INT NOT NULL,
    titulo VARCHAR(100) NOT NULL,
    album VARCHAR(100) DEFAULT NULL,
    genero VARCHAR(50) DEFAULT NULL,
    youtube_link VARCHAR(255) DEFAULT NULL,
    letra TEXT DEFAULT NULL,
    imagem_capa VARCHAR(255) DEFAULT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_musicas_artistas
      FOREIGN KEY (artista_id) REFERENCES artistas(id)
      ON UPDATE CASCADE
      ON DELETE RESTRICT,
    INDEX idx_musicas_artista (artista_id),
    INDEX idx_musicas_titulo (titulo),
    INDEX idx_musicas_genero (genero)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Usuário de demonstração definido no roteiro: admin / 123.
-- Hash gerado com password_hash('123', PASSWORD_BCRYPT).
INSERT INTO usuarios (usuario, senha)
VALUES ('admin', '$2y$12$zw1c2aDgt4HHnAzXc3g3dOV6Mn96Dm2UaSIketIhYe1xmtuaJG6qa')
ON DUPLICATE KEY UPDATE usuario = VALUES(usuario);