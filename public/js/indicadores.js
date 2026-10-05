/**
 * Gráficos de mantenimientos por mes (Chart.js, servido desde js/vendor).
 *
 * Cada <canvas data-grafico="N"> dibuja el bloque N del JSON
 * <script type="application/json" id="datos-graficos">.
 */
(() => {
    'use strict';

    const COLOR_TEXTO = '#ffffff';
    const COLOR_REJILLA = 'rgba(255, 255, 255, 0.1)';

    const SERIES = [
        { etiqueta: 'Preventivos', campo: 'preventivos', color: '#3b82f6' },
        { etiqueta: 'Correctivos', campo: 'correctivos', color: '#ec4141' },
    ];

    function crearGrafico(canvas, meses) {
        return new window.Chart(canvas, {
            type: 'bar',
            data: {
                labels: meses.map((mes) => mes.mes),
                datasets: SERIES.map((serie) => ({
                    label: serie.etiqueta,
                    data: meses.map((mes) => mes[serie.campo]),
                    backgroundColor: serie.color,
                    borderRadius: 6,
                })),
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { labels: { color: COLOR_TEXTO } },
                },
                scales: {
                    x: { ticks: { color: COLOR_TEXTO }, grid: { color: COLOR_REJILLA } },
                    y: { beginAtZero: true, ticks: { color: COLOR_TEXTO, precision: 0 }, grid: { color: COLOR_REJILLA } },
                },
            },
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        const bloqueDatos = document.getElementById('datos-graficos');

        if (!bloqueDatos || !window.Chart) {
            return;
        }

        const graficos = JSON.parse(bloqueDatos.textContent);

        document.querySelectorAll('canvas[data-grafico]').forEach((canvas) => {
            const meses = graficos[Number(canvas.dataset.grafico)];
            if (meses) {
                crearGrafico(canvas, meses);
            }
        });
    });
})();
