<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

// Filtro para leer solo los datos necesarios y ahorrar memoria
class MyReadFilter implements IReadFilter {
    public function readCell($columnAddress, $row, $worksheetName = '') {
        if ($row > 16) return true; // Solo lee de la fila 17 en adelante
        return false;
    }
}

session_start();
header('Content-Type: application/json');

$chunkSize = 100; // Aumentamos el lote a 100 para menos viajes al servidor

// --- FASE 1: CARGA INICIAL ---
if (isset($_FILES['archivo_excel'])) {
    try {
        $archivoTmp = $_FILES['archivo_excel']['tmp_name'];
        
        // Configuramos el lector para que sea rápido
        $reader = IOFactory::createReaderForFile($archivoTmp);
        $reader->setReadDataOnly(true); // IGNORA ESTILOS (Esto acelera mucho)
        $reader->setReadFilter(new MyReadFilter());
        
        $spreadsheet = $reader->load($archivoTmp);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();
        
        // Limpiamos filas vacías al inicio y final
        $dataRows = array_filter(array_slice($rows, 16), function($r) {
            return !empty($r[0]); 
        });

        $_SESSION['import_task'] = [
            'filas'        => array_values($dataRows),
            'total'        => count($dataRows),
            'insertados'   => 0,
            'actualizados' => 0,
            'eliminados'   => 0,
            'procesados'   => 0,
            'ids_excel'    => [],
            'finalizado'   => false
        ];

        echo json_encode(['status' => 'ready', 'total' => count($dataRows)]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['error' => 'Error al leer Excel: ' . $e->getMessage()]);
        exit;
    }
}

// --- FASE 2: PROCESO POR LOTES ---
if (!isset($_SESSION['import_task'])) {
    echo json_encode(['error' => 'Sesión expirada']);
    exit;
}

$task = &$_SESSION['import_task'];
$lote = array_slice($task['filas'], $task['procesados'], $chunkSize);

if (!empty($lote)) {
    try {
        $pdo->beginTransaction();
        
        // Usamos INSERT IGNORE o ON DUPLICATE KEY
        $sql = "INSERT INTO empleados (id_empleado, nombre_completo, id_departamento, id_turno, id_linea, fecha_ingreso)
                VALUES (?, ?, ?, ?, ?, CURDATE())
                ON DUPLICATE KEY UPDATE
                nombre_completo = VALUES(nombre_completo),
                id_departamento = VALUES(id_departamento),
                id_turno = VALUES(id_turno),
                id_linea = VALUES(id_linea)";
        
        $stmt = $pdo->prepare($sql);

        foreach ($lote as $row) {
            $id = (int)($row[0] ?? 0);
            if ($id <= 0) { $task['procesados']++; continue; }

            $nombre = trim($row[1] ?? '');
            $dep    = !empty($row[2]) ? (int)$row[2] : null;
            $turno  = !empty($row[3]) ? (int)$row[3] : 1;
            $linea  = (!empty($row[4]) || $row[4] === "0") ? trim($row[4]) : null;

            $stmt->execute([$id, $nombre, $dep, $turno, $linea]);
            
            // Conteo de registros
            if ($stmt->rowCount() == 1) $task['insertados']++;
            elseif ($stmt->rowCount() == 2) $task['actualizados']++;

            $task['ids_excel'][] = $id;
            $task['procesados']++;
        }
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}

// --- FASE 3: CIERRE (Optimización de eliminación) ---
if ($task['procesados'] >= $task['total']) {
    if (!empty($task['ids_excel'])) {
        // En lugar de un NOT IN gigante, usamos una tabla temporal si son muchos,
        // pero para optimizar aquí, procesamos la eliminación en un solo bloque.
        $idsString = implode(',', $task['ids_excel']);
        
        // Solo eliminamos si el ID NO está en la lista que acabamos de procesar
        $stmtDel = $pdo->prepare("DELETE FROM empleados WHERE id_empleado NOT IN ($idsString)");
        $stmtDel->execute();
        $task['eliminados'] = $stmtDel->rowCount();
    }
    $task['finalizado'] = true;
}

// Enviamos respuesta
echo json_encode([
    'procesados'   => $task['procesados'],
    'total'        => $task['total'],
    'insertados'   => $task['insertados'],
    'actualizados' => $task['actualizados'],
    'eliminados'   => $task['eliminados'],
    'finalizado'   => $task['finalizado']
]);

if ($task['finalizado']) unset($_SESSION['import_task']);