<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/EventModel.php';
require_once __DIR__ . '/../app/Registration.php';
require_once __DIR__ . '/../app/TeamModel.php';

$u = current_user();
$event_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$event = EventModel::find($event_id);

if (!$event) {
    die("Event not found.");
}

$isPast = strtotime($event['end_time']) < time();
$isStarted = strtotime($event['start_time']) < time();
$statusBadge = $isPast ? '<span class="badge badge-gray">Completed</span>' : 
               ($isStarted ? '<span class="badge badge-primary">Ongoing</span>' : '<span class="badge badge-success">Upcoming</span>');

$isRegistered = false;
$userTeam = null;

if ($u && $u['role'] === 'student') {
    if ($event['registration_type'] === 'individual') {
        $isRegistered = Registration::isRegistered($u['id'], $event_id);
    } else {
        $userTeam = TeamModel::getTeamForUserInEvent($u['id'], $event_id);
        $isRegistered = $userTeam !== false;
    }
}

$registeredCount = Registration::countByEvent($event_id);
if ($event['registration_type'] === 'team') {
    $teamCount = TeamModel::countTeamsByEvent($event_id);
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($event['title']) ?> - College Events</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="<?= BASE_URL ?>../assets/css/style.css">
</head>
<body>
<?php include __DIR__ . '/../views/partials/nav.php'; ?>
<div class="container">
    
    <?php if (isset($_SESSION['flash'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['flash']) ?></div>
        <script>alert(<?= json_encode($_SESSION['flash']) ?>);</script>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error"><?= htmlspecialchars($_SESSION['error']) ?></div>
        <script>alert(<?= json_encode("Registration error: " . $_SESSION['error']) ?>);</script>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div style="background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
            <div>
                <h1 style="margin-bottom: 10px;"><?= htmlspecialchars($event['title']) ?> <?= $statusBadge ?></h1>
                <p style="color: var(--gray);">Organized by: <?= htmlspecialchars($event['organizer_name']) ?></p>
            </div>
            <?php if ($u && $u['role'] === 'organizer' && $u['id'] == $event['organizer_id']): ?>
                <div class="form-actions">
                    <a href="<?= BASE_URL ?>dashboard.php?edit=<?= $event['id'] ?>" class="btn secondary">Edit Event</a>
                    <form method="post" action="<?= BASE_URL ?>dashboard.php?edit=<?= $event['id'] ?>" style="display:inline; margin:0;" onsubmit="return confirm('Are you sure you want to delete this event?');">
                        <?= csrf_field() ?>
                        <button type="submit" name="delete" value="1" class="btn red">Delete Event</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 40px;">
            <div>
                <h3>Description</h3>
                <p style="white-space: pre-wrap;"><?= htmlspecialchars($event['description']) ?></p>
            </div>
            
            <div style="background: var(--bg); padding: 20px; border-radius: 8px; border: 1px solid var(--border);">
                <h3>Event Details</h3>
                <ul style="list-style: none; padding: 0;">
                    <li style="margin-bottom: 10px;"><strong>Location:</strong> <?= htmlspecialchars($event['location']) ?></li>
                    <li style="margin-bottom: 10px;"><strong>Start:</strong> <?= date('F j, Y - H:i', strtotime($event['start_time'])) ?></li>
                    <li style="margin-bottom: 10px;"><strong>End:</strong> <?= date('F j, Y - H:i', strtotime($event['end_time'])) ?></li>
                    <li style="margin-bottom: 10px;"><strong>Type:</strong> <?= ucfirst($event['registration_type']) ?></li>
                    <?php if ($event['registration_type'] === 'team'): ?>
                        <li style="margin-bottom: 10px;"><strong>Team Size:</strong> <?= $event['team_min_size'] ?> - <?= $event['team_max_size'] ?> members</li>
                        <li style="margin-bottom: 10px;"><strong>Teams Registered:</strong> <?= $teamCount ?></li>
                    <?php else: ?>
                        <li style="margin-bottom: 10px;"><strong>Registered:</strong> <?= $registeredCount ?> individuals</li>
                    <?php endif; ?>
                </ul>

                <?php if ($u && $u['role'] === 'student'): ?>
                    <div style="margin-top: 20px; border-top: 1px solid var(--border); padding-top: 20px;">
                        <?php if ($isPast): ?>
                            <div class="alert alert-error" style="margin: 0;">Registration closed.</div>
                        <?php elseif ($isRegistered): ?>
                            <div class="alert alert-success" style="margin-bottom: 15px;">You are registered!</div>
                            <?php if ($event['registration_type'] === 'team'): ?>
                                <p style="margin-bottom: 10px;"><strong>Team:</strong> <?= htmlspecialchars($userTeam['team_name']) ?></p>
                                <form method="post" action="<?= BASE_URL ?>api/team_unregister.php">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                                    <input type="hidden" name="team_id" value="<?= $userTeam['id'] ?>">
                                    <button type="submit" class="btn red">Leave Team / Unregister</button>
                                </form>
                            <?php else: ?>
                                <form method="post" action="<?= BASE_URL ?>api/unregister.php">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                                    <button type="submit" class="btn red">Cancel Registration</button>
                                </form>
                            <?php endif; ?>
                        <?php else: ?>
                            <?php if ($event['registration_type'] === 'team'): ?>
                                <h4>Register a Team</h4>
                                <form method="post" action="<?= BASE_URL ?>api/team_register.php" style="margin-top: 10px;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                                    <label>Team Name:</label>
                                    <input type="text" name="team_name" required>
                                    
                                    <div style="margin-bottom: 15px;">
                                        <strong>Team Leader:</strong> <?= htmlspecialchars($u['name']) ?> (<?= htmlspecialchars($u['user_id']) ?>)
                                    </div>
                                    
                                    <div id="team_members_container">
                                        <label>Members (Maximum allowed: <?= $event['team_max_size'] ?>)</label>
                                        <?php for($i=2; $i <= $event['team_max_size']; $i++): ?>
                                            <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                                                <input type="text" name="member_names[]" placeholder="Member <?= $i ?> Name" <?= $i <= $event['team_min_size'] ? 'required' : '' ?> style="flex: 1; margin: 0;">
                                                <input type="text" name="member_usns[]" placeholder="Member <?= $i ?> USN" <?= $i <= $event['team_min_size'] ? 'required' : '' ?> style="flex: 1; margin: 0;">
                                            </div>
                                        <?php endfor; ?>
                                    </div>
                                    <button type="submit" class="btn">Register Team</button>
                                </form>
                            <?php else: ?>
                                <form method="post" action="<?= BASE_URL ?>api/register.php">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                                    <button type="submit" class="btn">Register for Event</button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>