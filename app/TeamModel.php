<?php
require_once __DIR__ . '/db.php';

class TeamModel {
    public static function create($event_id, $team_name, $leader_user_id) {
        $stmt = db()->prepare("INSERT INTO teams (event_id, team_name, leader_user_id) VALUES (?, ?, ?)");
        $stmt->execute([$event_id, $team_name, $leader_user_id]);
        $team_id = db()->lastInsertId();
        
        // Add leader as first member
        self::addMember($team_id, $leader_user_id);
        
        return $team_id;
    }

    public static function addMember($team_id, $user_id, $member_name = null, $member_usn = null) {
        if ($user_id && self::isMember($team_id, $user_id)) {
            return false;
        }
        $stmt = db()->prepare("INSERT INTO team_members (team_id, user_id, member_name, member_usn) VALUES (?, ?, ?, ?)");
        $stmt->execute([$team_id, $user_id, $member_name, $member_usn]);
        return true;
    }

    public static function removeMember($team_id, $user_id, $removed_by = null, $removed_role = null) {
        if ($removed_by) {
            $stmt = db()->prepare("UPDATE team_members SET removed_at = NOW(), removed_by = ?, removed_role = ? WHERE team_id = ? AND user_id = ? AND removed_at IS NULL");
            $stmt->execute([$removed_by, $removed_role, $team_id, $user_id]);
        } else {
            $stmt = db()->prepare("DELETE FROM team_members WHERE team_id = ? AND user_id = ? AND removed_at IS NULL");
            $stmt->execute([$team_id, $user_id]);
        }
    }

    public static function isMember($team_id, $user_id) {
        $stmt = db()->prepare("SELECT 1 FROM team_members WHERE team_id = ? AND user_id = ? AND removed_at IS NULL LIMIT 1");
        $stmt->execute([$team_id, $user_id]);
        return (bool) $stmt->fetchColumn();
    }
    
    public static function isRegisteredForEvent($user_id, $event_id) {
        $stmt = db()->prepare("
            SELECT 1 FROM team_members tm
            JOIN teams t ON t.id = tm.team_id
            WHERE t.event_id = ? AND tm.user_id = ? AND tm.removed_at IS NULL AND t.status = 'active'
            LIMIT 1
        ");
        $stmt->execute([$event_id, $user_id]);
        return (bool) $stmt->fetchColumn();
    }

    public static function getTeamForUserInEvent($user_id, $event_id) {
        $stmt = db()->prepare("
            SELECT t.* FROM teams t
            JOIN team_members tm ON tm.team_id = t.id
            WHERE t.event_id = ? AND tm.user_id = ? AND tm.removed_at IS NULL AND t.status = 'active'
            LIMIT 1
        ");
        $stmt->execute([$event_id, $user_id]);
        return $stmt->fetch();
    }

    public static function getMembers($team_id) {
        $stmt = db()->prepare("
            SELECT tm.*, 
                   COALESCE(u.name, tm.member_name) as name, 
                   COALESCE(u.user_id, tm.member_usn) as usn, 
                   u.department, u.year 
            FROM team_members tm 
            LEFT JOIN users u ON u.id = tm.user_id 
            WHERE tm.team_id = ? AND tm.removed_at IS NULL
        ");
        $stmt->execute([$team_id]);
        return $stmt->fetchAll();
    }

    public static function findById($team_id) {
        $stmt = db()->prepare("SELECT * FROM teams WHERE id = ? AND status = 'active'");
        $stmt->execute([$team_id]);
        return $stmt->fetch();
    }
    
    public static function listByEvent($event_id) {
        $stmt = db()->prepare("SELECT t.*, u.name as leader_name FROM teams t JOIN users u ON u.id = t.leader_user_id WHERE t.event_id = ? AND t.status = 'active'");
        $stmt->execute([$event_id]);
        return $stmt->fetchAll();
    }
    
    public static function countTeamsByEvent($event_id) {
        $stmt = db()->prepare("SELECT COUNT(*) FROM teams WHERE event_id = ? AND status = 'active'");
        $stmt->execute([$event_id]);
        return (int) $stmt->fetchColumn();
    }
}
