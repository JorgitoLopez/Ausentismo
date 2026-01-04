<?php
require_once 'config/db.php';

$fecha_hoy = date('Y-m-d');

// Catálogos para filtros y formulario
$turnos = $pdo->query("SELECT * FROM turnos ORDER BY id_turno")->fetchAll(PDO::FETCH_ASSOC);
$lineas = $pdo->query("SELECT * FROM lineas ORDER BY nombre_linea")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RH System | Gestión de Suspensiones</title>
    <link href="bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root { --sidebar-bg: #ffffff; --body-bg: #f4f6f9; --accent-color: #0d6efd; }
        body { background-color: var(--body-bg); font-size: 0.88rem; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .sidebar { background: var(--sidebar-bg); border-right: 1px solid #dee2e6; min-height: 100vh; }
        .sticky-sidebar { position: sticky; top: 80px; }
        .card { border: none; border-radius: 8px; box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075); margin-bottom: 1.5rem; }
        .table thead { background-color: #212529; color: #ffffff; }
        
        /* Calendario Dinámico */
        .cal-dia { border: 1px solid #dee2e6; border-radius: 6px; padding: 6px; min-height: 70px; font-size: 0.75rem; cursor: pointer; background-color: #fff; transition: all 0.2s ease; display: flex; flex-direction: column; justify-content: space-between; }
        .cal-dia:hover { background-color: #f8f9fa; border-color: #adb5bd; }
        .cal-dia.activo { background-color: #e7f1ff !important; border: 2px solid var(--accent-color) !important; transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .cal-total { color: #dc3545; font-size: 0.65rem; font-weight: 600; }
        .cal-turno { color: #0d6efd; font-size: 0.65rem; font-weight: 600; }
        .cal-header { font-weight: bold; text-align: center; font-size: 0.75rem; color: #495057; margin-bottom: 8px; text-transform: uppercase; }
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
<li class="nav-item"><a class="nav-link" href="entrevistas.php">Entrevistas</a></li>
<li class="nav-item"><a class="nav-link active" href="suspensiones.php">Suspensiones</a></li>
</ul>
</div>
</div>
</nav>

<div class="container-fluid" style="margin-top:75px">
    <div class="row">
        <div class="col-md-3 col-lg-2 sidebar p-3 shadow-sm">
            <div class="sticky-sidebar">
                <h6 class="fw-bold mb-3 border-bottom pb-2"><i class="bi bi-filter-left"></i> FILTROS</h6>
                
                <div class="mb-3">
                    <label class="form-label fw-bold small">Mes de Suspensión</label>
                    <input type="month" id="filtro_mes" class="form-control form-control-sm" value="<?= date('Y-m') ?>" onchange="cargarSuspensiones()">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small">Turno</label>
                    <select id="filtro_turno" class="form-select form-select-sm" onchange="cargarSuspensiones()">
                        <option value="">-- Todos --</option>
                        <?php foreach ($turnos as $t): ?>
                            <option value="<?= $t['id_turno'] ?>"><?= $t['nombre_turno'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button class="btn btn-primary btn-sm w-100 mb-2" onclick="nuevoRegistro()">
                    <i class="bi bi-plus-circle"></i> Nueva Suspensión
                </button>
                <button class="btn btn-light btn-sm w-100 border text-muted" onclick="location.reload()">
                    <i class="bi bi-arrow-clockwise"></i> Actualizar
                </button>
            </div>
        </div>

        <div class="col-md-9 col-lg-10 p-4">
            <div class="card shadow-sm border-start border-primary border-4 mb-4">
                <div class="card-body py-3 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-primary"><i class="bi bi-calendar-x"></i> Control General de Suspensiones</h5>
                    <div id="contador" class="badge bg-light text-dark border">0 registros</div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Empleado</th>
                                <th class="text-center">Turno/Línea</th>
                                <th class="text-center">Fecha Castigo</th>
                                <th class="text-center">Origen</th>
                                <th>Motivo</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tabla_suspensiones"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalSuspension" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold" id="modalTitulo">Gestionar Suspensión</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formSuspension">
                    <input type="hidden" id="edit_id">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small">EMPLEADO</label>
                            <select id="edit_empleado" class="form-select form-select-sm" required onchange="actualizarContextoEmpleado()">
                                <option value="">Seleccione empleado...</option>
                                <?php
                                $emps = $pdo->query("SELECT id_empleado, nombre_completo, id_turno, id_linea FROM empleados ORDER BY nombre_completo")->fetchAll(PDO::FETCH_ASSOC);
                                foreach($emps as $e): ?>
                                    <option value="<?= $e['id_empleado'] ?>" data-turno="<?= $e['id_turno'] ?>" data-linea="<?= $e['id_linea'] ?>">
                                        <?= $e['nombre_completo'] ?> (<?= $e['id_empleado'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small">MOTIVO</label>
                            <input type="text" id="edit_motivo" class="form-control form-control-sm" placeholder="Ej: Falta administrativa, Retardo, etc.">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">FECHA DE CASTIGO</label>
                        <div class="border rounded p-2">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <button type="button" class="btn btn-sm btn-light" onclick="cambiarMes(-1)"><i class="bi bi-chevron-left"></i></button>
                                <strong id="calendarioTitulo"></strong>
                                <button type="button" class="btn btn-sm btn-light" onclick="cambiarMes(1)"><i class="bi bi-chevron-right"></i></button>
                            </div>
                            <div class="d-grid" id="calendario" style="grid-template-columns:repeat(7,1fr);gap:6px"></div>
                            <div class="d-flex justify-content-between mt-1">
                                <span class="cal-header">Lu</span><span class="cal-header">Ma</span><span class="cal-header">Mi</span><span class="cal-header">Ju</span>
                                <span class="cal-header">Vi</span><span class="cal-header">Sa</span><span class="cal-header">Do</span>
                            </div>
                        </div>
                        <input type="hidden" id="edit_fecha" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-danger btn-sm me-auto" id="btnEliminar" onclick="eliminarSuspension()" style="display:none">Eliminar</button>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary btn-sm" onclick="guardarSuspension()">Guardar Suspensión</button>
            </div>
        </div>
    </div>
</div>

<script src="bootstrap.bundle.min.js"></script>
<script>
const modalSusp = new bootstrap.Modal(document.getElementById('modalSuspension'));
let fechaActual = new Date();
let turnoSel = null;
let lineaSel = null;

function cargarSuspensiones() {
    const filtros = {
        mes: document.getElementById('filtro_mes').value,
        turno: document.getElementById('filtro_turno').value
    };
    fetch('ajax/listar_todas_suspensiones.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify(filtros)
    })
    .then(r => r.text())
    .then(html => {
        document.getElementById('tabla_suspensiones').innerHTML = html;
        const total = document.querySelectorAll('#tabla_suspensiones tr:not(.no-data)').length;
        document.getElementById('contador').innerText = `${total} registros`;
    });
}

function nuevoRegistro() {
    document.getElementById('formSuspension').reset();
    document.getElementById('edit_id').value = "";
    document.getElementById('btnEliminar').style.display = "none";
    document.getElementById('modalTitulo').innerText = "Nueva Suspensión Administrativa";
    turnoSel = null; lineaSel = null;
    renderCalendario(0, "");
    modalSusp.show();
}

function editarRegistro(id, id_emp, motivo, fecha, turno, linea) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_empleado').value = id_emp;
    document.getElementById('edit_motivo').value = motivo;
    document.getElementById('edit_fecha').value = fecha;
    document.getElementById('btnEliminar').style.display = "block";
    document.getElementById('modalTitulo').innerText = "Editar Suspensión";
    
    turnoSel = turno;
    lineaSel = linea;
    fechaActual = new Date(fecha + "T12:00:00");
    renderCalendario(turnoSel, lineaSel);
    modalSusp.show();
}

function actualizarContextoEmpleado() {
    const opt = document.getElementById('edit_empleado').selectedOptions[0];
    if(opt.value !== "") {
        turnoSel = opt.getAttribute('data-turno');
        lineaSel = opt.getAttribute('data-linea');
        renderCalendario(turnoSel, lineaSel);
    }
}

function guardarSuspension() {
    const data = {
        id: document.getElementById('edit_id').value,
        id_empleado: document.getElementById('edit_empleado').value,
        motivo: document.getElementById('edit_motivo').value,
        fecha: document.getElementById('edit_fecha').value,
        turno: turnoSel,
        linea: lineaSel
    };

    if(!data.id_empleado || !data.fecha) return alert("Empleado y Fecha son obligatorios");

    fetch('ajax/guardar_suspension_manual.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify(data)
    }).then(r => r.json()).then(res => {
        if(res.success) { modalSusp.hide(); cargarSuspensiones(); }
        else alert(res.error);
    });
}

function eliminarSuspension() {
    if(!confirm("¿Eliminar esta suspensión permanentemente?")) return;
    fetch('ajax/eliminar_suspension.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({id: document.getElementById('edit_id').value})
    }).then(r => r.json()).then(res => {
        if(res.success) { modalSusp.hide(); cargarSuspensiones(); }
    });
}

// CALENDARIO (Misma lógica que entrevistas)
function renderCalendario(turno, linea){
    const y = fechaActual.getFullYear();
    const m = fechaActual.getMonth();
    document.getElementById('calendarioTitulo').innerText = fechaActual.toLocaleDateString('es-MX',{month:'long',year:'numeric'});
    const cont = document.getElementById('calendario');
    cont.innerHTML = '';
    const primerDia = new Date(y,m,1).getDay();
    const totalDias = new Date(y,m+1,0).getDate();
    let offset = primerDia === 0 ? 6 : primerDia-1;
    for(let i=0;i<offset;i++) cont.appendChild(document.createElement('div'));

    for(let d=1; d<=totalDias; d++){
        const fecha = `${y}-${String(m+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
        const div = document.createElement('div');
        div.className='cal-dia';
        if(document.getElementById('edit_fecha').value === fecha) div.classList.add('activo');
        div.innerHTML=`<strong>${d}</strong><div class="cal-total" id="t-${fecha}">T: 0</div><div class="cal-turno" id="l-${fecha}">L: 0</div>`;
        div.onclick=()=> {
            document.querySelectorAll('.cal-dia').forEach(x=>x.classList.remove('activo'));
            div.classList.add('activo');
            document.getElementById('edit_fecha').value = fecha;
        };
        cont.appendChild(div);
    }
    cargarConteoCalendario(y, m+1, turno, linea);
}

function cambiarMes(v) { fechaActual.setMonth(fechaActual.getMonth()+v); renderCalendario(turnoSel, lineaSel); }

function cargarConteoCalendario(anio, mes, turno, linea){
    fetch('ajax/calendario_suspensiones.php',{
        method:'POST', headers:{'Content-Type':'application/json'},
        body:JSON.stringify({anio, mes, turno, linea})
    }).then(r=>r.json()).then(data=>{
        data.forEach(x=>{
            const t=document.getElementById(`t-${x.fecha}`);
            const l=document.getElementById(`l-${x.fecha}`);
            if(t) t.innerText = `T: ${x.total_turno}`;
            if(l) l.innerText = `L: ${x.total_linea}`;
        });
    });
}

window.onload = cargarSuspensiones;
</script>
</body>
</html>