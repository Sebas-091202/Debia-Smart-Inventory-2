/**
 * Descarga de la hoja de vida en PDF (jsPDF + autoTable, servidos desde js/vendor).
 *
 * Los datos se leen del bloque <script type="application/json" id="datos-hoja-vida">
 * que genera el servidor ya escapado; nunca se interpolan en código JavaScript.
 * El logo (data-logo del botón) se descarga del mismo sitio; si no carga, el
 * PDF se genera igual, sin él.
 */
(() => {
    'use strict';

    // Medidas en milímetros sobre una hoja A4 (210 × 297).
    const ANCHO_PAGINA = 210;
    const ALTO_PAGINA = 297;
    const MARGEN = 15;
    const ANCHO_UTIL = ANCHO_PAGINA - 2 * MARGEN;
    const ANCHO_LOGO = 50;

    // Colores de la aplicación (RGB).
    const COLOR = {
        oscuro: [17, 24, 39],
        rojo: [196, 12, 12],
        azul: [37, 99, 235],
        gris: [107, 114, 128],
        grisClaro: [243, 244, 246],
        borde: [229, 231, 235],
        blanco: [255, 255, 255],
    };

    /** Mismos colores que los badges de estado (componentes.css). */
    const COLORES_ESTADO = {
        'Activo': [22, 163, 74],
        'Bodega': [65, 88, 133],
        'En reparación': [217, 119, 6],
        'Dado de baja': [220, 38, 38],
        'Finalizado': [15, 118, 110],
    };

    const COLORES_TIPO = { Preventivo: COLOR.azul, Correctivo: COLOR.rojo };

    const texto = (valor) => (valor ?? '').toString().trim();
    const oVacio = (valor, alternativa) => texto(valor) || alternativa;

    /** Nombre de archivo seguro (sin caracteres no válidos en Windows/macOS). */
    const nombreArchivo = (identificador) =>
        'Hoja_Vida_' + texto(identificador).replace(/[^\w.-]+/g, '_') + '.pdf';

    /** "22/10/2026 10:45" (armado a mano: las fuentes del PDF no traen todos los espacios de Intl). */
    function fechaHoraActual() {
        const ahora = new Date();
        const dos = (numero) => String(numero).padStart(2, '0');

        return `${dos(ahora.getDate())}/${dos(ahora.getMonth() + 1)}/${ahora.getFullYear()} ${dos(ahora.getHours())}:${dos(ahora.getMinutes())}`;
    }

    /** Bytes del logo, o null si no se pudo descargar. */
    async function cargarLogo(url) {
        if (!url) {
            return null;
        }

        try {
            const respuesta = await fetch(url, { credentials: 'same-origin' });

            return respuesta.ok ? new Uint8Array(await respuesta.arrayBuffer()) : null;
        } catch {
            return null;
        }
    }

    // ---------------------------------------------------------------
    // Etiquetas de color (estado). La tabla pinta el fondo de la celda
    // después de willDrawCell, así que la etiqueta se dibuja en
    // didDrawCell y el texto original se vacía para no duplicarlo.
    // ---------------------------------------------------------------
    const etiqueta = (valor) => ({ content: texto(valor) || '—', etiqueta: COLORES_ESTADO[texto(valor)] || COLOR.gris });

    function prepararEtiqueta(data) {
        if (data.cell.raw?.etiqueta) {
            data.cell.text = [''];
            data.cell.styles.minCellHeight = 8;
        }
    }

    function dibujarEtiqueta(data) {
        const { cell, doc } = data;

        if (!cell.raw?.etiqueta) {
            return;
        }

        const valor = cell.raw.content;
        const alto = 5;
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(7.5);
        const ancho = doc.getTextWidth(valor) + 5;
        const x = cell.x + cell.padding('left');
        const y = cell.styles.valign === 'top' ? cell.y + cell.padding('top') - 0.5 : cell.y + (cell.height - alto) / 2;

        doc.setFillColor(...cell.raw.etiqueta);
        doc.roundedRect(x, y, ancho, alto, 2.5, 2.5, 'F');
        doc.setTextColor(...COLOR.blanco);
        doc.text(valor, x + 2.5, y + alto / 2, { baseline: 'middle' });
    }

    // ---------------------------------------------------------------
    // Secciones del documento. Cada una recibe la posición vertical
    // donde empieza y devuelve dónde puede empezar la siguiente.
    // ---------------------------------------------------------------
    function encabezado(doc, { equipo }, logo) {
        let altoLogo = 0;

        if (logo) {
            const { width, height } = doc.getImageProperties(logo);
            altoLogo = (ANCHO_LOGO * height) / width;
            doc.addImage(logo, 'JPEG', MARGEN, 12, ANCHO_LOGO, altoLogo);
        }

        const derecha = ANCHO_PAGINA - MARGEN;
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(15);
        doc.setTextColor(...COLOR.oscuro);
        doc.text('HOJA DE VIDA DEL EQUIPO', derecha, 19, { align: 'right' });

        doc.setFontSize(20);
        doc.setTextColor(...COLOR.rojo);
        doc.text(texto(equipo.identificador), derecha, 28, { align: 'right' });

        doc.setFont('helvetica', 'normal');
        doc.setFontSize(8.5);
        doc.setTextColor(...COLOR.gris);
        doc.text(`Generado el ${fechaHoraActual()}`, derecha, 34, { align: 'right' });

        const linea = Math.max(12 + altoLogo, 36) + 3;
        doc.setDrawColor(...COLOR.rojo);
        doc.setLineWidth(0.8);
        doc.line(MARGEN, linea, derecha, linea);

        return linea + 11;
    }

    function tituloSeccion(doc, titulo, y) {
        doc.setFillColor(...COLOR.rojo);
        doc.rect(MARGEN, y - 3.8, 1.4, 5, 'F');
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(11);
        doc.setTextColor(...COLOR.oscuro);
        doc.text(titulo, MARGEN + 4, y);

        return y + 4;
    }

    /** Pares etiqueta/valor, dos por fila. */
    function filasFicha({ equipo, conSimcard, nombres }) {
        const conNombre = (codigo, nombre) => (nombre ? `${nombre} (${texto(codigo)})` : texto(codigo));
        const pares = [
            ['Identificador', texto(equipo.identificador), 'Código de barras', oVacio(equipo.codigo_barras, 'No registrado')],
            ['Tipo', conNombre(equipo.tipo, nombres?.tipo), 'Marca', conNombre(equipo.marca, nombres?.marca)],
            ['Serial', oVacio(equipo.serial, 'No registrado'), 'Estado', etiqueta(equipo.estado)],
            ['Asignado a', oVacio(equipo.asignado_a, 'Sin asignar'), 'Ubicación', oVacio(equipo.ubicacion, 'No registrada')],
            ...(conSimcard
                ? [['SIMCARD Relacionada', oVacio(equipo.simcard, 'No registrada'), 'SIMCARD 2 Relacionada', oVacio(equipo.simcard2, 'No registrada')]]
                : []),
            ['Procesador', oVacio(equipo.procesador, 'No registrado'), 'Memoria RAM', oVacio(equipo.ram, 'No registrada')],
            ['Disco C:', oVacio(equipo.disco, 'No registrado'), 'Disco D:', oVacio(equipo.disco2, 'No registrado')],
        ];

        return pares.map(([etiqueta1, valor1, etiqueta2, valor2]) => [etiqueta1.toUpperCase(), valor1, etiqueta2.toUpperCase(), valor2]);
    }

    function fichaTecnica(doc, datos, y) {
        const columnaEtiqueta = { cellWidth: 36, fontSize: 7.5, fontStyle: 'bold', textColor: COLOR.gris };

        doc.autoTable({
            startY: tituloSeccion(doc, 'FICHA TÉCNICA', y),
            margin: { left: MARGEN, right: MARGEN },
            theme: 'plain',
            body: filasFicha(datos),
            styles: {
                font: 'helvetica',
                fontSize: 9.5,
                textColor: COLOR.oscuro,
                valign: 'middle',
                cellPadding: { top: 2.6, bottom: 2.6, left: 3, right: 3 },
            },
            columnStyles: { 0: columnaEtiqueta, 1: { cellWidth: 54 }, 2: columnaEtiqueta, 3: { cellWidth: 54 } },
            alternateRowStyles: { fillColor: COLOR.grisClaro },
            didParseCell: prepararEtiqueta,
            didDrawCell: dibujarEtiqueta,
        });

        return doc.lastAutoTable.finalY + 12;
    }

    /** Cuatro tarjetas: total, preventivos, correctivos y fecha del último. */
    function resumen(doc, historial, y) {
        const contar = (tipo) => historial.filter((mantenimiento) => mantenimiento.tipo_mantenimiento === tipo).length;
        const ultimo = historial.reduce((mayor, mantenimiento) => (texto(mantenimiento.fecha) > mayor ? texto(mantenimiento.fecha) : mayor), '');
        const tarjetas = [
            ['MANTENIMIENTOS', String(historial.length), COLOR.oscuro],
            ['PREVENTIVOS', String(contar('Preventivo')), COLOR.azul],
            ['CORRECTIVOS', String(contar('Correctivo')), COLOR.rojo],
            ['ÚLTIMO MANTENIMIENTO', ultimo || 'Ninguno', COLOR.oscuro],
        ];
        const separacion = 4;
        const ancho = (ANCHO_UTIL - separacion * (tarjetas.length - 1)) / tarjetas.length;
        const alto = 16;

        tarjetas.forEach(([titulo, valor, color], indice) => {
            const x = MARGEN + indice * (ancho + separacion);
            doc.setFillColor(...COLOR.grisClaro);
            doc.roundedRect(x, y, ancho, alto, 2, 2, 'F');
            doc.setFillColor(...color);
            doc.rect(x, y + 2.5, 1.2, alto - 5, 'F');

            doc.setFont('helvetica', 'bold');
            doc.setFontSize(7);
            doc.setTextColor(...COLOR.gris);
            doc.text(titulo, x + 4.5, y + 6);
            doc.setFontSize(13);
            doc.setTextColor(...color);
            doc.text(valor, x + 4.5, y + 12.5);
        });

        return y + alto + 6;
    }

    function historialMantenimientos(doc, historial, y) {
        const filas = historial.map((mantenimiento) => [
            texto(mantenimiento.fecha),
            {
                content: texto(mantenimiento.tipo_mantenimiento),
                styles: { fontStyle: 'bold', textColor: COLORES_TIPO[texto(mantenimiento.tipo_mantenimiento)] || COLOR.oscuro },
            },
            texto(mantenimiento.responsable),
            texto(mantenimiento.descripcion),
            etiqueta(mantenimiento.estado),
            oVacio(mantenimiento.observaciones, '—'),
        ]);
        const sinMantenimientos = [[{
            content: 'Este equipo aún no tiene mantenimientos registrados.',
            colSpan: 6,
            styles: { halign: 'center', fontStyle: 'italic', textColor: COLOR.gris },
        }]];

        doc.autoTable({
            startY: y,
            // Arriba deja sitio al encabezado de continuación; abajo, al pie.
            margin: { left: MARGEN, right: MARGEN, top: 24, bottom: 22 },
            head: [['Fecha', 'Tipo', 'Responsable', 'Descripción', 'Estado', 'Observaciones']],
            body: filas.length > 0 ? filas : sinMantenimientos,
            theme: 'grid',
            styles: {
                font: 'helvetica',
                fontSize: 8.5,
                textColor: COLOR.oscuro,
                valign: 'top',
                cellPadding: 2.5,
                lineColor: COLOR.borde,
                lineWidth: 0.2,
            },
            headStyles: { fillColor: COLOR.oscuro, textColor: COLOR.blanco, fontStyle: 'bold', lineColor: COLOR.oscuro },
            alternateRowStyles: { fillColor: [249, 250, 251] },
            // Anchos fijos (suman ANCHO_UTIL): sin mantenimientos, la tabla no
            // tendría filas de las que calcularlos.
            columnStyles: {
                0: { cellWidth: 20 },
                1: { cellWidth: 22 },
                2: { cellWidth: 27 },
                3: { cellWidth: 34 },
                4: { cellWidth: 27 },
                5: { cellWidth: 50 },
            },
            didParseCell: prepararEtiqueta,
            didDrawCell: dibujarEtiqueta,
        });
    }

    /** Pie en todas las páginas y encabezado corto desde la segunda. */
    function pieYContinuacion(doc, equipo) {
        const total = doc.getNumberOfPages();
        const derecha = ANCHO_PAGINA - MARGEN;
        const yPie = ALTO_PAGINA - 12;

        for (let pagina = 1; pagina <= total; pagina++) {
            doc.setPage(pagina);
            doc.setFont('helvetica', 'normal');
            doc.setFontSize(8);
            doc.setTextColor(...COLOR.gris);

            if (pagina > 1) {
                doc.text(`Hoja de vida del equipo ${texto(equipo.identificador)} (continuación)`, MARGEN, 14);
                doc.setDrawColor(...COLOR.rojo);
                doc.setLineWidth(0.5);
                doc.line(MARGEN, 17, derecha, 17);
            }

            doc.setDrawColor(...COLOR.borde);
            doc.setLineWidth(0.3);
            doc.line(MARGEN, yPie - 5, derecha, yPie - 5);
            doc.text('Consultorías Debia SAS · Debia Smart Inventory', MARGEN, yPie);
            doc.text(`Página ${pagina} de ${total}`, derecha, yPie, { align: 'right' });
        }
    }

    function generarPdf(datos, logo) {
        const { jsPDF } = window.jspdf;
        const documento = new jsPDF({ unit: 'mm', format: 'a4' });
        documento.setProperties({
            title: `Hoja de vida ${texto(datos.equipo.identificador)}`,
            author: 'Consultorías Debia SAS',
            creator: 'Debia Smart Inventory',
        });

        let y = encabezado(documento, datos, logo);
        y = fichaTecnica(documento, datos, y);
        y = resumen(documento, datos.historial, tituloSeccion(documento, 'HISTORIAL DE MANTENIMIENTOS', y));
        historialMantenimientos(documento, datos.historial, y);
        pieYContinuacion(documento, datos.equipo);

        documento.save(nombreArchivo(datos.equipo.identificador));
    }

    document.addEventListener('DOMContentLoaded', () => {
        const boton = document.getElementById('btnDescargarPdf');
        const bloqueDatos = document.getElementById('datos-hoja-vida');

        if (!boton || !bloqueDatos) {
            return;
        }

        const datos = JSON.parse(bloqueDatos.textContent);
        let logo = null; // Se descarga una sola vez, en el primer clic.

        boton.addEventListener('click', async () => {
            boton.disabled = true;

            try {
                logo ??= cargarLogo(boton.dataset.logo);
                generarPdf(datos, await logo);
            } finally {
                boton.disabled = false;
            }
        });
    });
})();
