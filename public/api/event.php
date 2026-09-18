<?php
require_once __DIR__ . '/../../app/auth.php';
require_login();

$u = current_user();
$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

try {
    verify_csrf_token($_POST['csrf_token'] ?? '');
} catch (Exception $e) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}

$event_id = isset($_POST['event_id']) ? (int) $_POST['event_id'] : 0;

if (!$event_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing event_id']);
    exit;
}

require_once __DIR__ . '/../../app/EventModel.php';
$event = EventModel::find($event_id);
if (!$event) {
    http_response_code(404);
    echo json_encode(['error' => 'Event not found']);
    exit;
}

if ($action === 'checkin') {
    if (!in_array($u['role'], ['teacher', 'organizer'])) {
        http_response_code(403);
        echo json_encode(['error' => 'Only teachers and organizers can check in participants']);
        exit;
    }
    
    // If organizer, ensure they own it
    if ($u['role'] === 'organizer' && $event['organizer_id'] != $u['id']) {
        http_response_code(403);
        echo json_encode(['error' => 'You do not own this event']);
        exit;
    }

    $target_user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    if (!$target_user_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing target user_id']);
        exit;
    }

    require_once __DIR__ . '/../../app/AttendanceModel.php';
    if (AttendanceModel::checkin($target_user_id, $event_id)) {
        echo json_encode(['ok' => true]);
    } else {
        echo json_encode(['ok' => false, 'error' => 'Participant already checked in']);
    }
    exit;
}

http_response_code(404);
echo json_encode(['error' => 'Action not found']);
exit;