<?php
require_once __DIR__ . '/../../app/auth.php';
require_login();
$u = current_user();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Invalid request method.";
    header("Location: ../events.php");
    exit;
}

verify_csrf_token($_POST['csrf_token'] ?? '');

$event_id = (int)($_POST['event_id'] ?? 0);
$team_id = (int)($_POST['team_id'] ?? 0);

require_once __DIR__ . '/../../app/EventModel.php';
require_once __DIR__ . '/../../app/TeamModel.php';

$event = EventModel::find($event_id);
if (!$event) {
    $_SESSION['error'] = "Event not found.";
    header("Location: ../events.php");
    exit;
}

if (strtotime($event['start_time']) < time()) {
    $_SESSION['error'] = "Cannot unregister from a past or ongoing event.";
    header("Location: ../event_view.php?id=$event_id");
    exit;
}

$team = TeamModel::findById($team_id);
if (!$team || $team['event_id'] != $event_id) {
    $_SESSION['error'] = "Team not found.";
    header("Location: ../event_view.php?id=$event_id");
    exit;
}

if (!TeamModel::isMember($team_id, $u['id'])) {
    $_SESSION['error'] = "You are not a member of this team.";
    header("Location: ../event_view.php?id=$event_id");
    exit;
}

if ($team['leader_user_id'] == $u['id']) {
    // Leader unregistering => cancel whole team
    $stmt = db()->prepare("UPDATE teams SET status = 'removed', updated_at = NOW() WHERE id = ?");
    $stmt->execute([$team_id]);
    $_SESSION['flash'] = "Team successfully cancelled.";
} else {
    // Normal member unregistering => remove just them
    TeamModel::removeMember($team_id, $u['id']);
    $_SESSION['flash'] = "You have left the team.";
}

header("Location: ../event_view.php?id=$event_id");
exit;