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
$team_name = trim($_POST['team_name'] ?? '');
$member_names = $_POST['member_names'] ?? [];
$member_usns = $_POST['member_usns'] ?? [];

if ($u['role'] !== 'student') {
    $_SESSION['error'] = "Only students can register teams.";
    header("Location: ../event_view.php?id=$event_id");
    exit;
}

require_once __DIR__ . '/../../app/EventModel.php';
require_once __DIR__ . '/../../app/TeamModel.php';

$event = EventModel::find($event_id);
if (!$event) {
    $_SESSION['error'] = "Event not found.";
    header("Location: ../events.php");
    exit;
}

if (strtotime($event['start_time']) < time()) {
    $_SESSION['error'] = "Registration is closed for this event.";
    header("Location: ../event_view.php?id=$event_id");
    exit;
}

if ($event['registration_type'] !== 'team') {
    $_SESSION['error'] = "This is an individual event.";
    header("Location: ../event_view.php?id=$event_id");
    exit;
}

// Check if user is already in a team for this event
if (TeamModel::isRegisteredForEvent($u['id'], $event_id)) {
    $_SESSION['error'] = "You are already in a team for this event.";
    header("Location: ../event_view.php?id=$event_id");
    exit;
}

// Validate members
$valid_members = [];
$unique_usns = [];

for ($i = 0; $i < count($member_usns); $i++) {
    $name = trim($member_names[$i] ?? '');
    $usn = trim($member_usns[$i] ?? '');
    
    if (empty($name) && empty($usn)) continue;
    
    if (empty($name) || empty($usn)) {
        $_SESSION['error'] = "Both Name and USN must be provided for every member.";
        header("Location: ../event_view.php?id=$event_id");
        exit;
    }
    
    if ($usn === $u['user_id']) continue; // skip self
    
    if (in_array($usn, $unique_usns)) {
        $_SESSION['error'] = "Duplicate member USN found in the form.";
        header("Location: ../event_view.php?id=$event_id");
        exit;
    }
    
    $unique_usns[] = $usn;
    
    // Check if user exists to prevent double registration if they have an account
    $member_user = find_user_by_user_id($usn);
    if ($member_user) {
        if (TeamModel::isRegisteredForEvent($member_user['id'], $event_id)) {
            $_SESSION['error'] = "User $usn is already in a team for this event.";
            header("Location: ../event_view.php?id=$event_id");
            exit;
        }
        $valid_members[] = ['user_id' => $member_user['id'], 'name' => null, 'usn' => null];
    } else {
        $valid_members[] = ['user_id' => null, 'name' => $name, 'usn' => $usn];
    }
}

$total_members = 1 + count($valid_members);

if ($total_members < $event['team_min_size']) {
    $_SESSION['error'] = "Team must have at least " . $event['team_min_size'] . " members.";
    header("Location: ../event_view.php?id=$event_id");
    exit;
}

if ($total_members > $event['team_max_size']) {
    $_SESSION['error'] = "Team cannot have more than " . $event['team_max_size'] . " members.";
    header("Location: ../event_view.php?id=$event_id");
    exit;
}

try {
    db()->beginTransaction();
    
    $team_id = TeamModel::create($event_id, $team_name, $u['id']);
    
    foreach ($valid_members as $vm) {
        TeamModel::addMember($team_id, $vm['user_id'], $vm['name'], $vm['usn']);
    }
    
    db()->commit();
    $_SESSION['flash'] = "Team successfully registered!";
} catch (Exception $e) {
    db()->rollBack();
    $_SESSION['error'] = "An error occurred: " . $e->getMessage();
}

header("Location: ../event_view.php?id=$event_id");
exit;