<?php
require_once 'config/db.php';

$fecha_hoy = date('Y-m-d');

// Catálogos para filtros
$turnos = $pdo->query("SELECT DISTINCT id_turno FROM empleados WHERE id_turno IS NOT NULL ORDER BY id_turno")->fetchAll(PDO::FETCH_ASSOC);
$departamentos = $pdo->query("SELECT DISTINCT id_departamento FROM empleados WHERE id_departamento IS NOT NULL ORDER BY id_departamento")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>RH System | Gestión de Entrevistas</title>
<link href="bootstrap.min.css" rel="stylesheet">
<link href="bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<style>
:root { --sidebar-bg: #ffffff; --body-bg: #f4f6f9; }
body { background-color: var(--body-bg); font-size: 0.88rem; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
.sidebar { background: var(--sidebar-bg); border-right: 1px solid #dee2e6; min-height: 100vh; }
.sticky-sidebar { position: sticky; top: 80px; }
.card { border: none; border-radius: 8px; box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075); margin-bottom: 1.5rem; }
.table thead { background-color: #212529; color: #ffffff; }
.navbar { box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
.group-title { font-size: 0.72rem; font-weight: 700; color: #6c757d; margin-top: 15px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #f0f0f0; padding-bottom: 4px; margin-bottom: 10px; }

/* Calendario */
.cal-dia{
    border:1px solid #dee2e6;
    border-radius:6px;
    padding:4px;
    min-height:65px;
    font-size:0.7rem;
    cursor:pointer;
}
.cal-dia:hover{background:#f1f3f5}
.cal-dia.activo{
    background:#0d6efd;
    color:white;
}
.cal-total{color:#dc3545;font-size:0.65rem}
.cal-turno{color:#0d6efd;font-size:0.65rem}
.cal-header{font-weight:bold;text-align:center;font-size:0.75rem;color:#495057;margin-bottom:4px;}
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
<li class="nav-item"><a class="nav-link" href="index.php">Inicio</a></li>
<li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
<li class="nav-item"><a class="nav-link" href="empleados.php">Empleados</a></li>
<li class="nav-item"><a class="nav-link" href="permisos.php">Permisos</a></li>
<li class="nav-item"><a class="nav-link active" href="entrevistas.php">Entrevistas</a></li>
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
<label class="form-label fw-bold small">Fecha de Falta</label>
<input type="date" id="filtro_fecha" class="form-control form-control-sm" value="<?= $fecha_hoy ?>" onchange="cargarEntrevistas()">
</div>

<div class="mb-3">
<label class="form-label fw-bold small text-danger">Fecha de Suspensión</label>
<input type="date" id="filtro_suspension" class="form-control form-control-sm" onchange="cargarEntrevistas()">
<small class="text-muted" style="font-size: 0.7rem;">Filtrar por día de castigo</small>
</div>

<div class="mb-3">
<label class="form-label fw-bold small">Estatus Entrevista</label>
<select id="filtro_estatus" class="form-select form-select-sm" onchange="cargarEntrevistas()">
<option value="">-- Todos --</option>
<option value="PENDIENTE">PENDIENTE</option>
<option value="COMPLETADA">COMPLETADA</option>
</select>
</div>

<div class="mb-3">
<label class="form-label fw-bold small">Turno</label>
<select id="filtro_turno" class="form-select form-select-sm" onchange="cargarEntrevistas()">
<option value="">-- Todos --</option>
<?php foreach ($turnos as $t): ?>
<option value="<?= $t['id_turno'] ?>"><?= $t['id_turno'] ?></option>
<?php endforeach; ?>
</select>
</div>

<button class="btn btn-light btn-sm w-100 border text-muted" onclick="limpiarFiltros()">
<i class="bi bi-arrow-clockwise"></i> Limpiar Filtros
</button>

</div>
</div>

<div class="col-md-9 col-lg-10 p-4">
<div class="card shadow-sm border-start border-danger border-4 mb-4">
<div class="card-body py-3 d-flex justify-content-between align-items-center">
<h5 class="fw-bold mb-0 text-danger"><i class="bi bi-chat-right-text"></i> Panel de Entrevistas Administrativas</h5>
<div id="contador" class="badge bg-light text-dark border"></div>
</div>
</div>

<div class="card shadow-sm">
<div class="table-responsive">
<table class="table table-hover align-middle mb-0">
<thead>
<tr>
<th class="text-center" width="60">#</th>
<th>Empleado</th>
<th class="text-center">Fecha Falta</th>
<th class="text-center">Estatus</th>
<th class="text-center">F. Suspensión</th>
<th class="text-center">Acciones</th>
</tr>
</thead>
<tbody id="tabla_entrevistas"></tbody>
</table>
</div>
</div>
</div>
</div>
</div>

<!-- Modal -->
<div class="modal fade" id="modalEntrevista" tabindex="-1" aria-hidden="true">
<div class="modal-dialog modal-lg modal-dialog-centered">
<div class="modal-content border-0 shadow">
<div class="modal-header bg-dark text-white">
<h5 class="modal-title fw-bold"><i class="bi bi-pencil-square"></i> Editar Registro</h5>
<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
<form id="formEntrevista">
<input type="hidden" id="edit_id">
<div class="mb-3">
<label class="form-label fw-bold small">MOTIVO DECLARADO</label>
<textarea id="edit_motivo" class="form-control" rows="3" placeholder="Ej: Problemas de transporte, salud, etc."></textarea>
</div>

<!-- Calendario dinámico -->
<div class="mb-3">
<label class="form-label fw-bold small">FECHA DE SUSPENSIÓN</label>
<div class="border rounded p-2">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <button type="button" class="btn btn-sm btn-light" onclick="cambiarMes(-1)"><i class="bi bi-chevron-left"></i></button>
        <strong id="calendarioTitulo"></strong>
        <button type="button" class="btn btn-sm btn-light" onclick="cambiarMes(1)"><i class="bi bi-chevron-right"></i></button>
    </div>
    <div class="d-grid" id="calendario" style="grid-template-columns:repeat(7,1fr);gap:6px"></div>
    <div class="d-flex justify-content-between mt-1">
        <span class="cal-header">Lun</span><span class="cal-header">Mar</span><span class="cal-header">Mié</span><span class="cal-header">Jue</span>
        <span class="cal-header">Vie</span><span class="cal-header">Sáb</span><span class="cal-header">Dom</span>
    </div>
</div>
<input type="hidden" id="edit_suspension">
<small class="text-muted">Si no selecciona fecha, no se aplica suspensión</small>
</div>

<div class="mb-3">
<label class="form-label fw-bold small">ESTATUS DE ENTREVISTA</label>
<select id="edit_estatus" class="form-select">
<option value="PENDIENTE">PENDIENTE</option>
<option value="COMPLETADA">COMPLETADA</option>
</select>
</div>

</form>
</div>
<div class="modal-footer bg-light">
<button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
<button type="button" class="btn btn-primary btn-sm" onclick="guardarCambios()">Actualizar Registro</button>
</div>
</div>
</div>
</div>

<script src="bootstrap.bundle.min.js"></script>
<script>
const modalEntrevista = new bootstrap.Modal(document.getElementById('modalEntrevista'));

// Variables para calendario
let fechaActual = new Date();
let turnoEmpleado = null;
let lineaEmpleado = null;

function cargarEntrevistas() {
    const filtros = {
        fecha: document.getElementById('filtro_fecha').value,
        suspension: document.getElementById('filtro_suspension').value,
        estatus: document.getElementById('filtro_estatus').value,
        turno: document.getElementById('filtro_turno').value
    };
    fetch('ajax/filtrar_entrevistas.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify(filtros)
    })
    .then(r => r.text())
    .then(html => {
        const tbody = document.getElementById('tabla_entrevistas');
        tbody.innerHTML = html;
        const total = tbody.querySelectorAll('tr:not(.no-data)').length;
        document.getElementById('contador').innerText = `${total} registros`;
    });
}

// Abrir modal y preparar calendario
function abrirModal(id, motivo, suspension, estatus, turno, linea){
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_motivo').value = motivo;
    document.getElementById('edit_estatus').value = estatus;
    turnoEmpleado = turno;
    lineaEmpleado = linea;
    fechaActual = new Date();
    renderCalendario();
    modalEntrevista.show();
}

function guardarCambios(){
    const data = {
        id_entrevista: document.getElementById('edit_id').value,
        motivo: document.getElementById('edit_motivo').value,
        fecha_suspension: document.getElementById('edit_suspension').value,
        estatus_entrevista: document.getElementById('edit_estatus').value
    };
    fetch('ajax/actualizar_entrevista.php',{
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body:JSON.stringify(data)
    }).then(r=>r.json()).then(res=>{
        if(res.success){
            modalEntrevista.hide();
            cargarEntrevistas();
        } else {
            alert("Error al guardar cambios: "+res.error);
        }
    });
}

function limpiarFiltros(){
    document.getElementById('filtro_suspension').value="";
    document.getElementById('filtro_estatus').value="";
    document.getElementById('filtro_turno').value="";
    cargarEntrevistas();
}

// --------- CALENDARIO DINÁMICO ----------
function renderCalendario(){
    const y = fechaActual.getFullYear();
    const m = fechaActual.getMonth();

    const mesNombre = fechaActual.toLocaleDateString('es-MX',{month:'long',year:'numeric'});
    document.getElementById('calendarioTitulo').innerText = mesNombre;

    const cont = document.getElementById('calendario');
    cont.innerHTML = '';

    const primerDia = new Date(y,m,1).getDay(); // 0=Domingo
    const totalDias = new Date(y,m+1,0).getDate();

    // Ajuste para empezar lunes
    let offset = primerDia === 0 ? 6 : primerDia-1;
    for(let i=0;i<offset;i++) cont.appendChild(document.createElement('div'));

    for(let d=1; d<=totalDias; d++){
        const fecha = `${y}-${String(m+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
        const div = document.createElement('div');
        div.className='cal-dia';
        div.innerHTML=`<strong>${d}</strong><div class="cal-total" id="t-${fecha}">🔴 0</div><div class="cal-turno" id="l-${fecha}">🔵 0</div>`;
        div.onclick=()=>seleccionarFecha(fecha,div);
        cont.appendChild(div);
    }

    cargarConteoCalendario(y,m+1);
}

function cambiarMes(v){
    fechaActual.setMonth(fechaActual.getMonth()+v);
    renderCalendario();
}

function seleccionarFecha(fecha,div){
    document.querySelectorAll('.cal-dia').forEach(d=>d.classList.remove('activo'));
    div.classList.add('activo');
    document.getElementById('edit_suspension').value = fecha;
}

function cargarConteoCalendario(anio,mes){
    fetch('ajax/calendario_suspensiones.php',{
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body:JSON.stringify({anio, mes, turno:turnoEmpleado, linea:lineaEmpleado})
    }).then(r=>r.json()).then(data=>{
        data.forEach(x=>{
            const t=document.getElementById(`t-${x.fecha}`);
            const l=document.getElementById(`l-${x.fecha}`);
            if(t) t.innerText=`🔴 ${x.total}`;
            if(l) l.innerText=`🔵 ${x.turno_linea}`;
        });
    });
}

window.onload = cargarEntrevistas;
</script>
</body>
</html>
