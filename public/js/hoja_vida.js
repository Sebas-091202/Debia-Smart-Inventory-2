/**
 * Descarga de la hoja de vida en PDF (jsPDF + autoTable, servidos desde js/vendor).
 *
 * Los datos se leen del bloque <script type="application/json" id="datos-hoja-vida">
 * que genera el servidor ya escapado; nunca se interpolan en código JavaScript.
 */
(() => {
    'use strict';

    const CAMPOS_FICHA = [
        ['Identificador', 'identificador'],
        ['Tipo', 'tipo'],
        ['Marca', 'marca'],
        ['Ubicación', 'ubicacion'],
        ['Asignado a', 'asignado_a'],
        ['Serial', 'serial'],
        ['Procesador', 'procesador'],
        ['RAM', 'ram'],
        ['Disco C', 'disco'],
        ['Disco D', 'disco2'],
        ['Estado', 'estado'],
    ];

    const COLUMNAS_HISTORIAL = [
        ['Fecha', 'fecha'],
        ['Responsable', 'responsable'],
        ['Tipo', 'tipo_mantenimiento'],
        ['Descripción', 'descripcion'],
        ['Estado', 'estado'],
        ['Observaciones', 'observaciones'],
    ];

    const texto = (valor) => (valor ?? '').toString();

    /** Nombre de archivo seguro (sin caracteres no válidos en Windows/macOS). */
    const nombreArchivo = (identificador) =>
        'Hoja_Vida_' + texto(identificador).replace(/[^\w.-]+/g, '_') + '.pdf';

    function generarPdf({ equipo, historial }) {
        const { jsPDF } = window.jspdf;
        const documento = new jsPDF();

        documento.setFontSize(16);
        documento.text('HOJA DE VIDA DEL EQUIPO', 50, 15);
        documento.setFontSize(10);

        let y = 30;
        for (const [etiqueta, campo] of CAMPOS_FICHA) {
            documento.text(`${etiqueta}: ${texto(equipo[campo])}`, 10, y);
            y += 7;
        }

        documento.autoTable({
            startY: y + 3,
            head: [COLUMNAS_HISTORIAL.map(([etiqueta]) => etiqueta)],
            body: historial.map((fila) => COLUMNAS_HISTORIAL.map(([, campo]) => texto(fila[campo]))),
        });

        documento.save(nombreArchivo(equipo.identificador));
    }

    document.addEventListener('DOMContentLoaded', () => {
        const boton = document.getElementById('btnDescargarPdf');
        const bloqueDatos = document.getElementById('datos-hoja-vida');

        if (!boton || !bloqueDatos) {
            return;
        }

        const datos = JSON.parse(bloqueDatos.textContent);
        boton.addEventListener('click', () => generarPdf(datos));
    });
})();
