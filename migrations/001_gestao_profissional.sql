-- Incremental: preserva tabelas e registros existentes. Banco: mindly.
USE mindly;
CREATE TABLE IF NOT EXISTS versoes_registros_prontuario (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    registro_id BIGINT UNSIGNED NOT NULL,
    autor_id BIGINT UNSIGNED NOT NULL,
    observacoes TEXT NOT NULL,
    salvo_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_versao_registro FOREIGN KEY (registro_id) REFERENCES registros_prontuario(id) ON DELETE RESTRICT,
    CONSTRAINT fk_versao_autor FOREIGN KEY (autor_id) REFERENCES psicologos(usuario_id) ON DELETE RESTRICT,
    KEY idx_versao_registro (registro_id, salvo_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- Triggers instalados uma única vez; execute este arquivo uma vez por instalação.
DELIMITER $$
CREATE TRIGGER mindly_aviso_status_conta AFTER UPDATE ON usuarios FOR EACH ROW
BEGIN
    IF NEW.papel = 'psicologo' AND OLD.status <> NEW.status THEN
        INSERT INTO notificacoes(usuario_id,tipo,titulo,mensagem)
        VALUES(NEW.id,'conta','Status da conta atualizado',CONCAT('O status da sua conta foi alterado para: ',NEW.status,'.'));
    END IF;
END$$
CREATE TRIGGER mindly_aviso_verificacao AFTER UPDATE ON psicologos FOR EACH ROW
BEGIN
    IF OLD.status_verificacao <> NEW.status_verificacao THEN
        INSERT INTO notificacoes(usuario_id,tipo,titulo,mensagem)
        VALUES(NEW.usuario_id,'conta','Verificação profissional atualizada',CONCAT('O status da verificação foi alterado para: ',NEW.status_verificacao,'.'));
    END IF;
END$$
DELIMITER ;
