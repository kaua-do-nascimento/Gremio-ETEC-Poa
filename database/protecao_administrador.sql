-- ============================================================
-- PROTEÇÃO DO ADMINISTRADOR (gatilhos / triggers)
--
-- Impede, DIRETO NO BANCO, que um usuário com o perfil
-- "administrador" seja:
--   * apagado da tabela usuarios (DELETE);
--   * marcado como excluído (deleted_at) ou desativado (ativo = 0);
--   * desvinculado do perfil "administrador".
-- Vale para a API, para o phpMyAdmin e para qualquer outro cliente.
--
-- Como aplicar (escolha UMA forma):
--   1) Terminal:  php Backend/tests/proteger-admin.php
--   2) phpMyAdmin: selecione o banco gremio_etec_poa > aba "Importar"
--      > escolha este arquivo > Executar.
--
-- Para REMOVER a proteção (somente se realmente precisar):
--   DROP TRIGGER IF EXISTS trg_usuarios_admin_no_delete;
--   DROP TRIGGER IF EXISTS trg_usuarios_admin_no_disable;
--   DROP TRIGGER IF EXISTS trg_usuario_perfil_admin_no_delete;
--   DROP TRIGGER IF EXISTS trg_usuario_perfil_admin_no_update;
-- ============================================================

DROP TRIGGER IF EXISTS trg_usuarios_admin_no_delete;
DROP TRIGGER IF EXISTS trg_usuarios_admin_no_disable;
DROP TRIGGER IF EXISTS trg_usuario_perfil_admin_no_delete;
DROP TRIGGER IF EXISTS trg_usuario_perfil_admin_no_update;

DELIMITER $$

CREATE TRIGGER trg_usuarios_admin_no_delete
BEFORE DELETE ON usuarios
FOR EACH ROW
BEGIN
    IF EXISTS (
        SELECT 1 FROM usuario_perfil up
        INNER JOIN perfis p ON p.id = up.perfil_id
        WHERE up.usuario_id = OLD.id AND p.nome = 'administrador'
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Administrador protegido: nao pode ser excluido.';
    END IF;
END$$

CREATE TRIGGER trg_usuarios_admin_no_disable
BEFORE UPDATE ON usuarios
FOR EACH ROW
BEGIN
    IF (
        (NEW.deleted_at IS NOT NULL AND OLD.deleted_at IS NULL)
        OR (NEW.ativo = 0 AND OLD.ativo = 1)
    ) AND EXISTS (
        SELECT 1 FROM usuario_perfil up
        INNER JOIN perfis p ON p.id = up.perfil_id
        WHERE up.usuario_id = OLD.id AND p.nome = 'administrador'
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Administrador protegido: nao pode ser excluido nem suspenso.';
    END IF;
END$$

CREATE TRIGGER trg_usuario_perfil_admin_no_delete
BEFORE DELETE ON usuario_perfil
FOR EACH ROW
BEGIN
    IF EXISTS (
        SELECT 1 FROM perfis p
        WHERE p.id = OLD.perfil_id AND p.nome = 'administrador'
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Administrador protegido: o perfil administrador nao pode ser removido.';
    END IF;
END$$

CREATE TRIGGER trg_usuario_perfil_admin_no_update
BEFORE UPDATE ON usuario_perfil
FOR EACH ROW
BEGIN
    IF (NEW.perfil_id <> OLD.perfil_id OR NEW.usuario_id <> OLD.usuario_id) AND EXISTS (
        SELECT 1 FROM perfis p
        WHERE p.id = OLD.perfil_id AND p.nome = 'administrador'
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Administrador protegido: o perfil administrador nao pode ser alterado.';
    END IF;
END$$

DELIMITER ;
