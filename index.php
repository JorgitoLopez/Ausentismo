<?php

require_once 'config/db.php';

$fecha_hoy = date('Y-m-d');

/* ========================================================

   CATALOGOS DINÁMICOS

   Se obtienen directamente de la tabla empleados para 

   garantizar que los filtros coincidan con la nómina real.

   ======================================================== */

$turnos = $pdo->query("SELECT DISTINCT id_turno FROM empleados WHERE id_turno IS NOT NULL ORDER BY id_turno")->fetchAll(PDO::FETCH_ASSOC);

$departamentos = $pdo->query("SELECT DISTINCT id_departamento FROM empleados WHERE id_departamento IS NOT NULL ORDER BY id_departamento")->fetchAll(PDO::FETCH_ASSOC);

// Definición de etiquetas para los checkboxes de clasificación

$cat_asistencias = [

    'asistencia' => 'Asistencia (Reloj)',

    'olvido de gafete' => 'Olvido de Gafete',

    'permiso de llegada tarde' => 'Permiso Llegada Tarde'

];

$cat_justificados = [

    'incapacidad' => 'Incapacidad',

    'maternidad' => 'Maternidad',

    'vacaciones' => 'Vacaciones',

    'permiso justificado' => 'Permiso Justificado',

    'baja' => 'Baja'

];

