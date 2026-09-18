<?php
require_once __DIR__ . '/db.php';

class Registration {
    public static function add($user_id, $event_id) {
        // Prevent registering if soft-deleted or already registered
        if (self::isRegistered($user_id, $event_id)) {
            return false;
        }
        $stmt = db()->prepare("INSERT INTO registrations (user_id, event_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $event_id]);
        return true;
    }

    public static function remove($user_id, $event_id, $removed_by = null, $removed_role = null) {
        if ($removed_by) {
            $stmt = db()->prepare("UPDATE registrations SET removed_at = NOW(), removed_by = ?, removed_role = ? WHERE user_id = ? AND event_id = ? AND removed_at IS NULL");
            $stmt->execute([$removed_by, $removed_role, $user_id, $event_id]);
        } else {
            $stmt = db()->prepare("DELETE FROM registrations WHERE user_id = ? AND event_id = ? AND removed_at IS NULL");
            $stmt->execute([$user_id, $event_id]);
        }
    }

    public static function listByEvent($event_id) {
        $stmt = db()->prepare(
            "SELECT r.*, u.name, u.user_id as usn, u.department, u.year, u.email
             FROM registrations r 
             JOIN users u ON u.id = r.user_id 
             WHERE r.event_id = ? AND r.removed_at IS NULL
             ORDER BY r.registered_at DESC"
        );
        $stmt->execute([$event_id]);
        return $stmt->fetchAll();
    }

    public static function listByUser($user_id) {
        $stmt = db()->prepare(
            "SELECT r.event_id, r.registered_at, e.title, e.start_time, e.end_time, e.location, 'individual' as type, NULL as team_name
             FROM registrations r 
             JOIN events e ON e.id = r.event_id 
             WHERE r.user_id = ? AND r.removed_at IS NULL
             
             UNION ALL
             
             SELECT t.event_id, tm.joined_at as registered_at, e.title, e.start_time, e.end_time, e.location, 'team' as type, t.team_name
             FROM team_members tm
             JOIN teams t ON t.id = tm.team_id
             JOIN events e ON e.id = t.event_id
             WHERE tm.user_id = ? AND tm.removed_at IS NULL AND t.status = 'active'
             
             ORDER BY start_time ASC"
        );
        $stmt->execute([$user_id, $user_id]);
        return $stmt->fetchAll();
    }

    public static function isRegistered($user_id, $event_id) {
        $stmt = db()->prepare("SELECT 1 FROM registrations WHERE user_id = ? AND event_id = ? AND removed_at IS NULL LIMIT 1");
        $stmt->execute([$user_id, $event_id]);
        return (bool) $stmt->fetchColumn();
    }

    public static function countByEvent($event_id) {
        $stmt = db()->prepare("SELECT COUNT(*) FROM registrations WHERE event_id = ? AND removed_at IS NULL");
        $stmt->execute([$event_id]);
        return (int) $stmt->fetchColumn();
    }
}