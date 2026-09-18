<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/EventModel.php';

$q = $_GET['q'] ?? '';
$events = EventModel::all($q);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Events - College Events</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="<?= BASE_URL ?>../assets/css/style.css">
</head>
<body>
<?php include __DIR__ . '/../views/partials/nav.php'; ?>
<div class="container">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h1>All Events</h1>
    <form method="get" action="<?= BASE_URL ?>events.php" style="margin: 0; display: flex; gap: 10px;">
      <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search events..." style="margin: 0; padding: 0.5rem;">
      <button type="submit" class="btn">Search</button>
    </form>
  </div>

  <?php if (empty($events)): ?>
    <p>No events found.</p>
  <?php else: ?>
    <div class="events-grid">
      <?php foreach ($events as $evt): 
          $isPast = strtotime($evt['start_time']) < time();
      ?>
        <div class="event-card <?= $isPast ? 'past-event' : '' ?>">
          <?php if ($isPast): ?>
              <span class="badge badge-gray" style="float: right;">Past Event</span>
          <?php endif; ?>
          <h3><?= htmlspecialchars($evt['title']) ?></h3>
          <p><strong>Organizer:</strong> <?= htmlspecialchars($evt['organizer_name']) ?></p>
          <p><strong>Date:</strong> <?= date('M d, Y H:i', strtotime($evt['start_time'])) ?></p>
          <p><strong>Location:</strong> <?= htmlspecialchars($evt['location']) ?></p>
          <p><strong>Type:</strong> <?= ucfirst($evt['registration_type']) ?></p>
          <div class="card-actions" style="margin-top: 15px;">
            <a href="<?= BASE_URL ?>event_view.php?id=<?= $evt['id'] ?>" class="btn">View Details</a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
</body>
</html>