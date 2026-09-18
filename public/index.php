<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/EventModel.php';
$u = current_user();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Home - College Events</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="<?= BASE_URL ?>../assets/css/style.css">
</head>
<body>
<?php include __DIR__ . '/../views/partials/nav.php'; ?>
<div class="container" style="text-align: center; padding-top: 50px;">
  <?php if (isset($_GET['loggedin']) && $_GET['loggedin'] == 1): ?>
      <script>alert("Login successful!");</script>
  <?php endif; ?>
  <h1>College Event Registration Portal</h1>
  <p style="font-size: 1.2rem; margin: 20px 0;">Browse and register for upcoming events easily.</p>
  <?php if ($u): ?>
      <a class="btn" href="<?= BASE_URL ?>events.php" style="font-size: 1.2rem; padding: 10px 20px;">Explore Events</a>
  <?php else: ?>
      <a class="btn" href="<?= BASE_URL ?>login.php" style="font-size: 1.2rem; padding: 10px 20px;">Login to Start</a>
  <?php endif; ?>
</div>
</body>
</html>