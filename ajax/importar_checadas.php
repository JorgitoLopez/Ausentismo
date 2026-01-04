<?php

session_start();

require_once '../config/db.php';

header('Content-Type: application/json');

$response = [

    'success' => false,

    'finalizado' => false,

    'procesados' => 0,

    'total' => 0,

    'insertados' => 0,

    'descartados' => 0,

    'motivos' => [

        'duplicados' => 0,

        'estructura' => 0,

        'fecha' => 0,

        'hora' => 0,

        'empleado' => 0

    ]

];

/* =========================

   INICIALIZAR IMPORTACIÓN

========================= */

if (!isset($_SESSION['import_prn'])) {

    if (!isset($_FILES['archivo'])) {

        $response['mensaje'] = 'Archivo no recibido';

        echo json_encode($response);

        exit;

    }

    $lineas = file($_FILES['archivo']['tmp_name'], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    $_SESSION['import_prn'] = [

        'lineas' => $lineas,

        'pos' => 0,

        'insertados' => 0,

        'descartados' => 0,

        'motivos' => [

            'duplicados' => 0,

            'estructura' => 0,

            'fecha' => 0,

            'hora' => 0,

            'empleado' => 0

        ],

        'archivo' => $_FILES['archivo']['name']

    ];

}

/* =========================

   PROCESAR BLOQUE

========================= */

$batch = 50;

$datos = &$_SESSION['import_prn'];

$total = count($datos['lineas']);

$stmt = $pdo->prepare("

    INSERT IGNORE INTO checadas

    (id_empleado, fecha, hora, archivo_origen)

    VALUES (?, ?, ?, ?)

");

for ($i = 0; $i < $batch && $datos['pos'] < $total; $i++, $datos['pos']++) {

    $linea = $datos['lineas'][$datos['pos']];

    $partes = explode(',', trim($linea));

    if (count($partes) < 8) {

        $datos['descartados']++;

        $datos['motivos']['estructura']++;

        continue;

    }

    // FECHA

    $fechaRaw = trim($partes[4]);

    if (!preg_match('/^\d{8}$/', $fechaRaw)) {

        $datos['descartados']++;

        $datos['motivos']['fecha']++;

        continue;

    }

    $fecha = substr($fechaRaw,0,4).'-'.substr($fechaRaw,4,2).'-'.substr($fechaRaw,6,2);

    // HORA

    $horaRaw = trim($partes[5]);

    if (!preg_match('/^\d{6}$/', $horaRaw)) {

        $datos['descartados']++;

        $datos['motivos']['hora']++;

        continue;

    }

    $hora = substr($horaRaw,0,2).':'.substr($horaRaw,2,2).':'.substr($horaRaw,4,2);

    // EMPLEADO

    $empRaw = trim($partes[7]);

    $numerico = substr($empRaw, 2);

    $idEmpleado = (int) ltrim($numerico, '0');

    if ($idEmpleado <= 0) {

        $datos['descartados']++;

        $datos['motivos']['empleado']++;

        continue;

    }

    $stmt->execute([

        $idEmpleado,

        $fecha,

        $hora,

        $datos['archivo']

    ]);

    if ($stmt->rowCount() > 0) {

        $datos['insertados']++;

    } else {

        $datos['descartados']++;

        $datos['motivos']['duplicados']++;

    }

}

/* =========================

   RESPUESTA

========================= */

$response['procesados'] = $datos['pos'];

$response['total'] = $total;

$response['insertados'] = $datos['insertados'];

$response['descartados'] = $datos['descartados'];

$response['motivos'] = $datos['motivos'];

if ($datos['pos'] >= $total) {

    $response['success'] = true;

    $response['finalizado'] = true;

    unset($_SESSION['import_prn']);

}

echo json_encode($response);

exit;
 