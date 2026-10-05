<?php

declare(strict_types=1);

namespace App\Repositorios;

/**
 * Registro de intentos de login fallidos (tabla intentos_login).
 * El identificador es un hash, no el usuario ni la IP en claro.
 */
final class IntentosLoginRepositorio extends Repositorio
{
    /** Segundos que faltan de bloqueo, o 0 si no está bloqueado. */
    public function segundosDeBloqueo(string $identificador): int
    {
        $restante = $this->valor(
            'SELECT ceil(extract(epoch FROM bloqueado_hasta - now())) FROM intentos_login
             WHERE identificador = ? AND bloqueado_hasta > now()',
            [$identificador]
        );

        return $restante === false ? 0 : max(1, (int) $restante);
    }

    /**
     * Suma un fallo. Si el último fallo es más antiguo que la ventana, el
     * contador vuelve a 1; si alcanza el máximo, se fija el bloqueo.
     */
    public function registrarFallo(string $identificador, int $maximo, int $ventana, int $bloqueo): void
    {
        // En el UPDATE de PostgreSQL cada columna ve los valores ANTERIORES
        // de la fila, por eso "reinicia" se calcula una vez y se reutiliza.
        $this->ejecutar(
            'WITH limite AS (SELECT now() - make_interval(secs => :ventana) AS inicio_ventana)
             INSERT INTO intentos_login AS i (identificador, intentos, primer_intento, ultimo_intento)
             VALUES (:id, 1, now(), now())
             ON CONFLICT (identificador) DO UPDATE SET
                intentos = CASE WHEN i.ultimo_intento < (SELECT inicio_ventana FROM limite) THEN 1 ELSE i.intentos + 1 END,
                primer_intento = CASE WHEN i.ultimo_intento < (SELECT inicio_ventana FROM limite) THEN now() ELSE i.primer_intento END,
                ultimo_intento = now()',
            [':ventana' => $ventana, ':id' => $identificador]
        );

        $this->ejecutar(
            'UPDATE intentos_login
             SET bloqueado_hasta = CASE WHEN intentos >= :maximo THEN now() + make_interval(secs => :bloqueo) END
             WHERE identificador = :id',
            [':maximo' => $maximo, ':bloqueo' => $bloqueo, ':id' => $identificador]
        );
    }

    public function limpiar(string $identificador): void
    {
        $this->ejecutar('DELETE FROM intentos_login WHERE identificador = ?', [$identificador]);
    }
}
