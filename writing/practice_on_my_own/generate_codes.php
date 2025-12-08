<?php
// generate_codes.php - Script para generar códigos para tests existentes
require_once "db_connection.php";

echo "<h2>Generating Access Codes for Existing Tests</h2>";

// Obtener todos los tests sin código
$result = $practice_conn->query("SELECT id, test_number, part FROM writing_tests WHERE access_code IS NULL OR access_code = ''");

if ($result->num_rows > 0) {
    echo "<p>Found {$result->num_rows} tests without codes.</p>";
    echo "<ul>";
    
    while ($row = $result->fetch_assoc()) {
        // Generar código
        $prefix = 'WR' . $row['part'];
        $access_code = generateAccessCode($prefix, 6);
        
        // Actualizar en la base de datos
        $update = $practice_conn->prepare("UPDATE writing_tests SET access_code = ? WHERE id = ?");
        $update->bind_param("si", $access_code, $row['id']);
        
        if ($update->execute()) {
            echo "<li>Test #{$row['test_number']}, Part {$row['part']}: <strong>{$access_code}</strong> - OK</li>";
        } else {
            echo "<li>Test #{$row['test_number']}, Part {$row['part']}: ERROR</li>";
        }
        $update->close();
        
        // Verificar si este test_number ya tiene las 3 partes para generar Full Test Code
        $check_parts = $practice_conn->prepare("SELECT COUNT(DISTINCT part) as part_count FROM writing_tests WHERE test_number = ?");
        $check_parts->bind_param("i", $row['test_number']);
        $check_parts->execute();
        $check_parts->bind_result($part_count);
        $check_parts->fetch();
        $check_parts->close();
        
        if ($part_count >= 3) {
            // Generar código de Full Test
            $full_test_code = generateFullTestCode($row['test_number']);
            
            // Actualizar todas las partes con el mismo código de Full Test
            $update_full = $practice_conn->prepare("UPDATE writing_tests SET full_test_code = ? WHERE test_number = ?");
            $update_full->bind_param("si", $full_test_code, $row['test_number']);
            $update_full->execute();
            $update_full->close();
            
            echo "<li style='margin-left: 20px;'>→ Full Test Code: <strong>{$full_test_code}</strong> (for all 3 parts)</li>";
        }
    }
    echo "</ul>";
    echo "<p style='color: green; font-weight: bold;'>Codes generated successfully!</p>";
} else {
    echo "<p>All tests already have access codes.</p>";
}

// Mostrar resumen
echo "<h3>Summary of All Tests</h3>";
$summary = $practice_conn->query("
    SELECT test_number, 
           GROUP_CONCAT(DISTINCT part ORDER BY part) as parts,
           COUNT(*) as total_parts,
           MAX(access_code) as sample_code,
           MAX(full_test_code) as full_code
    FROM writing_tests 
    GROUP BY test_number 
    ORDER BY test_number ASC
");

echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
echo "<tr><th>Test #</th><th>Parts</th><th>Part Code</th><th>Full Test Code</th></tr>";

while ($row = $summary->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['test_number']}</td>";
    echo "<td>{$row['parts']}</td>";
    echo "<td>{$row['sample_code']}</td>";
    echo "<td>" . ($row['full_code'] ? $row['full_code'] : '<em>Not complete</em>') . "</td>";
    echo "</tr>";
}
echo "</table>";

$practice_conn->close();
?>