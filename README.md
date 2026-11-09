# 🎵 Sistema Musical

> Front-end de um catálogo de **artistas e músicas**, desenvolvido em HTML e CSS puros.
> Atividade das **Aulas 11, 12, 13 e 14** — Desenvolvimento de Sistemas, SENAI (turma 1ID-DS).

![HTML5](https://img.shields.io/badge/HTML5-E34F26?logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?logo=css3&logoColor=white)
![Responsivo](https://img.shields.io/badge/Layout-Responsivo-27ae60)
![Status](https://img.shields.io/badge/Status-Front--end%20conclu%C3%ADdo-3498db)

---

## 📑 Sumário

- [Sobre o projeto](#-sobre-o-projeto)
- [Tema do catálogo](#-tema-do-catálogo)
- [As 5 telas](#-as-5-telas)
- [Fluxo de navegação](#-fluxo-de-navegação)
- [Paleta de cores](#-paleta-de-cores)
- [Responsividade](#-responsividade)
- [Estrutura do repositório](#-estrutura-do-repositório)
- [Como executar](#-como-executar)
- [Checklist da atividade](#-checklist-da-atividade)
- [Equipe](#-equipe)

---

## 📖 Sobre o projeto

O **Sistema Musical** é o front-end de um catálogo onde é possível cadastrar **artistas** (os criadores) e **músicas** (as obras), além de consultar tudo em uma biblioteca com filtros.

Nesta etapa, o foco é a **interface**: estrutura semântica em HTML, estilização em um único arquivo CSS, formulários com validação nativa do navegador e layout que funciona tanto no computador quanto no celular. Os formulários já estão preparados (`method`, `action`, `name`, `required`) para serem ligados a um back-end nas próximas etapas.

## 🎼 Tema do catálogo

| Conceito da atividade | No nosso projeto |
| --------------------- | ---------------- |
| **Criador** | 🎤 Artista (nome, gênero musical, país e foto) |
| **Obra** | 🎵 Música (título, artista, álbum, gênero, capa e link do YouTube) |
| **Listagem** | 🎼 Biblioteca Musical com busca e filtros |

## 🖥️ As 5 telas

| # | Tela | Arquivo | O que faz |
|---|------|---------|-----------|
| 1 | 🔐 **Login** | [`login.html`](login.html) | Acesso ao sistema com usuário e senha (campos obrigatórios) |
| 2 | 🏠 **Menu principal** | [`index.html`](index.html) | Painel com contadores e atalhos para as demais telas |
| 3 | 🎤 **Cadastro do criador** | [`cadastro_artista.html`](cadastro_artista.html) | Formulário de artista (nome, gênero, país, foto) e tabela de artistas cadastrados |
| 4 | 🎵 **Cadastro da obra** | [`cadastro_musica.html`](cadastro_musica.html) | Formulário para cadastrar músicas e capas de álbuns |
| 5 | 🎼 **Listagem** | [`listagem_musicas.html`](listagem_musicas.html) | Biblioteca musical em tabela, com busca geral e filtros por artista e gênero |

### Destaques de cada tela

- **Login:** card centralizado, campos `required` e dica com as credenciais iniciais de teste (`admin` / `123`).
- **Menu:** cartões de estatística (artistas e músicas cadastrados) e três cartões de navegação com ícone, título e descrição.
- **Cadastro de artista:** formulário em grade de 2 colunas, campos obrigatórios marcados com `*`, upload de foto (JPG/PNG), botões de editar, ver músicas e excluir (com confirmação).
- **Cadastro de música:** formulário com os dados da obra e botão para voltar ao menu.
- **Listagem:** filtros combináveis (busca geral, artista e gênero), botões **Filtrar** e **Limpar**, tabela com capa, título, artista, álbum, gênero, link do YouTube e ações.

## 🧭 Fluxo de navegação

```text
                    ┌──────────────┐
                    │  login.html  │
                    └──────┬───────┘
                           ▼
                    ┌──────────────┐
         ┌──────────┤  index.html  ├──────────┐
         │          │    (menu)    │          │
         ▼          └──────┬───────┘          ▼
┌─────────────────────┐    │       ┌─────────────────────┐
│ cadastro_artista    │    │       │ listagem_musicas    │
│       .html         │    ▼       │       .html         │
└──────────┬──────────┘ ┌──────────────────┐ └─────────┬──┘
           │            │ cadastro_musica  │           │
           └───────────►│      .html       │◄──────────┘
                        └──────────────────┘
```

Todas as telas internas possuem o botão **⬅ Voltar ao Menu**, então é possível percorrer o sistema inteiro apenas pelos links.

## 🎨 Paleta de cores

A paleta fica centralizada em variáveis CSS no `:root` do [`styles.css`](styles.css), e todas as telas usam essas variáveis:

| Variável | Cor | Uso |
| -------- | --- | --- |
| `--primary` | `#2c3e50` | Títulos e elementos de destaque |
| `--blue` | `#3498db` | Botões primários e links |
| `--blue-dark` | `#2980b9` | Estado *hover* dos botões |
| `--green` | `#27ae60` | Mensagens de sucesso |
| `--red` | `#e74c3c` | Excluir e campos obrigatórios |
| `--red-dark` | `#c0392b` | Estado *hover* de exclusão |
| `--bg` | `#f0f2f5` | Fundo das páginas |
| `--surface` | `#ffffff` | Cartões, formulários e tabelas |
| `--border` | `#dde1e7` | Bordas |
| `--text` | `#2c3e50` | Texto principal |
| `--text-muted` | `#6c757d` | Texto secundário |

Também ficam no `:root` as sombras (`--shadow-sm`, `--shadow-md`), o raio de borda (`--radius`) e a fonte (`--font`).

## 📱 Responsividade

O projeto usa `viewport` configurada em todas as páginas e *media queries* no próprio `styles.css`:

| Largura | Ajustes |
| ------- | ------- |
| `≤ 768px` | Formulários em 1 coluna, filtros empilhados, menu em 1 coluna, tabelas com rolagem horizontal, botões e miniaturas menores |
| `≤ 600px` | Filtro de artista em coluna e cartões de estatística em 2 colunas |

> 💡 Para testar: abra qualquer tela, pressione **F12** e ative o **modo dispositivo (celular)**.

## 📂 Estrutura do repositório

```text
📦 raiz do repositório
 ┣ 📝 README.md               → Este arquivo
 ┣ 📄 cadastro_artista.html   → Cadastro do criador (artista)
 ┣ 📄 cadastro_musica.html    → Cadastro da obra (música)
 ┣ 📄 index.html              → Menu principal
 ┣ 📄 listagem_musicas.html   → Listagem / biblioteca musical
 ┣ 📄 login.html              → Tela de login
 ┗ 🎨 styles.css              → Único arquivo de estilos
```

## ▶️ Como executar

1. Clone o repositório ou baixe os arquivos:
   ```bash
   git clone https://github.com/SEU-USUARIO/NOME-DO-REPOSITORIO.git
   ```
2. Abra o arquivo **`login.html`** no navegador (duplo clique ou *Open with Live Server* no VS Code).
3. Entre com as credenciais de teste:
   - **Usuário:** `admin`
   - **Senha:** `123`
4. Navegue pelas telas usando os links e botões.

Não é necessário instalar nada: o projeto não usa frameworks, bibliotecas nem JavaScript.

## ✅ Checklist da atividade

- [x] Repositório público no GitHub, com os arquivos soltos na raiz (sem `.zip`)
- [x] 5 telas do tema: login, menu, cadastro do criador, cadastro da obra e listagem
- [x] Um único `styles.css` com a paleta do tema no `:root`
- [x] Layout funcionando no computador e no celular
- [x] Nomes dos integrantes em comentário no topo do `login.html`
- [x] Navegação entre todas as telas pelos links
- [x] Validação de campos vazios com `required`

## 👥 Equipe

| Integrante | Turma |
| ---------- | ----- |
| Vinycius Lopes Monteiro da Silva | 1ID-DS |

**Professor:** Raul Porto Lopes
**Instituição:** SENAI — Desenvolvimento de Sistemas

---

<p align="center">Feito com muito esforço pela equipe do Sistema Musical</p>
