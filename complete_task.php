<?php
session_start();
require 'db.php';
 
// Only logged in workers
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'worker') {
    header('Location: index.php');
    exit;
}
 
$user_id = $_SESSION['user_id'];
$task_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
 
if ($task_id <= 0) {
    die("Invalid task ID.");
}
 
// Verify this user has accepted this task and status is 'accepted'
$stmt = $pdo->prepare("SELECT * FROM user_tasks WHERE user_id = ? AND task_id = ? AND status = 'accepted'");
$stmt->execute([$user_id, $task_id]);
$assignment = $stmt->fetch(PDO::FETCH_ASSOC);
 
if (!$assignment) {
    die("Task not found or already completed.");
}
 
// Mark task completed
$update = $pdo->prepare("UPDATE user_tasks SET status = 'completed' WHERE id = ?");
if ($update->execute([$assignment['id']])) {
    // Redirect back to dashboard with success message (using GET param)
    header('Location: dashboard.php?msg=task_completed');
    exit;
} else {
    die("Failed to mark task as completed.");
}
?>
 
