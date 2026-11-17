table_name,column_name,data_type,is_nullable
avaliacoes,id,bigint,NO
avaliacoes,usuario_id,bigint,NO
avaliacoes,jogo_id_api,integer,NO
avaliacoes,nota,integer,NO
avaliacoes,comentario,text,YES
avaliacoes,data,timestamp without time zone,YES
bibliotea,id,integer,NO
bibliotea,usuario_id,bigint,NO
bibliotea,jogo_id_api,integer,NO
bibliotea,data_adicionado,timestamp without time zone,YES
favoritos,id,bigint,NO
favoritos,usuario_id,bigint,NO
favoritos,jogo_id_api,integer,NO
favoritos,data_adicionado,timestamp without time zone,YES
usuarios,id,integer,NO
usuarios,nome_usuario,character varying,NO
usuarios,email,character varying,NO
usuarios,senha_hash,character varying,YES
usuarios,data_criacao,timestamp without time zone,YES
