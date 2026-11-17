Create table usuarios (
  id serial primary key,
  nome_usuario varchar(100) not null,
  email varchar(100) not null,
  senha_hash varchar(100),
  data_criacao timestamp

);

select * from usuarios;

create table bibliotea(
  id serial primary key,
    usuario_id BIGINT NOT NULL,
    jogo_id_api INT NOT NULL,
    data_adicionado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_biblioteca_usuario 
        FOREIGN KEY (usuario_id) 
        REFERENCES usuarios(id) 
        ON DELETE CASCADE,
        
    CONSTRAINT uk_usuario_jogo_biblioteca 
        UNIQUE (usuario_id, jogo_id_api)
);

CREATE TABLE favoritos (
    id BIGSERIAL PRIMARY KEY,
    usuario_id BIGINT NOT NULL,
    jogo_id_api INT NOT NULL,
    data_adicionado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_favoritos_usuario 
        FOREIGN KEY (usuario_id) 
        REFERENCES usuarios(id) 
        ON DELETE CASCADE,
        
    -- Garante que o mesmo jogo não seja favoritado duas vezes pelo mesmo usuário
    CONSTRAINT uk_usuario_jogo_favoritos 
        UNIQUE (usuario_id, jogo_id_api)
);


CREATE TABLE avaliacoes (
    id BIGSERIAL PRIMARY KEY,
    usuario_id BIGINT NOT NULL,
    jogo_id_api INT NOT NULL,
    nota INT NOT NULL CHECK (nota BETWEEN 1 AND 5),
    comentario TEXT,
    data TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_avaliacoes_usuario 
        FOREIGN KEY (usuario_id) 
        REFERENCES usuarios(id) 
        ON DELETE CASCADE,
        
    -- Garante que cada usuário só possa avaliar o mesmo jogo uma vez
    CONSTRAINT uk_usuario_jogo_avaliacao 
        UNIQUE (usuario_id, jogo_id_api)
);


GRANT ALL ON TABLE public.usuarios, public.favoritos, public.avaliacoes, public.bibliotea TO anon, authenticated;
GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO anon, authenticated;

ALTER TABLE public.favoritos ENABLE ROW LEVEL SECURITY;

CREATE POLICY "Permitir acesso total em favoritos"
ON public.favoritos

GRANT ALL ON TABLE public.usuarios TO anon, authenticated;
GRANT ALL ON TABLE public.bibliotea TO anon, authenticated;
GRANT ALL ON TABLE public.favoritos TO anon, authenticated;
GRANT ALL ON TABLE public.avaliacoes TO anon, authenticated;
GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO anon, authenticated;

DROP POLICY IF EXISTS "Acesso total usuarios" ON public.usuarios;
CREATE POLICY "Acesso total usuarios" ON public.usuarios
FOR ALL TO anon, authenticated USING (true) WITH CHECK (true);

DROP POLICY IF EXISTS "Acesso total bibliotea" ON public.bibliotea;
CREATE POLICY "Acesso total bibliotea" ON public.bibliotea
FOR ALL TO anon, authenticated USING (true) WITH CHECK (true);


