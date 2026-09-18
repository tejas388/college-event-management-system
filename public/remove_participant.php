<?php
require_once __DIR__ . '/../app/auth.php';
require_login();
$u = current_user();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Invalid request method.";
    header("Location: dashboard.php");
    exit;
}

verify_csrf_token($_POST['csrf_token'] ?? '');

if (!in_array($u['role'], ['teacher','organizer'])) {
    http_response_code(403);
    die("Forbidden: Not allowed");
}

require_once __DIR__ . '/../app/EventModel.php';
require_once __DIR__ . '/../app/Registration.php';
require_once __DIR__ . '/../app/TeamModel.php';

$event_id = (int)($_POST['event_id'] ?? 0);
$user_id  = (int)($_POST['user_id'] ?? 0);
$team_id  = isset($_POST['team_id']) ? (int)$_POST['team_id'] : 0;

$event = EventModel::find($event_id);
if (!$event) {
    die("Event not found.");
}

// Organizers can only remove from their own events
if ($u['role'] === 'organizer' && $event['organizer_id'] != $u['id']) {
    die("Forbidden: You do not own this event.");
}

if ($event['registration_type'] === 'team' && $team_id) {
    TeamModel::removeMember($team_id, $user_id, $u['id'], $u['role']);
} else {
    Registration::remove($user_id, $event_id, $u['id'], $u['role']);
}

$_SESSION['flash'] = "Participant successfully removed.";
header("Location: participants.php?event_id=$event_id");
exit;