<?php
session_start();
require 'db.php';
 
// Only logged in workers allowed
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'worker') {
    header('Location: index.php');
    exit;
}
 
$user_id = $_SESSION['user_id'];
$errors = [];
$success = '';
 
// Handle task application
if (isset($_GET['apply']) && is_numeric($_GET['apply'])) {
    $task_id = (int)$_GET['apply'];
 
    // Check if task exists and is open
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ? AND status = 'open'");
    $stmt->execute([$task_id]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
 
    if (!$task) {
        $errors[] = "Task not found or is no longer open.";
    } else {
        // Check if user already applied
        $stmt2 = $pdo->prepare("SELECT * FROM user_tasks WHERE user_id = ? AND task_id = ?");
        $stmt2->execute([$user_id, $task_id]);
        if ($stmt2->rowCount() > 0) {
            $errors[] = "You have already applied for this task.";
        } else {
            // Insert application
            $stmt3 = $pdo->prepare("INSERT INTO user_tasks (user_id, task_id, status) VALUES (?, ?, 'accepted')");
            if ($stmt3->execute([$user_id, $task_id])) {
                $success = "You have successfully applied to the task: " . htmlspecialchars($task['title']);
            } else {
                $errors[] = "Failed to apply for the task.";
            }
        }
    }
}
 
// Filtering by category
$filter_category = $_GET['category'] ?? '';
 
if ($filter_category) {
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE status = 'open' AND category = ? ORDER BY deadline ASC");
    $stmt->execute([$filter_category]);
} else {
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE status = 'open' ORDER BY deadline ASC");
    $stmt->execute();
}
 
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
 
// Get distinct categories for filter dropdown
$cat_stmt = $pdo->query("SELECT DISTINCT category FROM tasks WHERE status = 'open'");
$categories = $cat_stmt->fetchAll(PDO::FETCH_COLUMN);
 
?>
 
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Task Marketplace</title>
<style>
    body { font-family: Arial, sans-serif; background:#f7f7f7; margin:0; padding:0; }
    header { background:#0073e6; color:#fff; padding:15px 20px; display:flex; justify-content: space-between; align-items: center; }
    header h2 { margin:0; }
    a.btn, button.btn { background:#0073e6; color:#fff; padding:8px 15px; border:none; border-radius:4px; cursor:pointer; text-decoration:none; }
    a.btn:hover, button.btn:hover { background:#005bb5; }
    main { max-width: 900px; margin: 20px auto; background:#fff; padding:20px; border-radius:8px; box-shadow: 0 0 10px rgba(0,0,0,0.1);}
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background:#0073e6; color:#fff; }
    .error { background:#fdd; color:#900; padding: 10px; margin-bottom: 10px; border-radius:4px;}
    .success { background:#dfd; color:#090; padding: 10px; margin-bottom: 10px; border-radius:4px;}
    form.filter { margin-bottom: 15px; }
    @media (max-width:600px) {
        main { margin: 10px; padding: 10px; }
        table, th, td { font-size: 14px; }
    }
</style>
</head>
<body>
<header>
    <h2>Task Marketplace</h2>
    <div>
        <a href="dashboard.php" class="btn">Dashboard</a>
        <a href="index.php?logout=1" class="btn">Logout</a>
    </div>
</header>
<main>
    <?php
    if ($errors) {
        echo '<div class="error"><ul>';
        foreach ($errors as $err) {
            echo '<li>'.htmlspecialchars($err).'</li>';
        }
        echo '</ul></div>';
    }
    if ($success) {
        echo '<div class="success">'.htmlspecialchars($success).'</div>';
    }
    ?>
 
    <form method="GET" class="filter">
        <label for="category">Filter by Category:</label>
        <select id="category" name="category" onchange="this.form.submit()">
            <option value="">All Categories</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?php echo htmlspecialchars($cat); ?>" <?php if ($filter_category === $cat) echo 'selected'; ?>><?php echo htmlspecialchars($cat); ?></option>
            <?php endforeach; ?>
        </select>
    </form>
 
    <?php if (count($tasks) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Task</th>
                    <th>Description</th>
                    <th>Category</th>
                    <th>Payment ($)</th>
                    <th>Deadline</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tasks as $task): ?>
                <tr>
                    <td><?php echo htmlspecialchars($task['title']); ?></td>
                    <td><?php echo htmlspecialchars($task['description']); ?></td>
                    <td><?php echo htmlspecialchars($task['category']); ?></td>
                    <td><?php echo number_format($task['payment'], 2); ?></td>
                    <td><?php echo htmlspecialchars($task['deadline']); ?></td>
                    <td><a href="?apply=<?php echo (int)$task['id']; ?>" class="btn" onclick="return confirm('Apply to this task?');">Apply</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No tasks available right now.</p>
    <?php endif; ?>
</main>
</body>
</html>
 
