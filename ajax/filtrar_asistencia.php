<?php
require_once '../config/db.php';
$data = json_decode(file_get_contents('php://input'), true);
$fecha = $data['fecha'] ?? date('Y-m-d');
try {
   $pdo->beginTransaction();
   // 1. SINCRONIZACIÓN: Insertar empleados nuevos (Estatus inicial vacío o NULL para obligar a seleccionar)
   $sql_sync = "INSERT IGNORE INTO asistencia_diaria (id_empleado, fecha, id_turno, id_departamento, id_linea, estatus, estatus_real)
                SELECT id_empleado, :f1, id_turno, id_departamento, id_linea, 'PENDIENTE', NULL FROM empleados";
   $pdo->prepare($sql_sync)->execute([':f1' => $fecha]);
   // 2. ACTUALIZACIÓN POR RELOJ: Si hay checada, es JUSTIFICADO automáticamente
   $sql_update_reloj = "UPDATE asistencia_diaria ad
                       INNER JOIN (SELECT id_empleado FROM checadas WHERE fecha = :f_ref GROUP BY id_empleado) c
                       ON ad.id_empleado = c.id_empleado
                       SET ad.estatus = 'JUSTIFICADO', ad.estatus_real = 'asistencia'
                       WHERE ad.fecha = :f_up";
   $pdo->prepare($sql_update_reloj)->execute([':f_ref' => $fecha, ':f_up' => $fecha]);
   $pdo->commit();
} catch (Exception $e) {
   $pdo->rollBack();
   die("Error: " . $e->getMessage());
}
// 3. CONSULTA PARA LA TABLA
$sql = "SELECT e.id_empleado, e.nombre_completo, c.hora_reloj, ad.estatus, ad.estatus_real
       FROM empleados e
       INNER JOIN asistencia_diaria ad ON e.id_empleado = ad.id_empleado AND ad.fecha = :f_cons
       LEFT JOIN (SELECT id_empleado, MIN(hora) as hora_reloj FROM checadas WHERE fecha = :f_ch GROUP BY id_empleado) c
            ON e.id_empleado = c.id_empleado
       WHERE 1=1";
$params = [':f_cons' => $fecha, ':f_ch' => $fecha];
// ... (Filtros de sidebar si los usas)
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $i => $row) {
   $est = $row['estatus'];
   $detalle = $row['estatus_real'];
   // Si es PENDIENTE (rojo suave o gris), si es JUSTIFICADO (verde), si es INJUSTIFICADO (rojo)
   $badgeColor = ($est == 'JUSTIFICADO') ? 'success' : (($est == 'INJUSTIFICADO') ? 'danger' : 'secondary');
   echo "<tr>
<td class='text-center'>".($i+1)."</td>
<td>
<div class='fw-bold'>{$row['nombre_completo']}</div>
<div class='text-muted small'>ID: {$row['id_empleado']}</div>
</td>
<td class='text-center'>
<span class='badge bg-$badgeColor' style='font-size: 0.9rem;'>$est</span><br>
<small class='text-muted fw-bold'>" .
               ($detalle && $detalle !== 'asistencia' ? strtoupper($detalle) : ($row['hora_reloj'] ?? 'SIN REGISTRO')) .
           "</small>
</td>
<td>";
   if (!$row['hora_reloj']) {
       echo "<select class='form-select form-select-sm' onchange='asignarCategoria({$row['id_empleado']}, \"$fecha\", this.value)'>
<option value=''>-- Seleccione --</option>
<optgroup label='Asistencias (JUSTIFICADO)'>
<option value='olvido de gafete' ".($detalle=='olvido de gafete'?'selected':'').">Olvido Gafete</option>
<option value='permiso de llegada tarde' ".($detalle=='permiso de llegada tarde'?'selected':'').">Llegada Tarde</option>
</optgroup>
<optgroup label='Ausencias (JUSTIFICADO)'>
<option value='permiso justificado' ".($detalle=='permiso justificado'?'selected':'').">Permiso Justificado</option>
<option value='vacaciones' ".($detalle=='vacaciones'?'selected':'').">Vacaciones</option>
<option value='incapacidad' ".($detalle=='incapacidad'?'selected':'').">Incapacidad</option>
<option value='maternidad' ".($detalle=='maternidad'?'selected':'').">Maternidad</option>
<option value='baja' ".($detalle=='baja'?'selected':'').">Baja</option>
</optgroup>
<option value='falta injustificada' ".($detalle=='falta injustificada'?'selected':'').">Falta Injustificada (INJUSTIFICADO)</option>
</select>";
   } else {
       echo "<div class='text-success small fw-bold text-center'><i class='bi bi-clock-fill'></i> ASISTENCIA VALIDADA</div>";
   }
   echo "</td></tr>";
}
