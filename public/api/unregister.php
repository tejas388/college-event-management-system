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

if ($u['role'] !== 'student') {
    $_SESSION['error'] = "Only students can perform this action.";
    header("Location: ../event_view.php?id=$event_id");
    exit;
}

require_once __DIR__ . '/../../app/EventModel.php';
require_once __DIR__ . '/../../app/Registration.php';

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

Registration::remove($u['id'], $event_id);
$_SESSION['flash'] = "Successfully cancelled registration.";

header("Location: ../event_view.php?id=$event_id");
exit;