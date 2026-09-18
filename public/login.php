<?php
require_once __DIR__ . '/../app/auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf_token($_POST['csrf_token'] ?? '');
        
        $user_id = trim($_POST['user_id'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($user_id) || empty($password)) {
            $error = "Please enter both User ID and password.";
        } elseif (login($user_id, $password)) {
            header('Location: ' . BASE_URL . 'index.php?loggedin=1');
            exit;
        } else {
            $error = "Invalid User ID or password.";
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Login - College Events</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="<?= BASE_URL ?>../assets/css/style.css">
  <script>
      function togglePassword() {
          const passInput = document.getElementById("password");
          if (passInput.type === "password") {
              passInput.type = "text";
          } else {
              passInput.type = "password";
          }
      }
      function showLoading(btn) {
          btn.innerHTML = 'Logging in...';
          btn.style.opacity = '0.7';
          btn.style.pointerEvents = 'none';
          btn.closest('form').submit();
      }
  </script>
</head>
<body>
<div class="container auth-container">
  <h1>Login</h1>
  
  <?php if (isset($_GET['registered']) && $_GET['registered'] == 1): ?>
      <div class="alert alert-success">Registration successful! You can now login.</div>
      <script>alert("Registration successful! You can now login.");</script>
  <?php endif; ?>
  
  <?php if ($error): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
      <script>alert(<?= json_encode("Login unsuccessful: " . $error) ?>);</script>
  <?php endif; ?>

  <form method="post" action="<?= BASE_URL ?>login.php" onsubmit="showLoading(this.querySelector('button[type=submit]'));">
    <?= csrf_field() ?>
    
    <label for="user_id">USN / Staff ID / Email</label>
    <input type="text" id="user_id" name="user_id" value="<?= htmlspecialchars($_POST['user_id'] ?? '') ?>" required>
    
    <label for="password">Password</label>
    <div style="display: flex; gap: 10px; align-items: center;">
        <input type="password" id="password" name="password" style="flex:1; margin-bottom:0;" required>
        <button type="button" class="btn" style="padding: 0.5rem;" onclick="togglePassword()">Show</button>
    </div>
    
    <div class="form-actions" style="margin-top: 15px;">
        <label style="display: inline-flex; align-items: center; font-weight: normal;">
            <input type="checkbox" name="remember" style="margin-right: 5px;"> Remember me
        </label>
    </div>
    
    <div class="form-actions" style="margin-top: 15px;">
        <button type="submit" class="btn">Login</button>
    </div>
    <p style="margin-top: 10px;">Don't have an account? <a href="<?= BASE_URL ?>register.php">Register here</a>.</p>
  </form>
</div>
</body>
</html>