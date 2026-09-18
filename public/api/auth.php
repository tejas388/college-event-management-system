<?php
require_once __DIR__ . '/../../app/auth.php';
require_login();
authorize(['organizer']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token'] ?? '');
    
    require_once __DIR__ . '/../../app/EventModel.php';
    
    $id = EventModel::create([
        'title'       => $_POST['title'],
        'description' => $_POST['description'],
        'location'    => $_POST['location'],
        'start_time'  => $_POST['start_time'],
        'end_time'    => $_POST['end_time'],
        'registration_type' => $_POST['registration_type'] ?? 'individual',
        'organizer_id'=> current_user()['id']
    ]);
    header('Location: ../event_view.php?id=' . $id);
    exit;
}