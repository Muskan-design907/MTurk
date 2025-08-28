<?php
session_start();
require 'db.php';
 
// Redirect logged in users to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
 
$errors = [];
$success = '';
 
// Handle registration
if (isset($_POST['register'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';
 
    if (!$name || !$email || !$password || !$role) {
        $errors[] = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    } else {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $errors[] = "Email already registered.";
        } else {
            // Insert user
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$name, $email, $hash, $role])) {
                $success = "Registration successful. Please log in.";
            } else {
                $errors[] = "Registration failed. Try again.";
            }
        }
    }
}
 
// Handle login
if (isset($_POST['login'])) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
 
    if (!$email || !$password) {
        $errors[] = "Please enter email and password.";
    } else {
        $stmt = $pdo->prepare("SELECT id, password, name, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && password_verify($password, $user['password'])) {
            // Login success
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            header('Location: dashboard.php');
            exit;
        } else {
            $errors[] = "Invalid email or password.";
        }
    }
}
?>
 
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Micro-tasking Platform - Home</title>
<style>
    body { font-family: Arial, sans-serif; background:#f7f7f7; margin:0; padding:0; }
    header { background:#0073e6; color:#fff; padding:15px 20px; text-align:center; }
    main { max-width: 800px; margin: 20px auto; background:#fff; padding:20px; border-radius:8px; box-shadow: 0 0 10px rgba(0,0,0,0.1);}
    h1 { margin-top:0; }
    form { margin-top: 20px; }
    input, select { width: 100%; padding: 8px; margin: 6px 0 12px 0; box-sizing: border-box; border: 1px solid #ccc; border-radius:4px;}
    button { background:#0073e6; color:#fff; border:none; padding:10px; cursor:pointer; border-radius:4px; width: 100%; font-size: 16px;}
    button:hover { background:#005bb5; }
    .tabs { display:flex; margin-top: 20px; }
    .tab { flex:1; padding:10px; text-align:center; cursor:pointer; background:#ddd; border-radius:4px 4px 0 0; }
    .tab.active { background:#0073e6; color:#fff; }
    .tab-content { border:1px solid #ddd; border-top:none; padding: 20px; border-radius:0 0 4px 4px; background:#fff; }
    .error { background:#fdd; color:#900; padding: 10px; margin-bottom: 10px; border-radius:4px;}
    .success { background:#dfd; color:#090; padding: 10px; margin-bottom: 10px; border-radius:4px;}
    @media (max-width:600px) {
        main { margin: 10px; padding: 10px; }
        button { font-size:14px; }
    }
</style>
<script>
    function showTab(tabName) {
        document.getElementById('login-tab').classList.remove('active');
        document.getElementById('register-tab').classList.remove('active');
        document.getElementById('login-content').style.display = 'none';
        document.getElementById('register-content').style.display = 'none';
        document.getElementById(tabName + '-tab').classList.add('active');
        document.getElementById(tabName + '-content').style.display = 'block';
    }
    window.onload = function(){
        showTab('login'); // default to login
    }
</script>
</head>
<body>
<header>
    <h1>Welcome to Micro-tasking Platform</h1>
    <p>Earn money by completing small tasks or post tasks to get them done.</p>
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
    <div class="tabs">
        <div id="login-tab" class="tab" onclick="showTab('login')">Login</div>
        <div id="register-tab" class="tab" onclick="showTab('register')">Register</div>
    </div>
    <div id="login-content" class="tab-content" style="display:none;">
        <form method="POST" action="">
            <label>Email</label>
            <input type="email" name="email" required />
            <label>Password</label>
            <input type="password" name="password" required />
            <button type="submit" name="login">Login</button>
        </form>
    </div>
    <div id="register-content" class="tab-content" style="display:none;">
        <form method="POST" action="">
            <label>Name</label>
            <input type="text" name="name" required />
            <label>Email</label>
            <input type="email" name="email" required />
            <label>Password</label>
            <input type="password" name="password" required />
            <label>I am a:</label>
            <select name="role" required>
                <option value="" disabled selected>Select role</option>
                <option value="worker">Worker (Complete tasks)</option>
                <option value="requester">Requester (Post tasks)</option>
            </select>
            <button type="submit" name="register">Register</button>
        </form>
    </div>
</main>
</body>
</html>
 
