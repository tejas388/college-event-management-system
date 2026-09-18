<?php
require_once __DIR__ . '/../app/auth.php';
require_login();
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/EventModel.php';
require_once __DIR__ . '/../app/Registration.php';
require_once __DIR__ . '/../app/TeamModel.php';
require_once __DIR__ . '/../app/AttendanceModel.php';

$u = current_user();
if (!in_array($u['role'], ['teacher', 'organizer'])) {
    die("Forbidden: Only teachers and organizers can view participants.");
}

$event_id = (int)($_GET['event_id'] ?? 0);

if (!$event_id) {
    // Show list of events
    if ($u['role'] === 'organizer') {
        $events = EventModel::myEvents($u['id']);
    } else {
        $events = EventModel::all();
    }
    ?>
    <!doctype html>
    <html lang="en">
    <head>
      <meta charset="utf-8">
      <title>Select Event - College Events</title>
      <link rel="stylesheet" href="<?= BASE_URL ?>../assets/css/style.css">
    </head>
    <body>
    <?php include __DIR__ . '/../views/partials/nav.php'; ?>
    <div class="container">
        <h1>Select an Event to View Participants</h1>
        <?php if (empty($events)): ?>
            <p>No events found.</p>
        <?php else: ?>
            <ul style="list-style: none; padding: 0;">
                <?php foreach($events as $e): ?>
                    <li style="margin-bottom: 10px; padding: 15px; border: 1px solid var(--border); border-radius: 4px; background: #fff;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <strong><?= htmlspecialchars($e['title']) ?></strong> (<?= ucfirst($e['registration_type']) ?>) <br>
                                <small><?= date('M d, Y H:i', strtotime($e['start_time'])) ?></small>
                            </div>
                            <a href="<?= BASE_URL ?>participants.php?event_id=<?= $e['id'] ?>" class="btn">View Participants</a>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
    </body>
    </html>
    <?php
    exit;
}

// Ensure organizer owns the event
$event = EventModel::find($event_id);
if (!$event) die("Event not found.");
if ($u['role'] === 'organizer' && $event['organizer_id'] != $u['id']) {
    die("Forbidden: You do not own this event.");
}

// Fetch participants
$participants = [];
if ($event['registration_type'] === 'individual') {
    $participants = Registration::listByEvent($event_id);
} else {
    $teams = TeamModel::listByEvent($event_id);
    foreach ($teams as $t) {
        $members = TeamModel::getMembers($t['id']);
        foreach ($members as $m) {
            $m['team_name'] = $t['team_name'];
            $participants[] = $m;
        }
    }
}

// Apply filters (simple array filter in PHP)
$department = $_GET['department'] ?? '';
$year = $_GET['year'] ?? '';
$q = strtolower($_GET['q'] ?? '');

$filtered = array_filter($participants, function($p) use ($department, $year, $q) {
    if ($q) {
        $match = false;
        if (stripos($p['name'] ?? '', $q) !== false) $match = true;
        if (stripos($p['usn'] ?? '', $q) !== false) $match = true;
        if (stripos($p['email'] ?? '', $q) !== false) $match = true;
        if (!$match) return false;
    }
    if ($department && ($p['department'] ?? '') !== $department) return false;
    if ($year && ($p['year'] ?? '') !== $year) return false;
    
    return true;
});

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Participants - <?= htmlspecialchars($event['title']) ?></title>
  <link rel="stylesheet" href="<?= BASE_URL ?>../assets/css/style.css">
  <script>
    function checkin(userId, eventId, btn) {
        btn.disabled = true;
        fetch('<?= BASE_URL ?>api/event.php?action=checkin', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'user_id=' + userId + '&event_id=' + eventId + '&csrf_token=<?= generate_csrf_token() ?>'
        })
        .then(response => response.json())
        .then(data => {
            if (data.ok) {
                btn.outerHTML = '<span class="badge badge-success">Checked In</span>';
            } else {
                alert(data.error || 'Check-in failed');
                btn.disabled = false;
            }
        });
    }
  </script>
</head>
<body>
<?php include __DIR__ . '/../views/partials/nav.php'; ?>

