<?php

require_once __DIR__ . '/../models/Publicacao.php';
require_once __DIR__ . '/../services/NotificacaoService.php';
require_once __DIR__ . '/../services/AuditoriaService.php';

class PublicacaoController
{
    private Publicacao $publicacaoModel;

    public function __construct()
    {
        $this->publicacaoModel = new Publicacao();
    }

    /**
     * Calcula o tempo estimado de leitura em minutos, com base na
     * contagem de palavras (~200 palavras por minuto). Mesma fórmula
     * que já existia no front-end mockado, agora no backend.
     */
    private function calcularTempoLeitura(string $conteudo): int
    {
        $totalPalavras = count(preg_split('/\s+/', trim($conteudo), -1, PREG_SPLIT_NO_EMPTY));
        return max(1, (int) ceil($totalPalavras / 200));
    }

    /**
     * Formata os campos agregados de uma linha do banco para o
     * formato que o front-end espera (mesmo "shape" do mock antigo,
     * dentro do que o banco real suporta).
     */
    private function formatarPublicacao(array $linha): array
    {
        return [
            'id' => (int) $linha['id'],
            'titulo' => $linha['titulo'],
            'conteudo' => $linha['conteudo'],
            'imagem_url' => $linha['imagem_url'],
            'publicado_em' => $linha['publicado_em'],
            'status' => $linha['status'],
            'tempo_leitura' => $this->calcularTempoLeitura($linha['conteudo']),
            'categoria' => $linha['categoria_nome'],
            'autor' => [
                'id' => (int) $linha['autor_id'],
                'nome' => $linha['autor_nome'],
                'foto_url' => $linha['autor_foto'],
            ],
            'total_curtidas' => (int) $linha['total_curtidas'],
            'curtido_por_mim' => (bool) $linha['curtido_por_mim'],
        ];
    }

    /**
     * Lista as publicações publicadas (feed).
     */
    public function listar(int $usuarioId): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $linhas = $this->publicacaoModel->listarPublicadas($usuarioId);
            $publicacoes = array_map([$this, 'formatarPublicacao'], $linhas);

