<?php 
require_once __DIR__ . '/../../app/auth.php';
$u = current_user(); 
if (!$u) return; 
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar">
  <div class="nav-brand">College Events</div>
  <ul class="nav-links">
    <li><a href="<?= BASE_URL ?>index.php" class="<?= $current_page == 'index.php' ? 'active' : '' ?>">Home</a></li>
    <li><a href="<?= BASE_URL ?>events.php" class="<?= $current_page == 'events.php' ? 'active' : '' ?>">Events</a></li>
    
    <?php if ($u['role'] === 'student'): ?>
        <li><a href="<?= BASE_URL ?>registered.php" class="<?= $current_page == 'registered.php' ? 'active' : '' ?>">My Registrations</a></li>
    <?php endif; ?>

    <?php if ($u['role'] === 'teacher'): ?>
        <li><a href="<?= BASE_URL ?>participants.php" class="<?= $current_page == 'participants.php' ? 'active' : '' ?>">Participants</a></li>
    <?php endif; ?>

    <?php if ($u['role'] === 'organizer'): ?>
        <li><a href="<?= BASE_URL ?>dashboard.php" class="<?= $current_page == 'dashboard.php' ? 'active' : '' ?>">Manage Events</a></li>
        <li><a href="<?= BASE_URL ?>dashboard.php?action=create" class="<?= $current_page == 'dashboard.php' && isset($_GET['action']) && $_GET['action'] == 'create' ? 'active' : '' ?>">Create Event</a></li>
        <li><a href="<?= BASE_URL ?>participants.php" class="<?= $current_page == 'participants.php' ? 'active' : '' ?>">Participants</a></li>
    <?php endif; ?>
  </ul>
  <div class="nav-user">
    <span><?= htmlspecialchars($u['name']) ?> (<?= ucfirst($u['role']) ?>)</span>
    <form method="post" action="<?= BASE_URL ?>logout.php" style="display:inline; margin:0;">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-small" style="padding: 0.3rem 0.6rem; font-size: 0.8rem;">Logout</button>
    </form>
  </div>
</nav>