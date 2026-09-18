<?php
require_once __DIR__ . '/app/config.php';
require_once __DIR__ . '/app/db.php';

try {
    $pdo = db();
    
    $stmt = $pdo->query("DESCRIBE team_members");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('member_name', $columns)) {
        $pdo->exec("ALTER TABLE team_members MODIFY user_id INT NULL");
        $pdo->exec("ALTER TABLE team_members ADD COLUMN member_name VARCHAR(100) NULL");
        $pdo->exec("ALTER TABLE team_members ADD COLUMN member_usn VARCHAR(30) NULL");
        echo "Columns added to team_members.\n";
    }
    
    // Check if uniq_team_member index exists
    $stmt = $pdo->query("SHOW INDEX FROM team_members WHERE Key_name = 'uniq_team_member'");
    if ($stmt->fetch()) {
        $pdo->exec("ALTER TABLE team_members DROP INDEX uniq_team_member");
        echo "Dropped uniq_team_member index.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
