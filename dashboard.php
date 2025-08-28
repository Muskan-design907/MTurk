<?php
session_start();
require 'db.php';
 
// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
 
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];
$user_role = $_SESSION['role'];
 
// Fetch tasks based on role
if ($user_role === 'worker') {
    // Tasks assigned or completed by this worker
    $stmt = $pdo->prepare("
        SELECT t.*, ut.status 
        FROM user_tasks ut
        JOIN tasks t ON ut.task_id = t.id
        WHERE ut.user_id = ?
        ORDER BY t.deadline ASC
    ");
    $stmt->execute([$user_id]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
 
    // Calculate total earnings for this worker (sum of completed tasks payments)
    $earnings_stmt = $pdo->prepare("SELECT IFNULL(SUM(t.payment),0) as total FROM user_tasks ut JOIN tasks t ON ut.task_id = t.id WHERE ut.user_id = ? AND ut.status = 'completed'");
    $earnings_stmt->execute([$user_id]);
    $earnings = $earnings_stmt->fetchColumn();
 
} else if ($user_role === 'requester') {
    // Tasks posted by this requester
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE requester_id = ? ORDER BY deadline ASC");
    $stmt->execute([$user_id]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
 
    // Total payments spent by requester
    $payments_stmt = $pdo->prepare("SELECT IFNULL(SUM(payment),0) FROM tasks WHERE requester_id = ?");
    $payments_stmt->execute([$user_id]);
    $payments = $payments_stmt->fetchColumn();
} else {
    $tasks = [];
}
 
// Logout handling
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}
?>
 
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Dashboard - <?php echo htmlspecialchars($user_name); ?></title>
<style>
    body { font-family: Arial, sans-serif; background:#f7f7f7; margin:0; padding:0; }
    header { background:#0073e6; color:#fff; padding:15px 20px; display:flex; justify-content: space-between; align-items: center; }
    header h2 { margin:0; }
    a.logout { color:#fff; text-decoration:none; background:#005bb5; padding:6px 12px; border-radius:4px; }
    a.logout:hover { background:#003f7f; }
    main { max-width: 900px; margin: 20px auto; background:#fff; padding:20px; border-radius:8px; box-shadow: 0 0 10px rgba(0,0,0,0.1);}
    h3 { margin-top: 0; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background:#0073e6; color:#fff; }
    .btn { background:#0073e6; color:#fff; padding:6px 12px; border:none; border-radius:4px; cursor:pointer; text-decoration:none; }
    .btn:hover { background:#005bb5; }
    .info-box { background:#e7f1ff; border:1px solid #c2dbff; padding:10px; margin-bottom: 20px; border-radius:4px; font-weight:bold; }
    @media (max-width:600px) {
        main { margin: 10px; padding: 10px; }
        table, th, td { font-size: 14px; }
    }
</style>
</head>
<body>
<header>
    <h2>Hello, <?php echo htmlspecialchars($user_name); ?> (<?php echo htmlspecialchars(ucfirst($user_role)); ?>)</h2>
    <a href="?logout=1" class="logout">Logout</a>
</header>
<main>
    <?php if ($user_role === 'worker'): ?>
        <div class="info-box">
            Total Earnings: $<?php echo number_format($earnings, 2); ?>
        </div>
        <h3>Your Tasks</h3>
        <?php if (count($tasks) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Task</th>
                    <th>Description</th>
                    <th>Payment ($)</th>
                    <th>Deadline</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tasks as $task): ?>
                <tr>
                    <td><?php echo htmlspecialchars($task['title']); ?></td>
                    <td><?php echo htmlspecialchars($task['description']); ?></td>
                    <td><?php echo number_format($task['payment'], 2); ?></td>
                    <td><?php echo htmlspecialchars($task['deadline']); ?></td>
                    <td><?php echo htmlspecialchars(ucfirst($task['status'])); ?></td>
                    <td>
                        <?php if ($task['status'] === 'accepted'): ?>
                            <a href="complete_task.php?id=<?php echo $task['task_id'] ?? $task['id']; ?>" class="btn">Mark Complete</a>
                        <?php elseif ($task['status'] === 'completed'): ?>
                            Completed
                        <?php else: ?>
                            N/A
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p>You have no tasks assigned yet.</p>
        <?php endif; ?>
 
    <?php elseif ($user_role === 'requester'): ?>
        <div class="info-box">
            Total Spent: $<?php echo number_format($payments, 2); ?>
        </div>
        <h3>Your Posted Tasks</h3>
        <p><a href="post_task.php" class="btn">Post New Task</a></p>
        <?php if (count($tasks) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Task</th>
                    <th>Description</th>
                    <th>Payment ($)</th>
                    <th>Deadline</th>
                    <th>Status</th>
                    <th>Applicants</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tasks as $task): ?>
                <tr>
                    <td><?php echo htmlspecialchars($task['title']); ?></td>
                    <td><?php echo htmlspecialchars($task['description']); ?></td>
                    <td><?php echo number_format($task['payment'], 2); ?></td>
                    <td><?php echo htmlspecialchars($task['deadline']); ?></td>
                    <td><?php echo htmlspecialchars(ucfirst($task['status'] ?? 'open')); ?></td>
                    <td>
                        <?php
                        $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM user_tasks WHERE task_id = ?");
                        $stmt2->execute([$task['id']]);
                        echo $stmt2->fetchColumn();
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p>You have not posted any tasks yet.</p>
        <?php endif; ?>
 
    <?php else: ?>
        <p>Unknown role.</p>
    <?php endif; ?>
</main>
</body>
</html>
 