?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>RH System | Control de Asistencia y Ausentismo</title>
<link href="bootstrap.min.css" rel="stylesheet">
<link href="bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<style>

        :root { --sidebar-bg: #ffffff; --body-bg: #f4f6f9; }

        body { background-color: var(--body-bg); font-size: 0.88rem; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }

        /* Estilos Sidebar */

        .sidebar { background: var(--sidebar-bg); border-right: 1px solid #dee2e6; min-height: 100vh; transition: all 0.3s; }

        .sticky-sidebar { position: sticky; top: 80px; }

        /* Estilos de Tabla y Cards */

        .card { border: none; border-radius: 8px; box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075); margin-bottom: 1.5rem; }

        .table thead { background-color: #212529; color: #ffffff; }

        .table-hover tbody tr:hover { background-color: rgba(0,0,0,0.02); }

        .badge { font-weight: 500; padding: 0.5em 0.8em; }

        /* Filtros y Checkboxes */

        .group-title { font-size: 0.72rem; font-weight: 700; color: #6c757d; margin-top: 18px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #f0f0f0; padding-bottom: 4px; margin-bottom: 10px; }

        .form-check { margin-bottom: 6px; }

        .form-check-label { cursor: pointer; color: #444; }

        .form-check-input:checked { background-color: #0d6efd; border-color: #0d6efd; }

        /* Navbar Custom */

        .navbar { box-shadow: 0 2px 4px rgba(0,0,0,0.1); }

        .navbar-brand { font-size: 1.25rem; letter-spacing: 1px; }

        /* Estilos para Importación */

        #detalle_importe { font-size: 0.8rem; padding: 10px; border-radius: 5px; background: #f8f9fa; }
</style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
<div class="container-fluid">
<span class="navbar-brand fw-bold">RH System</span>
<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
<span class="navbar-toggler-icon"></span>
</button>
<div class="collapse navbar-collapse" id="navMenu">
<ul class="navbar-nav ms-auto">
<li class="nav-item"><a class="nav-link active" href="index.php">Inicio</a></li>
<li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
<li class="nav-item"><a class="nav-link" href="empleados.php">Empleados</a></li>
<li class="nav-item"><a class="nav-link" href="permisos.php">Permisos</a></li>
<li class="nav-item"><a class="nav-link" href="entrevistas.php">Entrevistas</a></li>
<li class="nav-item"><a class="nav-link" href="suspensiones.php">Suspensiones</a></li>
</ul>
</div>
</div>
</nav>
<div class="container-fluid" style="margin-top:75px">
<div class="row">
<div class="col-md-3 col-lg-2 sidebar p-3 shadow-sm">
<div class="sticky-sidebar">
<h6 class="fw-bold mb-3 text-dark border-bottom pb-2"><i class="bi bi-filter-left"></i> FILTROS</h6>
<div class="mb-3">
<label class="form-label fw-bold small">Fecha Operativa</label>
<input type="date" id="filtro_fecha" class="form-control form-control-sm" value="<?= $fecha_hoy ?>" onchange="cargarEmpleados()">
</div>
<div class="mb-3">
<label class="form-label fw-bold small">Turno / Horario</label>
<select id="filtro_turno" class="form-select form-select-sm" onchange="cargarEmpleados()">
<option value="">-- Todos los turnos --</option>
<?php foreach ($turnos as $t): ?>
<option value="<?= $t['id_turno'] ?>"><?= $t['id_turno'] ?></option>
<?php endforeach; ?>
</select>
</div>
<div class="mb-3">
<label class="form-label fw-bold small">Departamento</label>
<select id="filtro_departamento" class="form-select form-select-sm" onchange="actualizarLineas()">
<option value="">-- Todos --</option>
<?php foreach ($departamentos as $d): ?>
<option value="<?= $d['id_departamento'] ?>"><?= $d['id_departamento'] ?></option>
<?php endforeach; ?>
</select>
</div>
<div class="mb-4">
<label class="form-label fw-bold small">Línea / Área</label>
<select id="filtro_linea" class="form-select form-select-sm" onchange="cargarEmpleados()">
<option value="">Seleccione dep...</option>
</select>
</div>
<h6 class="fw-bold mb-2 text-dark"><i class="bi bi-tags"></i> CLASIFICACIÓN RH</h6>
<div class="group-title text-success">Asistencias</div>
<?php foreach ($cat_asistencias as $val => $label): ?>
<div class="form-check">
<input class="form-check-input check-filtro" type="checkbox" value="<?= $val ?>" id="c_<?= str_replace(' ','_',$val) ?>" onchange="cargarEmpleados()">
<label class="form-check-label small" for="c_<?= str_replace(' ','_',$val) ?>"><?= $label ?></label>
</div>
<?php endforeach; ?>
<div class="group-title text-primary">Justificados</div>
<?php foreach ($cat_justificados as $val => $label): ?>
<div class="form-check">
<input class="form-check-input check-filtro" type="checkbox" value="<?= $val ?>" id="c_<?= str_replace(' ','_',$val) ?>" onchange="cargarEmpleados()">
<label class="form-check-label small" for="c_<?= str_replace(' ','_',$val) ?>"><?= $label ?></label>
</div>
<?php endforeach; ?>
<div class="group-title text-danger">Injustificados</div>
<div class="form-check">
<input class="form-check-input check-filtro" type="checkbox" value="falta injustificada" id="c_falta" onchange="cargarEmpleados()">
<label class="form-check-label small" for="c_falta">Falta Injustificada</label>
</div>
</div>
</div>
<div class="col-md-9 col-lg-10 p-4">
<div class="card shadow-sm mb-4 border-start border-primary border-4">
<div class="card-body">
<div class="d-flex justify-content-between align-items-center mb-3">
<h6 class="fw-bold mb-0 text-primary"><i class="bi bi-clock-history"></i> Sincronización de Reloj Checador</h6>
<span class="badge bg-light text-dark border">Formato aceptado: .PRN, .TXT</span>
</div>
<form id="formImportar" enctype="multipart/form-data">
<div class="row g-3">
<div class="col-md-10">
<input type="file" name="archivo" id="archivo_prn" class="form-control" accept=".prn,.txt" required>
</div>
<div class="col-md-2">
<button class="btn btn-dark w-100" type="submit" id="btnImportar">
<i class="bi bi-cloud-upload"></i> Importar
</button>
</div>
</div>
</form>
<div class="progress mt-3" style="height:22px; display:none" id="barraBox">
<div class="progress-bar bg-success progress-bar-striped progress-bar-animated" 

                             id="barra" role="progressbar" style="width: 0%;">0%</div>
</div>
<div id="detalle_importe" class="mt-2 text-muted fw-bold">
</div>
</div>
</div>
<div class="card shadow-sm">
<div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
<h6 class="fw-bold mb-0 text-dark">Registros de Asistencia del Personal</h6>
<div class="text-muted small" id="contador_registros"></div>
</div>
<div class="card-body p-0">
<div class="table-responsive">
<table class="table table-hover align-middle mb-0">
<thead>
<tr>
<th class="text-center" width="60">#</th>
<th>Información del Empleado</th>
<th class="text-center">Estatus de Asistencia</th>
<th width="280">Acción / Clasificación RH</th>
</tr>
</thead>
<tbody id="tabla_empleados">
</tbody>
</table>
</div>
</div>
</div>
</div>
</div>
</div>
<script src="bootstrap.bundle.min.js"></script>
<script>

/**

 * Actualiza las líneas disponibles basándose en el departamento seleccionado

 */

function actualizarLineas() {

    const dep = document.getElementById('filtro_departamento').value;

    const selectL = document.getElementById('filtro_linea');

    fetch(`ajax/obtener_lineas.php?dep=${encodeURIComponent(dep)}`)

        .then(r => r.json())

        .then(data => {

            selectL.innerHTML = '<option value="">-- Todas las líneas --</option>';

            data.forEach(l => {

                selectL.innerHTML += `<option value="${l.id_linea}">${l.id_linea}</option>`;

            });

            cargarEmpleados(); // Recargar tabla automáticamente

        })

        .catch(err => console.error("Error al cargar líneas:", err));

}

/**

 * Carga principal de la tabla de empleados con filtros aplicados

 */

function cargarEmpleados() {

    // Recolectar valores de checkboxes seleccionados

    const filtrosClasificacion = Array.from(document.querySelectorAll('.check-filtro:checked'))

                                     .map(c => c.value);

    const dataFiltros = {

        fecha: document.getElementById('filtro_fecha').value,

        turno: document.getElementById('filtro_turno').value,

        departamento: document.getElementById('filtro_departamento').value,

        linea: document.getElementById('filtro_linea').value,

        clasificaciones: filtrosClasificacion

    };

    const tbody = document.getElementById('tabla_empleados');

    fetch('ajax/filtrar_asistencia.php', {

        method: 'POST',

        headers: {'Content-Type': 'application/json'},

        body: JSON.stringify(dataFiltros)

    })

    .then(r => r.text())

    .then(html => {

        tbody.innerHTML = html;

        const totalRows = tbody.querySelectorAll('tr').length;

        document.getElementById('contador_registros').innerText = `Mostrando ${totalRows} empleados`;

    })

    .catch(err => {

        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error al cargar datos.</td></tr>';

        console.error("Error en el filtrado:", err);

    });

}

/**

 * Clasifica manualmente a un empleado (RH) y sincroniza con la base de datos

 */

function asignarCategoria(idEmpleado, fecha, tipoSeleccionado) {

    if(!tipoSeleccionado) return;

    fetch('ajax/guardar_categoria.php', {

        method: 'POST',

        headers: {'Content-Type': 'application/json'},

        body: JSON.stringify({

            id_empleado: idEmpleado, 

            fecha: fecha, 

            tipo: tipoSeleccionado

        })

    })

    .then(r => r.json())

    .then(res => {

        if(res.success) {

            cargarEmpleados(); // Refrescar para ver cambios y badges

        } else {

            alert("Error al clasificar: " + res.error);

        }

    });

}

/**

 * Lógica de Importación PRN (Detección de duplicados y progreso)

 */

const formImp = document.getElementById('formImportar');

const btnImp = document.getElementById('btnImportar');

const barra = document.getElementById('barra');

const barraBox = document.getElementById('barraBox');

const detalleImp = document.getElementById('detalle_importe');

formImp.addEventListener('submit', e => {

    e.preventDefault();

    // UI Setup

    btnImp.disabled = true;

    barraBox.style.display = 'block';

    barra.style.width = '0%';

    barra.innerText = '0%';

    detalleImp.innerHTML = '<span class="text-info">Iniciando procesamiento de archivo...</span>';

    const formData = new FormData(formImp);

    function procesarFragmento() {

        fetch('ajax/importar_checadas.php', { 

            method: 'POST', 

            body: formData 

        })

        .then(r => r.json())

        .then(res => {

            // Calcular porcentaje real

            const porcentaje = Math.round((res.procesados / res.total) * 100);

            barra.style.width = porcentaje + '%';

            barra.innerText = porcentaje + '%';

            // Mostrar estadísticas de la carga (DUPLICADOS Y NUEVOS)

            detalleImp.innerHTML = `
<span class="badge bg-primary">Total: ${res.total}</span>
<span class="badge bg-success">Nuevos: ${res.insertados}</span>
<span class="badge bg-warning text-dark">Duplicados/Omitidos: ${res.omitidos || 0}</span>
<span class="ms-2">Progreso: ${res.procesados} / ${res.total}</span>

            `;

            if (!res.finalizado) {

                procesarFragmento(); // Recursión para el siguiente lote

            } else {

                barra.classList.remove('progress-bar-animated');

                btnImp.disabled = false;

                cargarEmpleados(); // Refrescar tabla final

                alert("Importación completada con éxito.");

            }

        })

        .catch(err => {

            console.error(err);

            btnImp.disabled = false;

            detalleImp.innerHTML = '<span class="text-danger">Error crítico durante la importación.</span>';

        });

    }

    procesarFragmento();

});

// Inicialización

window.onload = () => {

    actualizarLineas();

};
</script>
</body>
</html>
 