<?php
require_once __DIR__ . '/../app/auth.php';
require_login();

$u = current_user();
if ($u['role'] !== 'student') {
    die("Only students can view their registrations here.");
}

require_once __DIR__ . '/../app/Registration.php';
$myRegistrations = Registration::listByUser($u['id']);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>My Registrations - College Events</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="<?= BASE_URL ?>../assets/css/style.css">
</head>
<body>
<?php include __DIR__ . '/../views/partials/nav.php'; ?>

<div class="container">
  <h1>My Registrations</h1>
  <?php if (empty($myRegistrations)): ?>
      <p>You have not registered for any events yet.</p>
  <?php else: ?>
      <div class="events-grid">
          <?php foreach ($myRegistrations as $reg): 
              $isPast = strtotime($reg['start_time']) < time();
          ?>
              <div class="event-card <?= $isPast ? 'past-event' : '' ?>">
                  <?php if ($isPast): ?>
                      <span class="badge badge-gray" style="float: right;">Completed</span>
                  <?php else: ?>
                      <span class="badge badge-success" style="float: right;">Registered</span>
                  <?php endif; ?>
                  <h3><?= htmlspecialchars($reg['title']) ?></h3>
                  <p><strong>Date:</strong> <?= date('M d, Y H:i', strtotime($reg['start_time'])) ?></p>
                  <p><strong>Location:</strong> <?= htmlspecialchars($reg['location']) ?></p>
                  <p><strong>Registered At:</strong> <?= date('M d, Y H:i', strtotime($reg['registered_at'])) ?></p>
                  <div class="card-actions" style="margin-top: 15px;">
                      <a href="<?= BASE_URL ?>event_view.php?id=<?= $reg['event_id'] ?>" class="btn btn-small">View Event</a>
                  </div>
              </div>
          <?php endforeach; ?>
      </div>
  <?php endif; ?>
</div>
</body>
</html>