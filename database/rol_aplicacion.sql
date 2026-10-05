-- =====================================================================
-- Rol de mínimo privilegio para la aplicación (recomendado).
--
-- La app solo necesita leer y escribir filas: no crear ni borrar tablas.
-- Conectarse con este rol en lugar de "postgres" limita el daño si las
-- credenciales del servidor se filtraran.
--
-- Uso (Supabase → SQL Editor), DESPUÉS de esquema.sql:
--   1. Reemplazar CAMBIAR_POR_UNA_CLAVE_LARGA por una contraseña aleatoria
--      (p. ej. 32+ caracteres de un gestor de contraseñas). No la guardes
--      en el repositorio.
--   2. Ejecutar.
--   3. En el .env del servidor:
--        DB_USER=dsi_app.<referencia-del-proyecto>   (con el pooler)
--        DB_PASS=<la contraseña elegida>
-- =====================================================================

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'dsi_app') THEN
        CREATE ROLE dsi_app LOGIN PASSWORD 'CAMBIAR_POR_UNA_CLAVE_LARGA';
    END IF;
END
$$;

GRANT USAGE ON SCHEMA inventario TO dsi_app;
GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA inventario TO dsi_app;
GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA inventario TO dsi_app;
ALTER ROLE dsi_app SET search_path TO inventario;

-- Con RLS activado, un rol que no es dueño de la tabla necesita una
-- política explícita. Solo dsi_app la recibe; anon/authenticated no.
DO $$
DECLARE
    tabla text;
BEGIN
    FOREACH tabla IN ARRAY ARRAY[
        'usuarios', 'equipos', 'mantenimientos', 'repuestos',
        'mantenimiento_repuestos', 'logs_sistema', 'intentos_login'
    ] LOOP
        IF NOT EXISTS (
            SELECT 1 FROM pg_policies
            WHERE schemaname = 'inventario' AND tablename = tabla AND policyname = 'aplicacion_acceso_total'
        ) THEN
            EXECUTE format(
                'CREATE POLICY aplicacion_acceso_total ON inventario.%I FOR ALL TO dsi_app USING (true) WITH CHECK (true)',
                tabla
            );
        END IF;
    END LOOP;
END
$$;
