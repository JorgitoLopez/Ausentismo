<?php

require_once 'config/db.php';

$fecha_hoy = date('Y-m-d');

// Obtenemos catálogos para los filtros

$turnos = $pdo->query("SELECT DISTINCT id_turno FROM empleados ORDER BY id_turno")->fetchAll(PDO::FETCH_ASSOC);

$departamentos = $pdo->query("SELECT DISTINCT id_departamento FROM empleados ORDER BY id_departamento")->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Executive Analytics | RH System</title>
<link href="bootstrap.min.css" rel="stylesheet">
<link href="bootstrap-icons.css" rel="stylesheet">
<script src="chart.js"></script>
<script src="chartjs-plugin-datalabels@2.0.0"></script>
<style>

        :root { --glass-bg: rgba(255, 255, 255, 0.9); --primary-hex: #0d6efd; }

        body { background-color: var(--body-bg); font-size: 0.88rem; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }

        /* Sidebar Glassmorphism */

        .sidebar { background: var(--glass-bg); border-right: 1px solid #e3e8ee; min-height: 100vh; backdrop-filter: blur(10px); }

        .sticky-sidebar { position: sticky; top: 80px; height: calc(100vh - 100px); overflow-y: auto; scrollbar-width: thin; }

        /* Cards Estilo Senior */

        .card { border: none; border-radius: 12px; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); background: #ffffff; box-shadow: 0 2px 15px rgba(0,0,0,0.04); }

        .card:hover { transform: translateY(-4px); box-shadow: 0 10px 25px rgba(0,0,0,0.08); }

        .card-header { background: transparent; border-bottom: 1px solid #f0f2f5; padding: 1.25rem; font-weight: 700; color: #4f566b; }

        .metric-card { border-left: 5px solid var(--primary-hex); }

        .chart-container { position: relative; height: 320px; width: 100%; padding: 1rem; }

        /* Custom Checkboxes */

        .form-check-input:checked { background-color: var(--primary-hex); border-color: var(--primary-hex); }

        .filter-label { font-size: 0.75rem; font-weight: 800; color: #8792a2; text-transform: uppercase; letter-spacing: 0.05em; }
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
<li class="nav-item"><a class="nav-link active" href="dashboard.php">Dashboard</a></li>
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
<div class="col-md-3 col-lg-2 sidebar p-4">
<div class="sticky-sidebar">
<h5 class="fw-bold mb-4">Segmentación</h5>
<div class="mb-4">
<label class="filter-label">Rango Temporal</label>
<input type="date" id="f_ini" class="form-control form-control-sm mb-2 shadow-sm" value="<?= date('Y-m-01') ?>">
<input type="date" id="f_fin" class="form-control form-control-sm shadow-sm" value="<?= $fecha_hoy ?>">
</div>
<div class="mb-4">
<label class="filter-label">Filtrar por Turno</label>
<select id="f_turno" class="form-select form-select-sm shadow-sm">
<option value="">Todos los turnos</option>
<?php foreach($turnos as $t): ?>
<option value="<?= $t['id_turno'] ?>">Turno <?= $t['id_turno'] ?></option>
<?php endforeach; ?>
</select>
</div>
<div class="mb-4">
<label class="filter-label">Departamentos</label>
<div class="bg-light p-3 rounded border shadow-sm" style="max-height: 250px; overflow-y: auto;">
<?php foreach($departamentos as $d): ?>
<div class="form-check mb-2">
<input class="form-check-input check-depto" type="checkbox" value="<?= $d['id_departamento'] ?>" id="d_<?= $d['id_departamento'] ?>">
<label class="form-check-label small fw-medium" for="d_<?= $d['id_departamento'] ?>">
<?= $d['id_departamento'] ?>
</label>
</div>
<?php endforeach; ?>
</div>
</div>
<button class="btn btn-primary w-100 fw-bold shadow" onclick="actualizarDashboard()">
<i class="bi bi-lightning-charge-fill"></i> PROCESAR
</button>
</div>
</div>
<div class="col-md-9 col-lg-10 p-4">
<div class="row g-3 mb-4" id="kpi_container">
</div>
<div class="row g-4">
<div class="col-lg-4">
<div class="card h-100">
<div class="card-header">Estructura de Asistencia (%)</div>
<div class="card-body chart-container">
<canvas id="chartDonut"></canvas>
</div>
</div>
</div>
<div class="col-lg-8">
<div class="card h-100">
<div class="card-header">Análisis de Pareto: Causas de Incidencia</div>
<div class="card-body chart-container">
<canvas id="chartPareto"></canvas>
</div>
</div>
</div>
<div class="col-12">
<div class="card">
<div class="card-header">Tendencia Histórica: Faltas vs Justificados</div>
<div class="card-body chart-container" style="height: 400px;">
<canvas id="chartTrend"></canvas>
</div>
</div>
</div>
</div>
</div>
</div>
</div>
<script>

Chart.register(ChartDataLabels);

let charts = {};

async function actualizarDashboard() {

    const selectedDeptos = [...document.querySelectorAll('.check-depto:checked')].map(c => c.value);

    if(selectedDeptos.length === 0) {

        alert("⚠️ Por favor, seleccione al menos un departamento para el análisis.");

        return;

    }

    const payload = {

        inicio: document.getElementById('f_ini').value,

        fin: document.getElementById('f_fin').value,

        turno: document.getElementById('f_turno').value,

        deptos: selectedDeptos

    };

    try {

        const response = await fetch('ajax/obtener_metricas.php', {

            method: 'POST',

            headers: {'Content-Type': 'application/json'},

            body: JSON.stringify(payload)

        });

        const data = await response.json();

        renderData(data);

    } catch (e) {

        console.error("Error fetching data:", e);

    }

}

function renderData(data) {

    // KPI Cards

    const kpis = [

        { label: 'Registros Totales', val: data.stats.total, color: '#0d6efd' },

        { label: '% Asistencia', val: data.stats.porc_asistencia + '%', color: '#198754' },

        { label: '% Ausentismo', val: data.stats.porc_ausente + '%', color: '#dc3545' },

        { label: 'Incidencias RH', val: data.stats.incidencias, color: '#ffc107' }

    ];

    document.getElementById('kpi_container').innerHTML = kpis.map(k => `
<div class="col-md-3">
<div class="card p-3 metric-card" style="border-left-color: ${k.color}">
<div class="small fw-bold text-muted">${k.label}</div>
<div class="h3 fw-bold mb-0">${k.val}</div>
</div>
</div>

    `).join('');

    // Gráfica Donut

    createChart('chartDonut', 'doughnut', {

        labels: data.pastel.labels,

        datasets: [{ data: data.pastel.values, backgroundColor: ['#198754', '#dc3545', '#0d6efd', '#6c757d'] }]

    }, { cutout: '70%', plugins: { datalabels: { color: '#fff', font: { weight: 'bold' } } } });

    // Pareto

    createChart('chartPareto', 'bar', {

        labels: data.pareto.labels,

        datasets: [

            { label: 'Eventos', data: data.pareto.values, backgroundColor: '#4f566b', borderRadius: 5 },

            { label: '% Acumulado', data: data.pareto.acumulado, type: 'line', borderColor: '#ffc107', yAxisID: 'y1', tension: 0.4 }

        ]

    }, { scales: { y1: { position: 'right', max: 100 } } });

    // Trend

    createChart('chartTrend', 'line', {

        labels: data.lineal.labels,

        datasets: [

            { label: 'Injustificados', data: data.lineal.faltas, borderColor: '#dc3545', fill: true, backgroundColor: 'rgba(220, 53, 69, 0.05)' },

            { label: 'Justificados', data: data.lineal.justificados, borderColor: '#0d6efd', tension: 0.3 }

        ]

    });

}

function createChart(id, type, data, options = {}) {

    if(charts[id]) charts[id].destroy();

    charts[id] = new Chart(document.getElementById(id), {

        type: type,

        data: data,

        options: { 

            responsive: true, 

            maintainAspectRatio: false,

            plugins: { legend: { position: 'bottom' } },

            ...options 

        }

    });

}
</script>
</body>
</html>
 