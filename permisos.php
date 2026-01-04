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

?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>RH System | Permisos de Entrada</title>
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

        /* Navbar Custom */

        .navbar { box-shadow: 0 2px 4px rgba(0,0,0,0.1); }

        .navbar-brand { font-size: 1.25rem; letter-spacing: 1px; }

        /* Ajuste para el layout */

        .main-content { padding: 2rem; }
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
<li class="nav-item"><a class="nav-link active" href="permisos.php">Permisos</a></li>
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
<input type="date" id="filtro_fecha" class="form-control form-control-sm" value="<?= $fecha_hoy ?>" onchange="cargarPermisos()">
</div>
<div class="mb-3">
<label class="form-label fw-bold small">Turno / Horario</label>
<select id="filtro_turno" class="form-select form-select-sm" onchange="cargarPermisos()">
<option value="">-- Todos los turnos --</option>
<?php foreach ($turnos as $t): ?>
<option value="<?= $t['id_turno'] ?>"><?= $t['id_turno'] ?></option>
<?php endforeach; ?>
</select>
</div>
<div class="mb-3">
<label class="form-label fw-bold small">Departamento</label>
<select id="filtro_departamento" class="form-select form-select-sm" onchange="cargarPermisos()">
<option value="">-- Todos --</option>
<?php foreach ($departamentos as $d): ?>
<option value="<?= $d['id_departamento'] ?>"><?= $d['id_departamento'] ?></option>
<?php endforeach; ?>
</select>
</div>
<div class="mt-4">
<button class="btn btn-outline-secondary btn-sm w-100" onclick="limpiarFiltros()">
<i class="bi bi-arrow-clockwise"></i> Resetear Filtros
</button>
</div>
<div class="alert alert-info mt-4 py-2 small border-0 shadow-sm">
<i class="bi bi-info-circle-fill"></i> Esta vista muestra solo personal con <b>Permiso de Llegada Tarde</b>.
</div>
</div>
</div>
<div class="col-md-9 col-lg-10 p-4">
<div class="card shadow-sm mb-4 border-start border-primary border-4">
<div class="card-body py-3">
<div class="d-flex justify-content-between align-items-center">
<div>
<h5 class="fw-bold mb-0 text-primary"><i class="bi bi-clock-history"></i> Control de Permisos de Entrada</h5>
<p class="text-muted small mb-0">Visualización de personal autorizado con llegada tarde en la asistencia diaria.</p>
</div>
<div id="contador_registros" class="badge bg-light text-dark border"></div>
</div>
</div>
</div>
<div class="card shadow-sm">
<div class="card-header bg-white py-3">
<h6 class="fw-bold mb-0 text-dark">Registros de Permisos Autorizados</h6>
</div>
<div class="card-body p-0">
<div class="table-responsive">
<table class="table table-hover align-middle mb-0">
<thead>
<tr>
<th class="text-center" width="60">#</th>
<th>Información del Empleado</th>
<th class="text-center">Estatus General</th>
<th class="text-center">Motivo RH</th>
<th class="text-center">Hora Reloj</th>
</tr>
</thead>
<tbody id="tabla_permisos">
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

 * Carga principal de la tabla de permisos filtrando por fecha, turno y departamento

 */

function cargarPermisos() {

    const dataFiltros = {

        fecha: document.getElementById('filtro_fecha').value,

        turno: document.getElementById('filtro_turno').value,

        departamento: document.getElementById('filtro_departamento').value

    };

    const tbody = document.getElementById('tabla_permisos');

    fetch('ajax/filtrar_permisos.php', {

        method: 'POST',

        headers: {'Content-Type': 'application/json'},

        body: JSON.stringify(dataFiltros)

    })

    .then(r => r.text())

    .then(html => {

        tbody.innerHTML = html;

        // Contar filas reales (excluyendo la fila de "no hay datos")

        const totalRows = tbody.querySelectorAll('tr:not(.no-data)').length;

        document.getElementById('contador_registros').innerText = `Mostrando ${totalRows} permisos`;

    })

    .catch(err => {

        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error al cargar datos.</td></tr>';

        console.error("Error en el filtrado:", err);

    });

}

/**

 * Resetea los filtros y recarga la tabla

 */

function limpiarFiltros() {

    document.getElementById('filtro_turno').value = "";

    document.getElementById('filtro_departamento').value = "";

    cargarPermisos();

}

// Inicialización al cargar la página

window.onload = () => {

    cargarPermisos();

};
</script>
</body>
</html>
 