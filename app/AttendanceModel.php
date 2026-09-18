<?php
require_once __DIR__.'/db.php';

class AttendanceModel {
    public static function checkin($user_id, $event_id) {
        try {
            $stmt = db()->prepare('INSERT INTO attendance (user_id, event_id, checkin_time) VALUES (?, ?, NOW())');
            $stmt->execute([$user_id, $event_id]);
            return true;
        } catch (PDOException $e) {
            // Check if it's a duplicate entry error
            if ($e->getCode() == 23000) {
                return false;
            }
            throw $e;
        }
    }

    public static function byEvent($event_id) {
        $stmt = db()->prepare('
            SELECT a.*, u.name, u.user_id as usn, u.department, u.year 
            FROM attendance a 
            JOIN users u ON u.id = a.user_id 
            WHERE a.event_id = ? 
            ORDER BY a.checkin_time ASC
        ');
        $stmt->execute([$event_id]);
        return $stmt->fetchAll();
    }
    
    public static function hasCheckedIn($user_id, $event_id) {
        $stmt = db()->prepare('SELECT id FROM attendance WHERE user_id = ? AND event_id = ?');
        $stmt->execute([$user_id, $event_id]);
        return (bool) $stmt->fetch();
    }
}
