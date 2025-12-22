<?php
// admin_actions.php
// Handle AJAX/Form actions for admin panel
session_start();

Check if user is logged in and is a teacher
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header('HTTP/1.1 401 Unauthorized');
    exit('Unauthorized');
}

require_once '../db.php';

// Get the database connection
if (!isset($conn) || !$conn) {
    header('HTTP/1.1 500 Internal Server Error');
    exit('Database connection failed');
}

// Get action from request
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'add_allowed_teacher':
        handleAddAllowedTeacher($conn);
        break;
        
    case 'delete_allowed_teacher':
        handleDeleteAllowedTeacher($conn);
        break;
        
    case 'add_class_code':
        handleAddClassCode($conn);
        break;
        
    case 'delete_class_code':
        handleDeleteClassCode($conn);
        break;
        
    default:
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['error' => 'Invalid action']);
        break;
}

// Function to handle adding allowed teacher
function handleAddAllowedTeacher($conn) {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Email is required']);
        return;
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        return;
    }
    
    // Validate domain
    if (!preg_match('/@adoc\.superate\.org\.sv$/i', $email)) {
        echo json_encode(['success' => false, 'message' => 'Email must be from @adoc.superate.org.sv domain']);
        return;
    }
    
    try {
        $stmt = $conn->prepare("INSERT INTO allowed_teachers (email) VALUES (?)");
        $stmt->bind_param('s', $email);
        
        if ($stmt->execute()) {
            $id = $stmt->insert_id;
            echo json_encode([
                'success' => true, 
                'message' => 'Teacher added successfully',
                'teacher' => [
                    'id' => $id,
                    'email' => $email,
                    'created_at' => date('Y-m-d H:i:s')
                ]
            ]);
        } else {
            // Check if it's a duplicate error
            if ($conn->errno == 1062) {
                echo json_encode(['success' => false, 'message' => 'Teacher email already exists']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add teacher: ' . $conn->error]);
            }
        }
        
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

// Function to handle deleting allowed teacher
function handleDeleteAllowedTeacher($conn) {
    $id = intval($_POST['id'] ?? $_GET['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid teacher ID']);
        return;
    }
    
    try {
        $stmt = $conn->prepare("DELETE FROM allowed_teachers WHERE id = ?");
        $stmt->bind_param('i', $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Teacher deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete teacher']);
        }
        
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

// Function to handle adding class code
function handleAddClassCode($conn) {
    $code = trim($_POST['code'] ?? '');
    $name = trim($_POST['name'] ?? '');
    
    if (empty($code)) {
        echo json_encode(['success' => false, 'message' => 'Class code is required']);
        return;
    }
    
    // Validate code format (max 13 chars, letters and numbers only)
    if (strlen($code) > 13) {
        echo json_encode(['success' => false, 'message' => 'Class code must be maximum 13 characters']);
        return;
    }
    
    if (!preg_match('/^[a-zA-Z0-9]+$/', $code)) {
        echo json_encode(['success' => false, 'message' => 'Class code must contain only letters and numbers']);
        return;
    }
    
    try {
        $stmt = $conn->prepare("INSERT INTO class_codes (code, name) VALUES (?, ?)");
        $stmt->bind_param('ss', $code, $name);
        
        if ($stmt->execute()) {
            $id = $stmt->insert_id;
            echo json_encode([
                'success' => true, 
                'message' => 'Class code created successfully',
                'code' => [
                    'id' => $id,
                    'code' => $code,
                    'name' => $name,
                    'created_at' => date('Y-m-d H:i:s')
                ]
            ]);
        } else {
            // Check if it's a duplicate error
            if ($conn->errno == 1062) {
                echo json_encode(['success' => false, 'message' => 'Class code already exists']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create class code: ' . $conn->error]);
            }
        }
        
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

// Function to handle deleting class code
function handleDeleteClassCode($conn) {
    $id = intval($_POST['id'] ?? $_GET['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid class code ID']);
        return;
    }
    
    try {
        $stmt = $conn->prepare("DELETE FROM class_codes WHERE id = ?");
        $stmt->bind_param('i', $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Class code deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete class code']);
        }
        
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}
?>