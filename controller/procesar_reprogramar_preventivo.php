<?php
/*==
1. AGREGAR FILTROS DE EQUIPO A SELECCIONAR PARA REPROGRAMAR
2. AGREGAR SELECCION AUTOMATICA DESDE EL BOTON DE REPROGRAMAR EN LA VISTA DE PROGRAMAR PREVENTIVOS, QUE LLENE EL CAMPO OCULTO CON EL ID DEL PREVENTIVO Y MUESTRE EL EQUIPO SELECCIONADO EN EL 
FORMULARIO DE REPROGRAMACION
3. AJUSTAR CALCULO DE PROXIMA FECHA PARA QUE SE BASE EN LA NUEVA FECHA PROGRAMADA, NO EN LA FECHA ANTERIOR
4. AJUSTAR DISEÑO CSS QUE MANTENGA LA COHERENCIA CON EL RESTO DE VISTAS.
5. PONER A LAS TABLAS QUE MUESTRAN LA INFORMACION, UN LIMITE DE REGISTROS POR PAGINA DE 10, CON PAGINACION PARA NAVEGAR ENTRE PAGINAS
===*/
require '../bd/conn.php';


/*==================================
PROCESAR REPROGRAMACION
===================================*/

if(
$_SERVER['REQUEST_METHOD']=='POST'&&isset($_POST['reprogramar'])){
$id=$_POST['preventivo_id'];
$nuevaFecha=$_POST['nueva_fecha'];
$motivo=$_POST['motivo'];
$responsable=$_POST['responsable'];


/* Obtener preventivo actual */

$stmt=$conn->prepare("
SELECT
fecha_programada,
proxima_fecha_programada,
tipo_frecuencia
FROM preventivos_programados
WHERE id=?
");

$stmt->execute([$id]);

$p=$stmt->fetch(PDO::FETCH_ASSOC);


/* calcular siguiente fecha */

$proxima=$nuevaFecha;

switch($p['tipo_frecuencia']){

case 'Semanal':
$proxima=date(
'Y-m-d',
strtotime('+1 week',strtotime($nuevaFecha))
);
break;

case 'Mensual':
$proxima=date(
'Y-m-d',
strtotime('+1 month',strtotime($nuevaFecha))
);
break;

case 'Trimestral':
$proxima=date(
'Y-m-d',
strtotime('+3 months',strtotime($nuevaFecha))
);
break;

case 'Semestral':
$proxima=date(
'Y-m-d',
strtotime('+6 months',strtotime($nuevaFecha))
);
break;

case 'Anual':
$proxima=date(
'Y-m-d',
strtotime('+1 year',strtotime($nuevaFecha))
);
break;
}



/* guardar historial */

$hist=$conn->prepare("
INSERT INTO preventivos_historial(
preventivo_id,
valor_anterior,
valor_nuevo,
motivo,
usuario
)
VALUES(?,?,?,?,?)
");

$hist->execute([
$id,
$p['fecha_programada'],
$nuevaFecha,
$motivo,
$responsable
]);


/* actualizar preventivo */

$upd=$conn->prepare("
UPDATE preventivos_programados
SET
fecha_programada=?,
proxima_fecha_programada=?,
estado='Reprogramado',
descripcion=CONCAT(
descripcion,
' | Reprogramado: ',
?
)
WHERE id=?
");

$upd->execute([
$nuevaFecha,
$proxima,
$motivo,
$id
]);


header(
"Location: ../views/programar_preventivos.php"
);

exit;

}
?>