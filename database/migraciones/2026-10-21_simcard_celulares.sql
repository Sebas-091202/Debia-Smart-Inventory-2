-- =====================================================================
-- Migración 2026-10-21: SIMCARD relacionada de los celulares.
--
-- Agrega la columna equipos.simcard (número de celular de la SIMCARD
-- asignada al equipo). Solo se diligencia en equipos de tipo Celulares
-- ('CE'); en los demás queda NULL. Ejecutar UNA vez (Supabase → SQL
-- Editor → pegar y "Run") ANTES de publicar el código nuevo en el
-- servidor.
--
-- No requiere permisos nuevos: dsi_app tiene acceso a nivel de tabla.
-- =====================================================================

BEGIN;

SET LOCAL search_path TO inventario;

ALTER TABLE equipos ADD COLUMN IF NOT EXISTS simcard varchar(20);

COMMIT;

-- Verificación: debe listar la columna nueva.
SELECT column_name, data_type, character_maximum_length
FROM information_schema.columns
WHERE table_schema = 'inventario' AND table_name = 'equipos' AND column_name = 'simcard';
