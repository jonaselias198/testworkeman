SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

SET default_tablespace = '';

SET default_table_access_method = heap;

CREATE UNLOGGED TABLE IF NOT EXISTS clients (
    "id"                SERIAL,
    "limit"             INT NOT NULL,
    "balance"           INT DEFAULT 0,
    "client_id"    INT NOT null,
    PRIMARY KEY (id)
);

CREATE UNLOGGED TABLE IF NOT EXISTS transactions (
    "id"           SERIAL,
    "value"        INT NOT NULL,
    "type"         VARCHAR(1) NOT NULL,
    "description"  VARCHAR(10) NOT NULL,
    "created_at"   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    "client_id"    INT NOT NULL
);

CREATE INDEX ids_transacoes_ids_cliente_id ON transactions (client_id);
CREATE INDEX ids_saldos_ids_cliente_id ON clients (client_id);


DO $$
BEGIN
	INSERT INTO clients (client_id, "limit", balance)
	VALUES (1,   1000 * 100, 0),
		   (2,    800 * 100, 0),
		   (3,  10000 * 100, 0),
		   (4, 100000 * 100, 0),
		   (5,   5000 * 100, 0);
END;
$$;


