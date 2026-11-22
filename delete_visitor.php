<?php
/**
 * FILE: delete_visitor.php
 * PURPOSE: Admin-only. Delete one visit record (active session or past log).
 *   "source" tells which table the row came from, because active_sessions and
 *   library_logs have separate IDs (id 12 in one table is a different person in the other).
 */
header('Content-Type: application/json');
require_once 'auth.php';
require_admin_api();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
    exit;
}

$id     = (int) ($_POST['id'] ?? 0);
$source = $_POST['source'] ?? '';
$tables = $source === 'active' ? ['active_sessions']
        : ($source === 'log'   ? ['library_logs'] : ['active_sessions', 'library_logs']);

$id_number = null;
$deleted   = false;

try {
    foreach ($tables as $table) {
        // Remember the ID number for masterlist cleanup
        $find = $conn->prepare("SELECT id_number FROM $table WHERE id = ?");
        $find->bind_param("i", $id);
        $find->execute();
        if ($row = $find->get_result()->fetch_assoc()) $id_number = $row['id_number'];

        $del = $conn->prepare("DELETE FROM $table WHERE id = ?");
        $del->bind_param("i", $id);
        $del->execute();
        if ($del->affected_rows > 0) { $deleted = true; break; }
    }

    // Remove from masterlist when the person has no remaining records
    if ($id_number) {
        $clean = $conn->prepare("
            DELETE FROM visitor_masterlist
            WHERE id_number = ?
              AND NOT EXISTS (SELECT 1 FROM active_sessions WHERE id_number = ?)
              AND NOT EXISTS (SELECT 1 FROM library_logs   WHERE id_number = ?)");
        $clean->bind_param("sss", $id_number, $id_number, $id_number);
        $clean->execute();
    }

    echo json_encode($deleted ? ['status' => 'success'] : ['status' => 'error', 'message' => 'No record found.']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Delete failed.']);
}
