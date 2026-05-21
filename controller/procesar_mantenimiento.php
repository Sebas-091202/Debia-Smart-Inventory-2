<?php
require '../bd/conn.php';

//  DATOS MANTENIMIENTO
$equipo_id = $_POST['equipo_id'];
$fecha = $_POST['fecha'];
$responsable = $_POST['responsable'];
$tipo = $_POST['tipo_mantenimiento'];
$descripcion = $_POST['descripcion'];
$estado = $_POST['estado'];
$obs = $_POST['observaciones'];

//  1. INSERTAR MANTENIMIENTO
$sql = "INSERT INTO mantenimientos 
(equipo_id, fecha, responsable, tipo_mantenimiento, descripcion, estado, observaciones)
VALUES (?,?,?,?,?,?,?)";

$stmt = $conn->prepare($sql);
$stmt->execute([$equipo_id, $fecha, $responsable, $tipo, $descripcion, $estado, $obs]);

// ESTE ES EL BUENO
$id_mantenimiento = $conn->lastInsertId();


// 2. PROCESAR REPUESTOS (OPCIONAL)
if (!empty($_POST['nombre_repuesto'])) {

    foreach ($_POST['nombre_repuesto'] as $i => $nombre) {

        $tipo_r = $_POST['tipo_repuesto'][$i];
        $serial = $_POST['serial_repuesto'][$i];
        $capacidad = $_POST['capacidad_repuesto'][$i];
        $descripcion_r = $_POST['descripcion_repuesto'][$i];
        $valor = $_POST['valor_repuesto'][$i];
        $cantidad = $_POST['cantidad'][$i];

        // SOLO SI HAY DATOS
        if (!empty($nombre) && !empty($tipo_r) && !empty($cantidad)) {

            // 🔍 BUSCAR EXISTENTE
            $buscar = $conn->prepare("
                SELECT id FROM repuestos 
                WHERE nombre=? AND tipo=? AND capacidad=?
            ");
            $buscar->execute([$nombre, $tipo_r, $capacidad]);
            $existe = $buscar->fetch(PDO::FETCH_ASSOC);

            if ($existe) {

                // SUMAR STOCK
                $conn->prepare("
                    UPDATE repuestos 
                    SET stock = stock + ?
                    WHERE id = ?
                ")->execute([$cantidad, $existe['id']]);

                $repuesto_id = $existe['id'];

            } else {

                // INSERTAR NUEVO
                $conn->prepare("
                    INSERT INTO repuestos 
                    (nombre, serial, capacidad, valor, tipo, descripcion, stock, estado)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'Disponible')
                ")->execute([$nombre, $serial, $capacidad, $valor, $tipo_r, $descripcion_r, $cantidad]);

                $repuesto_id = $conn->lastInsertId();
            }

            // 3. RELACIONAR CON MANTENIMIENTO (CORRECTO)
            $conn->prepare("
                INSERT INTO mantenimiento_repuestos (mantenimiento_id, repuesto_id, cantidad, valor_unitario)
                VALUES (?, ?, ?, ?)
            ")->execute([$id_mantenimiento, $repuesto_id, $cantidad, $valor]);
        }
    }
}

//  REDIRECCIÓN
header("Location: ../views/hoja_vida_equipos.php?id=".$equipo_id);
exit;
?>