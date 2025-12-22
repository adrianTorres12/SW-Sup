<?php
// File: writing/practice_on_my_own/update_test_config.php
require_once "db_connection.php";
require_once "../../session_helper.php";

startSecureSession();

if (!isUserLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_role = getUserRole();
if ($user_role !== 'teacher') {
    echo json_encode(['success' => false, 'message' => 'Teachers only']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$action = $_POST['action'] ?? 'update';
$test_number = $_POST['test_number'] ?? null;
$part = $_POST['part'] ?? null;

if (!$test_number) {
    echo json_encode(['success' => false, 'message' => 'Test number required']);
    exit();
}

// ===================================
// BORRAR TEST
// ===================================
if ($action === 'delete') {
    try {
        $practice_conn->begin_transaction();
        
        if ($part === 'full') {
            // No permitir borrar full tests desde aquí
            echo json_encode(['success' => false, 'message' => 'Cannot delete full test directly. Delete individual parts instead.']);
            exit();
        } else {
            // Borrar una parte específica
            // 1. Obtener el test_id
            $query = "SELECT id FROM writing_tests WHERE test_number = ? AND part = ?";
            $stmt = $practice_conn->prepare($query);
            $stmt->bind_param("ii", $test_number, $part);
            $stmt->execute();
            $stmt->bind_result($test_id);
            $stmt->fetch();
            $stmt->close();
            
            if (!$test_id) {
                echo json_encode(['success' => false, 'message' => 'Test not found']);
                exit();
            }
            
            // 2. Borrar preguntas de la parte correspondiente
            if ($part == 1) {
                $delete_q = "DELETE FROM writing_part1_questions WHERE test_id = ?";
            } elseif ($part == 2) {
                $delete_q = "DELETE FROM writing_part2_questions WHERE test_id = ?";
            } elseif ($part == 3) {
                $delete_q = "DELETE FROM writing_part3_questions WHERE test_id = ?";
            }
            
            $delete_stmt = $practice_conn->prepare($delete_q);
            $delete_stmt->bind_param("i", $test_id);
            $delete_stmt->execute();
            $delete_stmt->close();
            
            // 3. Borrar el registro del test
            $delete_test = $practice_conn->prepare("DELETE FROM writing_tests WHERE id = ?");
            $delete_test->bind_param("i", $test_id);
            $delete_test->execute();
            $delete_test->close();
            
            echo json_encode([
                'success' => true, 
                'message' => 'Test part deleted successfully.',
                'action' => 'delete'
            ]);
        }
        
        $practice_conn->commit();
        
    } catch (Exception $e) {
        $practice_conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}
// ===================================
// ACTUALIZAR VISIBILIDAD
// ===================================
else {
    $is_visible = $_POST['is_visible'] ?? 0;

    try {
        $practice_conn->begin_transaction();
        
        if ($part === 'full') {
            // Actualizar todas las partes del full test
            $query = "UPDATE writing_tests 
                      SET is_visible_to_students = ?, 
                          updated_at = CURRENT_TIMESTAMP 
                      WHERE test_number = ?";
            $stmt = $practice_conn->prepare($query);
            $stmt->bind_param("ii", $is_visible, $test_number);
        } else {
            // Actualizar solo una parte específica
            $query = "UPDATE writing_tests 
                      SET is_visible_to_students = ?, 
                          updated_at = CURRENT_TIMESTAMP 
                      WHERE test_number = ? AND part = ?";
            $stmt = $practice_conn->prepare($query);
            $stmt->bind_param("iii", $is_visible, $test_number, $part);
        }
        
        if ($stmt->execute()) {
            $practice_conn->commit();
            echo json_encode(['success' => true, 'message' => 'Configuration updated successfully']);
        } else {
            $practice_conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Update failed: ' . $stmt->error]);
        }
        
        $stmt->close();
    } catch (Exception $e) {
        $practice_conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}
?>