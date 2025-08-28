<?php
session_start();
require 'db.php';
 
// Check user logged in and role is requester
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'requester') {
    header('Location: index.php');
    exit;
}
 
$user_id = $_SESSION['user_id'];
$errors = [];
$success = '';
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $payment = trim($_POST['payment'] ?? '');
    $deadline = trim($_POST['deadline'] ?? '');
 
    if (!$title || !$description || !$category || !$payment || !$deadline) {
        $errors[] = "All fields are required.";
    } elseif (!is_numeric($payment) || $payment <= 0) {
        $errors[] = "Payment must be a positive number.";
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $deadline)) {
        $errors[] = "Deadline must be a valid date (YYYY-MM-DD).";
    } else {
        // Insert task
        $stmt = $pdo->prepare("INSERT INTO tasks (requester_id, title, description, category, payment, deadline) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$user_id, $title, $description, $category, $payment, $deadline])) {
            $success = "Task posted successfully!";
        } else {
            $errors[] = "Failed to post task. Try again.";
        }
    }
}
?>
 
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Post New Task</title>
<style>
    body { font-family: Arial, sans-serif; background:#f7f7f7; margin:0; padding:0; }
    header { background:#0073e6; color:#fff; padding:15px 20px; display:flex; justify-content: space-between; align-items: center; }
    header h2 { margin:0; }
    a.btn, button.btn { background:#0073e6; color:#fff; padding:8px 15px; border:none; border-radius:4px; cursor:pointer; text-decoration:none; }
    a.btn:hover, button.btn:hover { background:#005bb5; }
    main { max-width: 600px; margin: 20px auto; background:#fff; padding:20px; border-radius:8px; box-shadow: 0 0 10px rgba(0,0,0,0.1);}
    form label { display:block; margin-top: 15px; }
    form input[type="text"],
    form input[type="date"],
    form input[type="number"],
    form textarea,
    form select { width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ccc; border-radius:4px; box-sizing: border-box; }
    form textarea { resize: vertical; height: 100px; }
    .error { background:#fdd; color:#900; padding: 10px; margin-bottom: 10px; border-radius:4px;}
    .success { background:#dfd; color:#090; padding: 10px; margin-bottom: 10px; border-radius:4px;}
    @media (max-width:600px) {
        main { margin: 10px; padding: 10px; }
    }
</style>
</head>
<body>
<header>
    <h2>Post New Task</h2>
    <a href="dashboard.php" class="btn">Back to Dashboard</a>
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
    <form method="POST" action="">
        <label for="title">Task Title</label>
        <input type="text" id="title" name="title" required value="<?php echo htmlspecialchars($_POST['title'] ?? '') ?>" />
 
        <label for="description">Description</label>
        <textarea id="description" name="description" required><?php echo htmlspecialchars($_POST['description'] ?? '') ?></textarea>
 
        <label for="category">Category</label>
        <select id="category" name="category" required>
            <option value="" disabled <?php if(empty($_POST['category'])) echo 'selected'; ?>>Select category</option>
            <option value="Data Entry" <?php if(($_POST['category'] ?? '') == 'Data Entry') echo 'selected'; ?>>Data Entry</option>
            <option value="Surveys" <?php if(($_POST['category'] ?? '') == 'Surveys') echo 'selected'; ?>>Surveys</option>
            <option value="Transcription" <?php if(($_POST['category'] ?? '') == 'Transcription') echo 'selected'; ?>>Transcription</option>
            <option value="Image Tagging" <?php if(($_POST['category'] ?? '') == 'Image Tagging') echo 'selected'; ?>>Image Tagging</option>
            <option value="Other" <?php if(($_POST['category'] ?? '') == 'Other') echo 'selected'; ?>>Other</option>
        </select>
 
        <label for="payment">Payment Amount ($)</label>
        <input type="number" step="0.01" min="0.01" id="payment" name="payment" required value="<?php echo htmlspecialchars($_POST['payment'] ?? '') ?>" />
 
        <label for="deadline">Deadline</label>
        <input type="date" id="deadline" name="deadline" required value="<?php echo htmlspecialchars($_POST['deadline'] ?? '') ?>" />
 
        <button type="submit" class="btn">Post Task</button>
    </form>
</main>
</body>
</html>
 
