<?php
require_once __DIR__ . '/../app/auth.php';
require_login();
authorize(['organizer']);

require_once __DIR__ . '/../app/EventModel.php';

$action = $_GET['action'] ?? 'list';
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$event = $editId ? EventModel::find($editId) : null;

$u = current_user();

if ($editId && $event && $event['organizer_id'] != $u['id']) {
    die("Forbidden: You do not own this event.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token'] ?? '');
    
    if (isset($_POST['delete']) && $editId) {
        EventModel::delete($editId, $u['id']);
        header('Location: dashboard.php');
        exit;
    } elseif ($editId) {
        EventModel::update($editId, $_POST, $u['id']);
        header('Location: event_view.php?id=' . $editId);
        exit;
    } else {
        // Create
        if (strtotime($_POST['end_time']) <= strtotime($_POST['start_time'])) {
            $error = "End time must be after start time.";
        } else {
            $id = EventModel::create([
                'title' => $_POST['title'],
                'description' => $_POST['description'],
                'location' => $_POST['location'],
                'start_time' => $_POST['start_time'],
                'end_time' => $_POST['end_time'],
                'organizer_id' => $u['id'],
                'registration_type' => $_POST['registration_type'],
                'team_min_size' => $_POST['team_min_size'] ?? 1,
                'team_max_size' => $_POST['team_max_size'] ?? 1
            ]);
            header('Location: event_view.php?id=' . $id);
            exit;
        }
    }
}

$myEvents = EventModel::myEvents($u['id']);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Dashboard - College Events</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="<?= BASE_URL ?>../assets/css/style.css">
  <script>
    function toggleTeamSize(val) {
      document.getElementById('teamSizeFields').style.display = (val === 'team') ? 'block' : 'none';
    }
  </script>
</head>
<body>
<?php include __DIR__ . '/../views/partials/nav.php'; ?>
<div class="container">
  
  <?php if (isset($error)): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <?php if ($action === 'create' || $editId): ?>
      <h1><?= $editId ? 'Edit Event' : 'Create Event' ?></h1>
      <form method="post" action="<?= BASE_URL ?>dashboard.php<?= $editId ? '?edit='.$editId : '' ?>">
        <?= csrf_field() ?>
        
        <label>Title</label>
        <input name="title" value="<?= htmlspecialchars($event['title'] ?? '') ?>" required>
    
        <label>Description</label>
        <textarea name="description" required><?= htmlspecialchars($event['description'] ?? '') ?></textarea>
    
        <label>Location</label>
        <input name="location" value="<?= htmlspecialchars($event['location'] ?? '') ?>" required>
    
        <label>Start Time</label>
        <input type="datetime-local" name="start_time"
               value="<?= $event ? date('Y-m-d\TH:i', strtotime($event['start_time'])) : '' ?>" required>
    
        <label>End Time</label>
        <input type="datetime-local" name="end_time"
               value="<?= $event ? date('Y-m-d\TH:i', strtotime($event['end_time'])) : '' ?>" required>
    
        <label>Registration Type</label>
        <select name="registration_type" id="regType" required onchange="toggleTeamSize(this.value)">
          <option value="individual" <?= ($event['registration_type'] ?? '') === 'individual' ? 'selected' : '' ?>>Individual</option>
          <option value="team" <?= ($event['registration_type'] ?? '') === 'team' ? 'selected' : '' ?>>Team</option>
        </select>
    
        <div id="teamSizeFields" style="display: <?= ($event['registration_type'] ?? '') === 'team' ? 'block' : 'none' ?>;">
          <label>Minimum Team Size</label>
          <input type="number" name="team_min_size" min="1"
                 value="<?= htmlspecialchars($event['team_min_size'] ?? 1) ?>" required>
    
          <label>Maximum Team Size</label>
          <input type="number" name="team_max_size" min="1"
                 value="<?= htmlspecialchars($event['team_max_size'] ?? 1) ?>" required>
        </div>
    
        <div class="form-actions">
          <button type="submit" class="btn"><?= $editId ? 'Save Changes' : 'Create Event' ?></button>
          <?php if ($editId): ?>
            <button type="submit" name="delete" value="1" class="btn red"
                    onclick="return confirm('Deleting this event will also remove all registrations/teams. Continue?');">
              Delete Event
            </button>
          <?php endif; ?>
          <a href="<?= BASE_URL ?>dashboard.php" class="btn secondary">Cancel</a>
        </div>
      </form>
  <?php else: ?>
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
          <h1>My Events</h1>
          <a href="<?= BASE_URL ?>dashboard.php?action=create" class="btn">Create New Event</a>
      </div>
      
      <?php if (empty($myEvents)): ?>
          <p>You haven't created any events yet.</p>
      <?php else: ?>
          <div class="events-grid">
              <?php foreach ($myEvents as $evt): ?>
                  <div class="event-card">
                      <h3><?= htmlspecialchars($evt['title']) ?></h3>
                      <p><strong>Date:</strong> <?= date('M d, Y H:i', strtotime($evt['start_time'])) ?></p>
                      <p><strong>Location:</strong> <?= htmlspecialchars($evt['location']) ?></p>
                      <div class="card-actions">
                          <a href="<?= BASE_URL ?>event_view.php?id=<?= $evt['id'] ?>" class="btn btn-small">View</a>
                          <a href="<?= BASE_URL ?>dashboard.php?edit=<?= $evt['id'] ?>" class="btn btn-small secondary">Edit</a>
                      </div>
                  </div>
              <?php endforeach; ?>
          </div>
      <?php endif; ?>
  <?php endif; ?>
</div>
</body>
</html>