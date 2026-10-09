-- =====================================================================
-- Migración 2026-10-09: permisos por usuario.
--
-- Agrega la columna usuarios.permisos a una base creada con una versión
-- anterior de esquema.sql. Ejecutar UNA vez (Supabase → SQL Editor →
-- pegar y "Run") ANTES de publicar el código nuevo en el servidor.
--
-- Para no quitarle a nadie lo que ya podía hacer:
--   * administradores → "Editar equipo" + "Cambio de contraseña";
--   * usuarios        → "Cambio de contraseña".
-- "Crear nuevo usuario" y "Editar usuario" no se asignan a nadie: desde
-- ahora solo la cuenta principal (sistemas@consultoriasdebia.com.co)
-- gestiona usuarios, y ella decide si delega esos permisos.
-- =====================================================================

BEGIN;

SET LOCAL search_path TO inventario;

ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS permisos text[] NOT NULL DEFAULT '{}';

ALTER TABLE usuarios DROP CONSTRAINT IF EXISTS usuarios_permisos_validos;
ALTER TABLE usuarios ADD CONSTRAINT usuarios_permisos_validos CHECK (
    permisos <@ ARRAY['EDITAR_EQUIPO', 'CREAR_USUARIO', 'EDITAR_USUARIO', 'CAMBIAR_CONTRASENA']::text[]
);

UPDATE usuarios
SET permisos = CASE rol
        WHEN 'ADMIN' THEN ARRAY['EDITAR_EQUIPO', 'CAMBIAR_CONTRASENA']
        ELSE ARRAY['CAMBIAR_CONTRASENA']
    END
WHERE permisos = '{}';

COMMIT;

-- Verificación: debe listar cada cuenta con sus permisos.
SELECT usuario, correo, rol, permisos FROM inventario.usuarios ORDER BY id;
