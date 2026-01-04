<?php require_once 'config/db.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RH System | Empleados</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

    <style>
        body { background-color: #f4f6f9; font-size: 0.88rem; font-family: 'Segoe UI', Tahoma, sans-serif; padding-top: 80px; }
        .card { border-radius: 15px; border: none; }
        .progress { height: 25px; border-radius: 12px; }
        .stat-val { font-size: 1.5rem; font-weight: bold; }
        .navbar-brand { font-size: 1.25rem; }
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
<li class="nav-item"><a class="nav-link active" href="empleados.php">Empleados</a></li>
<li class="nav-item"><a class="nav-link" href="permisos.php">Permisos</a></li>
<li class="nav-item"><a class="nav-link" href="entrevistas.php">Entrevistas</a></li>
</ul>
</div>
</div>
</nav>

<div class="container mt-4">
    <div class="row justify-content-center mb-5">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-file-earmark-excel me-2"></i>Sincronización Masiva de Personal</h5>
                </div>
                <div class="card-body p-4">
                    <form id="formImportar" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Archivo Excel (.xlsx)</label>
                            <input type="file" name="archivo_excel" class="form-control" required>
                        </div>
                        <button class="btn btn-primary w-100 fw-bold" id="btnSubmit">
                            <i class="bi bi-cloud-arrow-up me-2"></i>Comenzar Importación
                        </button>
                    </form>

                    <div id="procesoBox" class="mt-4" style="display:none;">
                        <hr>
                        <div class="d-flex justify-content-between mb-2">
                            <span id="status_text" class="fw-bold text-primary">Procesando…</span>
                            <span id="pct_text" class="badge bg-primary">0%</span>
                        </div>
                        <div class="progress mb-3">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" id="barra" style="width:0%"></div>
                        </div>
                        <div class="row text-center">
                            <div class="col"><div class="border rounded p-2 bg-light"><div class="small">Insertados</div><div class="stat-val text-success" id="ins_val">0</div></div></div>
                            <div class="col"><div class="border rounded p-2 bg-light"><div class="small">Actualizados</div><div class="stat-val text-warning" id="upd_val">0</div></div></div>
                            <div class="col"><div class="border rounded p-2 bg-light"><div class="small">Eliminados</div><div class="stat-val text-danger" id="del_val">0</div></div></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-header fw-bold bg-white py-3">
            <i class="bi bi-people me-2"></i>Gestión de Empleados
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="tablaEmpleados" class="table table-hover table-sm w-100">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Turno</th>
                            <th>Departamento</th>
                            <th>Línea</th>
                            <th>Ingreso</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEmpleado" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Editar Empleado</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formEditar">
                    <input type="hidden" id="edit_id">
                    <div class="mb-2">
                        <label class="small fw-bold">Nombre Completo</label>
                        <input type="text" class="form-control" id="edit_nombre">
                    </div>
                    <div class="row mb-2">
                        <div class="col">
                            <label class="small fw-bold">Turno</label>
                            <input type="text" class="form-control" id="edit_turno">
                        </div>
                        <div class="col">
                            <label class="small fw-bold">Departamento</label>
                            <input type="text" class="form-control" id="edit_depto">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col">
                            <label class="small fw-bold">Línea</label>
                            <input type="text" class="form-control" id="edit_linea">
                        </div>
                        <div class="col">
                            <label class="small fw-bold">Fecha Ingreso</label>
                            <input type="date" class="form-control" id="edit_fecha">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary px-4" onclick="guardarCambios()">Guardar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
let tabla;

$(document).ready(function() {
    // Inicializar DataTable
    tabla = $('#tablaEmpleados').DataTable({
        processing: true,
        serverSide: true,
        ajax: { url: 'ajax/empleados_datatables.php', type: 'POST' },
        columns: [
            { data: 'id_empleado' },
            { data: 'nombre_completo' },
            { data: 'id_turno' },
            { data: 'id_departamento' },
            { data: 'id_linea' },
            { data: 'fecha_ingreso' },
            { data: 'acciones', orderable: false, searchable: false }
        ],
        language: { url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-MX.json' },
        pageLength: 10
    });
});

/* --- Lógica de Importación --- */
const formImportar = document.getElementById('formImportar');
formImportar.addEventListener('submit', async e => {
    e.preventDefault();
    document.getElementById('btnSubmit').disabled = true;
    document.getElementById('procesoBox').style.display = 'block';

    const fd = new FormData(formImportar);
    try {
        await fetch('ajax/importar_empleados.php', { method:'POST', body:fd });
        ejecutarLote();
    } catch (err) { alert("Error al iniciar importación"); }
});

async function ejecutarLote() {
    const r = await fetch('ajax/importar_empleados.php');
    const d = await r.json();

    const pct = Math.round((d.procesados / d.total) * 100) || 0;
    document.getElementById('barra').style.width = pct + '%';
    document.getElementById('pct_text').innerText = pct + '%';
    document.getElementById('ins_val').innerText = d.insertados;
    document.getElementById('upd_val').innerText = d.actualizados;
    document.getElementById('del_val').innerText = d.eliminados;

    if (!d.finalizado) {
        setTimeout(ejecutarLote, 500); 
    } else {
        document.getElementById('status_text').innerText = 'Sincronización Completa';
        document.getElementById('btnSubmit').disabled = false;
        tabla.ajax.reload(); // Recarga la tabla al terminar
    }
}

/* --- CRUD: Editar --- */
function editar(id) {
    fetch('ajax/empleados_obtener.php?id=' + id)
    .then(r => r.json())
    .then(e => {
        document.getElementById('edit_id').value = e.id_empleado;
        document.getElementById('edit_nombre').value = e.nombre_completo;
        document.getElementById('edit_turno').value = e.id_turno;
        document.getElementById('edit_depto').value = e.id_departamento;
        document.getElementById('edit_linea').value = e.id_linea;
        document.getElementById('edit_fecha').value = e.fecha_ingreso;
        
        let modal = new bootstrap.Modal(document.getElementById('modalEmpleado'));
        modal.show();
    });
}

function guardarCambios() {
    const data = {
        id_empleado: document.getElementById('edit_id').value,
        nombre_completo: document.getElementById('edit_nombre').value,
        id_turno: document.getElementById('edit_turno').value,
        id_departamento: document.getElementById('edit_depto').value,
        id_linea: document.getElementById('edit_linea').value,
        fecha_ingreso: document.getElementById('edit_fecha').value
    };

    fetch('ajax/empleados_update.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(() => {
        bootstrap.Modal.getInstance(document.getElementById('modalEmpleado')).hide();
        tabla.ajax.reload(null, false);
    });
}

/* --- CRUD: Eliminar --- */
function eliminar(id) {
    if (confirm('¿Deseas eliminar permanentemente al empleado ' + id + '?')) {
        fetch('ajax/empleados_eliminar.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_empleado: id })
        })
        .then(() => tabla.ajax.reload(null, false));
    }
}
</script>
</body>
</html>