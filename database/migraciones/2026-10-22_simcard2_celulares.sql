-- =====================================================================
-- Migración 2026-10-22: SIMCARD 2 relacionada de los celulares.
--
-- Agrega la columna equipos.simcard2 para los celulares con dos SIM.
-- Igual que equipos.simcard, solo se diligencia en equipos de tipo
-- Celulares ('CE'); en los demás queda NULL. Ejecutar UNA vez (Supabase
-- → SQL Editor → pegar y "Run") ANTES de publicar el código nuevo en el
-- servidor.
--
-- No requiere permisos nuevos: dsi_app tiene acceso a nivel de tabla.
-- =====================================================================

BEGIN;

SET LOCAL search_path TO inventario;

ALTER TABLE equipos ADD COLUMN IF NOT EXISTS simcard2 varchar(20);

COMMIT;

-- Verificación: debe listar las dos columnas de SIMCARD.
SELECT column_name, data_type, character_maximum_length
FROM information_schema.columns
WHERE table_schema = 'inventario' AND table_name = 'equipos' AND column_name IN ('simcard', 'simcard2')
ORDER BY column_name;
