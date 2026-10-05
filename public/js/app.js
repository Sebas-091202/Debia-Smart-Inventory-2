/**
 * Comportamiento común a todas las vistas.
 *
 * Se engancha a atributos data-* en lugar de onclick="..." porque el CSP
 * de la aplicación (script-src 'self') bloquea todo JavaScript en línea.
 *
 *   #btnToggleSidebar        Abre/cierra el menú lateral.
 *   [data-colapsable="X"]    Panel plegable (p. ej. la Guía de Abreviaciones);
 *                            su estado se recuerda en localStorage con la clave X.
 *   form[data-confirmar]     Pide confirmación antes de enviar.
 *   [data-alerta]            Mensaje que se cierra solo o con su botón.
 */
(() => {
    'use strict';

    const ANCHO_MAXIMO_MOVIL = 768;
    const DURACION_ALERTA_MS = 6000;

    const almacenamiento = {
        leer(clave) {
            try {
                return window.localStorage.getItem(clave);
            } catch {
                return null; // Modo privado o almacenamiento bloqueado.
            }
        },
        guardar(clave, valor) {
            try {
                window.localStorage.setItem(clave, valor);
            } catch {
                /* Sin almacenamiento: el panel simplemente no recuerda su estado. */
            }
        },
    };

    function alternarMenuLateral() {
        const esMovil = window.innerWidth <= ANCHO_MAXIMO_MOVIL;
        document.body.classList.toggle(esMovil ? 'sidebar-open' : 'sidebar-collapsed');
    }

    function inicializarColapsable(panel) {
        const boton = panel.querySelector('[data-colapsable-boton]');
        const clave = panel.dataset.colapsable;

        if (!boton) {
            return;
        }

        const aplicar = (abierto) => {
            panel.classList.toggle('abierto', abierto);
            boton.setAttribute('aria-expanded', String(abierto));
        };

        aplicar(almacenamiento.leer(clave) === '1');

        boton.addEventListener('click', () => {
            const abierto = !panel.classList.contains('abierto');
            aplicar(abierto);
            almacenamiento.guardar(clave, abierto ? '1' : '0');
        });
    }

    function inicializarConfirmacion(formulario) {
        formulario.addEventListener('submit', (evento) => {
            if (!window.confirm(formulario.dataset.confirmar)) {
                evento.preventDefault();
            }
        });
    }

    function inicializarAlerta(alerta) {
        const cerrar = () => alerta.remove();
        alerta.querySelector('[data-alerta-cerrar]')?.addEventListener('click', cerrar);
        window.setTimeout(cerrar, DURACION_ALERTA_MS);
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('btnToggleSidebar')?.addEventListener('click', alternarMenuLateral);
        document.querySelectorAll('[data-colapsable]').forEach(inicializarColapsable);
        document.querySelectorAll('form[data-confirmar]').forEach(inicializarConfirmacion);
        document.querySelectorAll('[data-alerta]').forEach(inicializarAlerta);
    });
})();
