<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$isAdmin = strpos($_SERVER['PHP_SELF'], 'admin') !== false;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>SSS - Dashboard</title>

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- App styles -->
  <link href="/sports/assets/css/style.css" rel="stylesheet">

  <!-- Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" />
</head>
<body class="rp-bg rp-text">

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <h4 class="sidebar-brand">
      <i class="fas fa-basketball-ball me-2"></i>
      SSS
    </h4>
  </div>
  
  <div class="sidebar-menu">
    <div class="menu-section">
      <div class="menu-label">Main</div>
      <a href="/sports/public/dashboard.php" class="menu-item <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
        <i class="fas fa-home"></i>
        <span>Dashboard</span>
      </a>
      <a href="/sports/public/teams.php" class="menu-item <?= $currentPage === 'teams' || $currentPage === 'team_view' ? 'active' : '' ?>">
        <i class="fas fa-shield-alt"></i>
        <span>Teams</span>
      </a>
      <a href="/sports/public/players.php" class="menu-item <?= $currentPage === 'players' || $currentPage === 'player_view' ? 'active' : '' ?>">
        <i class="fas fa-users"></i>
        <span>Players</span>
      </a>
      <a href="/sports/public/tournaments.php" class="menu-item <?= $currentPage === 'tournaments' || $currentPage === 'tournament_view' ? 'active' : '' ?>">
        <i class="fas fa-trophy"></i>
        <span>Tournaments</span>
      </a>
      <a href="/sports/public/series.php" class="menu-item <?= $currentPage === 'series' || $currentPage === 'series_view' ? 'active' : '' ?>">
        <i class="fas fa-layer-group"></i>
        <span>Series</span>
      </a>
      <a href="/sports/public/games.php" class="menu-item <?= $currentPage === 'games' || $currentPage === 'game_view' ? 'active' : '' ?>">
        <i class="fas fa-gamepad"></i>
        <span>Games</span>
      </a>
      <a href="/sports/public/standings.php" class="menu-item <?= $currentPage === 'standings' ? 'active' : '' ?>">
        <i class="fas fa-list-ol"></i>
        <span>Standings</span>
      </a>
    </div>
    
    <div class="menu-section mt-4">
      <div class="menu-label">Admin</div>
      <a href="/sports/admin/index.php" class="menu-item <?= $currentPage === 'index' && $isAdmin ? 'active' : '' ?>">
        <i class="fas fa-cog"></i>
        <span>Admin Panel</span>
      </a>
    </div>
  </div>
  
  <div class="sidebar-footer">
    <button id="dm-toggle" class="btn btn-sm btn-outline-secondary w-100">
      <i class="fas fa-moon me-2"></i>
      <span>Toggle Theme</span>
    </button>
  </div>
</div>

<!-- Main Content -->
<div class="main-content">
  <!-- Top Bar -->
  <div class="topbar">
    <div class="topbar-search">
      <i class="fas fa-search"></i>
      <input id="top-search" type="text" placeholder="Quick search players, games, series..." />
    </div>
    
    <div class="topbar-actions">
      <span class="text-muted small">Cupalao - Fian - Roxas</span>
    </div>
  </div>

  <!-- Content Area -->
  <div class="content-wrapper">
    <?php
    // flash message (if any)
    if (!empty($_SESSION['flash'])):
      $f = $_SESSION['flash'];
      unset($_SESSION['flash']);
    ?>
      <div class="alert alert-<?= htmlspecialchars($f['level'] ?? 'info') ?> alert-dismissible fade show shadow-sm" role="alert">
        <?= htmlspecialchars($f['msg']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>