<?php
require_once __DIR__ . '/../app/auth.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf_token($_POST['csrf_token'] ?? '');
        
        signup(
            trim($_POST['name'] ?? ''),
            trim($_POST['user_id'] ?? ''),
            $_POST['role'] ?? '',
            $_POST['department'] ?? '',
            $_POST['year'] ?? '',
            trim($_POST['email'] ?? ''),
            $_POST['password'] ?? ''
        );
        
        header('Location: ' . BASE_URL . 'login.php?registered=1');
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Register - College Events</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="<?= BASE_URL ?>../assets/css/style.css">
  <script>
    function toggleYearField() {
      const role = document.getElementById('role').value;
      const yearField = document.getElementById('year-field');

      if (role === 'teacher' || role === 'organizer') {
        yearField.style.display = 'none';
        yearField.querySelector('select').removeAttribute('required');
      } else {
        yearField.style.display = 'block';
        yearField.querySelector('select').setAttribute('required', 'required');
      }
    }

    document.addEventListener("DOMContentLoaded", toggleYearField);
  </script>
</head>
<body>
<div class="container auth-container">
  <h1>Register</h1>
  
  <?php if ($error): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
      <script>alert(<?= json_encode("Registration unsuccessful: " . $error) ?>);</script>
  <?php endif; ?>
  <form method="post" action="<?= BASE_URL ?>register.php">
    <?= csrf_field() ?>
    
    <label for="name">Full Name</label>
    <input type="text" id="name" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
    
    <label for="user_id">USN / Staff ID</label>
    <input type="text" id="user_id" name="user_id" value="<?= htmlspecialchars($_POST['user_id'] ?? '') ?>" required>
    
    <label for="role">Role</label>
    <select name="role" id="role" required onchange="toggleYearField()">
        <option value="student" <?= ($_POST['role'] ?? '') === 'student' ? 'selected' : '' ?>>Student</option>
        <option value="teacher" <?= ($_POST['role'] ?? '') === 'teacher' ? 'selected' : '' ?>>Teacher</option>
        <option value="organizer" <?= ($_POST['role'] ?? '') === 'organizer' ? 'selected' : '' ?>>Organizer</option>
    </select>

    <label for="department">Department</label>
    <select name="department" id="department" required>
        <?php foreach(['CSE', 'ECE', 'ME', 'CE', 'ISE'] as $dept): ?>
            <option value="<?= $dept ?>" <?= ($_POST['department'] ?? '') === $dept ? 'selected' : '' ?>><?= $dept ?></option>
        <?php endforeach; ?>
    </select>

    <div id="year-field">
        <label for="year">Year</label>
        <select name="year" id="year">
            <?php foreach(['1', '2', '3', '4'] as $yr): ?>
                <option value="<?= $yr ?>" <?= ($_POST['year'] ?? '') === $yr ? 'selected' : '' ?>><?= $yr ?> Year</option>
            <?php endforeach; ?>
        </select>
    </div>

    <label for="email">Email</label>
    <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
    
    <label for="password">Password</label>
    <input type="password" id="password" name="password" minlength="8" required>
    
    <div class="form-actions">
        <button type="submit" class="btn">Create account</button>
    </div>
    <p style="margin-top: 10px;">Already have an account? <a href="<?= BASE_URL ?>login.php">Login here</a>.</p>
  </form>
</div>
</body>
</html>