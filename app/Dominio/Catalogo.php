<?php

declare(strict_types=1);

namespace App\Dominio;

/**
 * Fuente única de verdad de los valores permitidos.
 *
 * Alimenta los <select> de filtros y formularios, la Guía de
 * Abreviaciones y la validación del servidor (lista blanca), de modo
 * que todos quedan siempre sincronizados.
 */
final class Catalogo
{
    public const TIPOS_EQUIPO = [
        'PO' => 'Portátil',
        'TO' => 'Todo en Uno',
        'ES' => 'Escritorio',
        'IM' => 'Impresora',
        'VI' => 'Videobeam',
        'BR' => 'Bases de Refrigeración',
        'MS' => 'Mouse',
        'TE' => 'Teclado',
        'DI' => 'Diademas',
        'AU' => 'Auriculares',
        'CE' => 'Celulares',
        'MO' => 'Monitor',
    ];

    /** Tipos de equipo de cómputo a los que se les mide el mantenimiento. */
    public const TIPOS_COMPUTO = ['PO', 'TO', 'ES'];

    public const MARCAS = [
        'DE' => 'DELL',
        'IN' => 'INPOWER',
        'AS' => 'ASUS',
        'HP' => 'HEWLETT-PACKARD',
        'LE' => 'LENOVO',
        'EP' => 'EPSON',
        'CA' => 'CANON',
        'CP' => 'COOLER PAD',
        'GE' => 'GENIUS',
        'OP' => 'OPPO',
        'KA' => 'KALLEY',
        'SA' => 'SAMSUNG',
        'CH' => 'CHALLENGER',
        'LG' => 'LG',
        'MA' => 'MAXELL',
        'PA' => 'PANASONIC',
        'AR' => 'ARCHTEX',
        'XK' => 'XKIM',
        'HA' => 'HAVIT',
        'LO' => 'LOGITECH',
        'WI' => 'WIT',
        'HU' => 'HUAWEI',
        'MT' => 'MOTOROLA',
        'XI' => 'XIAOMI',
        'SM' => 'SIN MARCA',
    ];

    public const UBICACIONES = [
        'AXA Mortales',
        'AXA Gastos Medicos',
        'AXA IPS',
        'Generales',
        'Consultas',
        'HDI',
        'SURA',
        'SURA IPS',
        'SURA Gastos Medicos',
        'Sistemas',
        'Financiera',
        'Talento Humano',
        'Dirección Operativa',
        'En Casa',
        'Renting',
        'Camaras',
        'Bodega',
    ];

    /** Coincide con la restricción CHECK de equipos.estado. */
    public const ESTADOS_EQUIPO = ['Activo', 'Bodega', 'En reparación', 'Dado de baja'];

    /** Coincide con la restricción CHECK de mantenimientos.estado. */
    public const ESTADOS_MANTENIMIENTO = ['Activo', 'En reparación', 'Finalizado'];

    /** Coincide con la restricción CHECK de mantenimientos.tipo_mantenimiento. */
    public const TIPOS_MANTENIMIENTO = ['Preventivo', 'Correctivo'];

    public const RAM = ['4GB', '8GB', '12GB', '16GB', '32GB', '64GB'];

    /** Opciones de disco agrupadas por tecnología (se muestran con <optgroup>). */
    public const DISCOS_C = [
        'SSD' => ['16GB', '32GB', '64GB', '128GB SSD', '256GB SSD', '512GB SSD', '1TB SSD', '2TB SSD'],
        'HDD' => ['500GB HDD', '700GB HDD', '1TB HDD', '2TB HDD'],
    ];

    public const DISCOS_D = [
        'SSD' => ['Ninguno SSD', '128GB SSD', '256GB SSD', '512GB SSD', '1TB SSD', '2TB SSD'],
        'HDD' => ['Ninguno HDD', '500GB HDD', '700GB HDD', '1TB HDD', '2TB HDD'],
    ];

    private const CLASES_ESTADO = [
        'Activo'        => 'estado-activo',
        'Bodega'        => 'estado-bodega',
        'En reparación' => 'estado-reparacion',
        'Dado de baja'  => 'estado-baja',
        'Finalizado'    => 'estado-finalizado',
    ];

    /** Clase CSS del badge de un estado de equipo o de mantenimiento. */
    public static function claseEstado(?string $estado): string
    {
        return self::CLASES_ESTADO[$estado ?? ''] ?? 'estado-desconocido';
    }

    /** Aplana un catálogo agrupado (DISCOS_C / DISCOS_D) en una lista. */
    public static function valores(array $agrupado): array
    {
        return array_merge(...array_values($agrupado));
    }
}