<div class="container">
  <h1>Participants for <?= htmlspecialchars($event['title']) ?></h1>
  
  <?php if (isset($_SESSION['flash'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['flash']); unset($_SESSION['flash']); ?></div>
  <?php endif; ?>
  <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
  <?php endif; ?>

  <form method="get" class="filter-form" style="display: flex; gap: 10px; margin-bottom: 20px; align-items: flex-end; flex-wrap: wrap;">
    <input type="hidden" name="event_id" value="<?= $event_id ?>">
    
    <div>
        <label>Search (Name, USN, Email):</label>
        <input type="text" name="q" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" placeholder="Search..." style="margin-bottom:0;">
    </div>
    
    <div>
        <label>Department:</label>
        <select name="department" style="margin-bottom:0;">
          <option value="">All</option>
          <option value="CSE" <?= $department==='CSE'?'selected':'' ?>>CSE</option>
          <option value="ECE" <?= $department==='ECE'?'selected':'' ?>>ECE</option>
          <option value="ME" <?= $department==='ME'?'selected':'' ?>>ME</option>
          <option value="CE" <?= $department==='CE'?'selected':'' ?>>CE</option>
          <option value="ISE" <?= $department==='ISE'?'selected':'' ?>>ISE</option>
        </select>
    </div>

    <div>
        <label>Year:</label>
        <select name="year" style="margin-bottom:0;">
          <option value="">All</option>
          <option value="1" <?= $year==='1'?'selected':'' ?>>1st Year</option>
          <option value="2" <?= $year==='2'?'selected':'' ?>>2nd Year</option>
          <option value="3" <?= $year==='3'?'selected':'' ?>>3rd Year</option>
          <option value="4" <?= $year==='4'?'selected':'' ?>>4th Year</option>
        </select>
    </div>

    <button type="submit" class="btn">Apply Filters</button>
    <a href="<?= BASE_URL ?>participants.php?event_id=<?= $event_id ?>" class="btn secondary">Clear</a>
  </form>

  <?php if (!empty($filtered)): ?>
    <div style="overflow-x: auto; background: #fff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); padding: 20px;">
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
          <thead>
            <tr style="border-bottom: 2px solid #eee;">
              <th style="padding: 12px; font-weight: bold; color: #333;">Name</th>
              <th style="padding: 12px; font-weight: bold; color: #333;">USN</th>
              <th style="padding: 12px; font-weight: bold; color: #333;">Email</th>
              <th style="padding: 12px; font-weight: bold; color: #333;">Dept / Year</th>
              <?php if ($event['registration_type'] === 'team'): ?><th style="padding: 12px; font-weight: bold; color: #333;">Team</th><?php endif; ?>
              <th style="padding: 12px; font-weight: bold; color: #333;">Status</th>
              <th style="padding: 12px; font-weight: bold; color: #333;">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($filtered as $p): 
                $hasCheckedIn = $p['user_id'] ? AttendanceModel::hasCheckedIn($p['user_id'], $event_id) : false;
            ?>
              <tr style="border-bottom: 1px solid #eee;">
                <td style="padding: 12px;"><?= htmlspecialchars($p['name'] ?? '') ?></td>
                <td style="padding: 12px;"><?= htmlspecialchars($p['usn'] ?? '') ?></td>
                <td style="padding: 12px;"><?= htmlspecialchars($p['email'] ?? 'N/A') ?></td>
                <td style="padding: 12px;">
                    <?php if (isset($p['department']) && isset($p['year'])): ?>
                        <?= htmlspecialchars($p['department']) ?> / <?= htmlspecialchars($p['year']) ?>
                    <?php else: ?>
                        N/A
                    <?php endif; ?>
                </td>
                <?php if ($event['registration_type'] === 'team'): ?>
                    <td style="padding: 12px;"><?= htmlspecialchars($p['team_name'] ?? '') ?></td>
                <?php endif; ?>
                <td id="status-<?= htmlspecialchars($p['usn'] ?? '') ?>" style="padding: 12px;">
                    <?php if ($p['user_id']): ?>
                        <?= $hasCheckedIn ? '<span class="badge badge-success">Checked In</span>' : '<span class="badge badge-gray">Not Checked In</span>' ?>
                    <?php else: ?>
                        <span class="badge badge-gray">External Member</span>
                    <?php endif; ?>
                </td>
                <td style="white-space: nowrap; padding: 12px;">
                    <?php if ($p['user_id']): ?>
                        <?php if (!$hasCheckedIn): ?>
                            <button class="btn btn-small" onclick="checkin(<?= $p['user_id'] ?>, <?= $event_id ?>, this)">Check In</button>
                        <?php endif; ?>
                        
                        <form method="post" action="<?= BASE_URL ?>remove_participant.php" style="display:inline; margin:0;" onsubmit="return confirm('Are you sure you want to remove this participant?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="event_id" value="<?= $event_id ?>">
                            <input type="hidden" name="user_id" value="<?= $p['user_id'] ?>">
                            <?php if ($event['registration_type'] === 'team'): ?>
                                <input type="hidden" name="team_id" value="<?= $p['team_id'] ?>">
                            <?php endif; ?>
                            <button type="submit" class="btn btn-small red">Remove</button>
                        </form>
                    <?php else: ?>
                        <span class="badge badge-gray" style="font-size: 0.8em;">No actions available</span>
                    <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
    </div>
  <?php else: ?>
    <p>No participants found.</p>
  <?php endif; ?>
</div>
</body>
</html>