            $this->responder(200, [
                'success' => true,
                'publicacoes' => $publicacoes
            ]);
        } catch (PDOException $e) {
            $this->responder(500, [
                'success' => false,
                'message' => 'Erro ao listar publicações.'
            ]);
        }
    }

    /**
     * Busca uma publicação específica.
     */
    public function buscarPorId(int $id, int $usuarioId): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $linha = $this->publicacaoModel->buscarPorId($id, $usuarioId);

            if (!$linha) {
                $this->responder(404, [
                    'success' => false,
                    'message' => 'Publicação não encontrada.'
                ]);
                return;
            }

            $this->responder(200, [
                'success' => true,
                'publicacao' => $this->formatarPublicacao($linha)
            ]);
        } catch (PDOException $e) {
            $this->responder(500, [
                'success' => false,
                'message' => 'Erro ao buscar publicação.'
            ]);
        }
    }

    /**
     * Lista as categorias disponíveis (para o seletor do formulário).
     */
    public function listarCategorias(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $categorias = $this->publicacaoModel->listarCategorias();

            $this->responder(200, [
                'success' => true,
                'categorias' => $categorias
            ]);
        } catch (PDOException $e) {
            $this->responder(500, [
                'success' => false,
                'message' => 'Erro ao listar categorias.'
            ]);
        }
    }

    /**
     * Cria uma nova publicação (somente administrador).
     */
    public function criar(int $usuarioId, array $dados): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $titulo = trim((string) ($dados['titulo'] ?? ''));
        $conteudo = trim((string) ($dados['conteudo'] ?? ''));
        $categoriaId = (int) ($dados['categoria_id'] ?? 0);

        if ($titulo === '' || mb_strlen($titulo) > 150) {
            $this->responder(400, [
                'success' => false,
                'message' => 'Título é obrigatório e deve ter no máximo 150 caracteres.'
            ]);
            return;
        }

        if ($conteudo === '') {
            $this->responder(400, [
                'success' => false,
                'message' => 'Conteúdo é obrigatório.'
            ]);
            return;
        }

        try {
            if (!$this->publicacaoModel->categoriaExiste($categoriaId)) {
                $this->responder(400, [
                    'success' => false,
                    'message' => 'Categoria inválida.'
                ]);
                return;
            }

            $id = $this->publicacaoModel->criar($usuarioId, $categoriaId, $titulo, $conteudo);

            NotificacaoService::notificarNovaPublicacao($usuarioId, $id, $titulo);
            AuditoriaService::log($usuarioId, 'criacao', 'publicacoes', "Publicação \"{$titulo}\" criada (#{$id}).");

            $this->responder(201, [
                'success' => true,
                'message' => 'Publicação criada com sucesso.',
                'id' => $id
            ]);
        } catch (PDOException $e) {
            $this->responder(500, [
                'success' => false,
                'message' => 'Erro ao criar publicação.'
            ]);
        }
    }

    /**
     * Atualiza uma publicação existente (somente administrador).
     */
    public function atualizar(int $id, array $dados, int $usuarioId): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $titulo = trim((string) ($dados['titulo'] ?? ''));
        $conteudo = trim((string) ($dados['conteudo'] ?? ''));
        $categoriaId = (int) ($dados['categoria_id'] ?? 0);

        if ($titulo === '' || mb_strlen($titulo) > 150) {
            $this->responder(400, [
                'success' => false,
                'message' => 'Título é obrigatório e deve ter no máximo 150 caracteres.'
            ]);
            return;
        }

        if ($conteudo === '') {
            $this->responder(400, [
                'success' => false,
                'message' => 'Conteúdo é obrigatório.'
            ]);
            return;
        }

        try {
            if (!$this->publicacaoModel->categoriaExiste($categoriaId)) {
                $this->responder(400, [
                    'success' => false,
                    'message' => 'Categoria inválida.'
                ]);
                return;
            }

            $atualizou = $this->publicacaoModel->atualizar($id, $categoriaId, $titulo, $conteudo);

            if (!$atualizou) {
                $this->responder(404, [
                    'success' => false,
                    'message' => 'Publicação não encontrada.'
                ]);
                return;
            }

            AuditoriaService::log($usuarioId, 'atualizacao', 'publicacoes', "Publicação #{$id} atualizada.");

            $this->responder(200, [
                'success' => true,
                'message' => 'Publicação atualizada com sucesso.'
            ]);
        } catch (PDOException $e) {
            $this->responder(500, [
                'success' => false,
                'message' => 'Erro ao atualizar publicação.'
            ]);
        }
    }

    /**
     * Exclui (soft delete) uma publicação (somente administrador).
     */
    public function excluir(int $id, int $usuarioId): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $excluiu = $this->publicacaoModel->excluir($id);

            if (!$excluiu) {
                $this->responder(404, [
                    'success' => false,
                    'message' => 'Publicação não encontrada.'
                ]);
                return;
            }

            AuditoriaService::log($usuarioId, 'exclusao', 'publicacoes', "Publicação #{$id} excluída.");

            $this->responder(200, [
                'success' => true,
                'message' => 'Publicação excluída com sucesso.'
            ]);
        } catch (PDOException $e) {
            $this->responder(500, [
                'success' => false,
                'message' => 'Erro ao excluir publicação.'
            ]);
        }
    }

    /**
     * Alterna a curtida do usuário autenticado numa publicação
     * (qualquer usuário autenticado pode curtir).
     */
    public function reagir(int $id, int $usuarioId): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            if (!$this->publicacaoModel->existe($id)) {
                $this->responder(404, [
                    'success' => false,
                    'message' => 'Publicação não encontrada.'
                ]);
                return;
            }

            $tipoReacaoId = $this->publicacaoModel->idTipoReacaoCurtida();
            $curtiu = $this->publicacaoModel->alternarCurtida($usuarioId, $id, $tipoReacaoId);

            $this->responder(200, [
                'success' => true,
                'curtido' => $curtiu
            ]);
        } catch (RuntimeException $e) {
            $this->responder(500, [
                'success' => false,
                'message' => $e->getMessage()
            ]);
        } catch (PDOException $e) {
            $this->responder(500, [
                'success' => false,
                'message' => 'Erro ao registrar reação.'
            ]);
        }
    }

    private function responder(int $status, array $dados): void
    {
        http_response_code($status);

        echo json_encode(
            $dados,
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
        );
    }
}
