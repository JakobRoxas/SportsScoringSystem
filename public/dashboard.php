<?php
require_once __DIR__ . '/../config.php';
include __DIR__ . '/header.php';

// Get statistics
$stats = [];
try {
  $stats['teams'] = $pdo->query("SELECT COUNT(*) as cnt FROM Team")->fetch()['cnt'];
  $stats['players'] = $pdo->query("SELECT COUNT(*) as cnt FROM Player")->fetch()['cnt'];
  $stats['tournaments'] = $pdo->query("SELECT COUNT(*) as cnt FROM Tournament")->fetch()['cnt'];
  $stats['games'] = $pdo->query("SELECT COUNT(*) as cnt FROM Game")->fetch()['cnt'];
} catch (Exception $e) {
  $stats = ['teams' => 0, 'players' => 0, 'tournaments' => 0, 'games' => 0];
}

// Find champion
$champion = null;
try {
  $stmt = $pdo->prepare("
    SELECT s.SeriesID, s.WinnerTeamID, t.TeamName, t.TeamCity
    FROM Series s
    JOIN Round r ON s.RoundID = r.RoundID
    JOIN Team t ON s.WinnerTeamID = t.TeamID
    WHERE r.RoundType = 'FINALS' AND s.WinnerTeamID IS NOT NULL
    ORDER BY s.SeriesID DESC
    LIMIT 1
  ");
  $stmt->execute();
  $champion = $stmt->fetch();
} catch (Exception $e) {}

// Recent games
$recentGames = [];
try {
  $recentGames = $pdo->query("SELECT g.GameID, g.GameDate, g.Venue, g.WinnerTeamID, s.Team1ID, s.Team2ID 
    FROM Game g JOIN Series s ON g.SeriesID = s.SeriesID 
    ORDER BY g.GameDate DESC LIMIT 5")->fetchAll();
} catch (Exception $e) {}
?>

<div class="mb-4">
  <h2 class="mb-1">User Dashboard</h2>
</div>

<!-- Stats Cards -->
<div class="row g-3 mb-4">
  <div class="col-lg-3 col-sm-6">
    <div class="stat-card accent">
      <div class="stat-icon">
        <i class="fas fa-shield-alt"></i>
      </div>
      <div class="stat-value"><?= $stats['teams'] ?></div>
      <div class="stat-label">Teams</div>
    </div>
  </div>
  <div class="col-lg-3 col-sm-6">
    <div class="stat-card accent">
      <div class="stat-icon">
        <i class="fas fa-users"></i>
      </div>
      <div class="stat-value"><?= $stats['players'] ?></div>
      <div class="stat-label">Players</div>
    </div>
  </div>
  <div class="col-lg-3 col-sm-6">
    <div class="stat-card gold">
      <div class="stat-icon">
        <i class="fas fa-user"></i>
      </div>
      <div class="stat-value"><?= number_format($stats['players']) ?></div>
      <div class="stat-label">Players</div>
    </div>
  </div>
  <div class="col-lg-3 col-sm-6">
    <div class="stat-card accent">
      <div class="stat-icon">
        <i class="fas fa-trophy"></i>
      </div>
      <div class="stat-value"><?= number_format($stats['tournaments']) ?></div>
      <div class="stat-label">Tournaments</div>
    </div>
  </div>
  <div class="col-lg-3 col-sm-6">
    <div class="stat-card gold">
      <div class="stat-icon">
        <i class="fas fa-gamepad"></i>
      </div>
      <div class="stat-value"><?= number_format($stats['games']) ?></div>
      <div class="stat-label">Games</div>
    </div>
  </div>
</div>

<!-- Champion & Recent Games -->
<div class="row g-3 mb-4">
  <?php if ($champion): ?>
  <div class="col-lg-6">
    <div class="card p-4">
      <div class="d-flex align-items-center gap-3 mb-3">
        <div style="width:64px;height:64px;background:linear-gradient(135deg,var(--rp-accent),var(--rp-accent-hover));border-radius:16px;display:flex;align-items:center;justify-content:center;">
          <i class="fas fa-trophy fa-2x" style="color:white"></i>
        </div>
        <div>
          <div class="text-muted small mb-1">Current Champion</div>
          <h4 class="mb-0"><?= htmlspecialchars($champion['TeamName']) ?></h4>
          <div class="text-muted small"><?= htmlspecialchars($champion['TeamCity']) ?></div>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>
  
  <div class="col-lg-<?= $champion ? '6' : '12' ?>">
    <div class="card p-4">
      <h5 class="mb-3">Recent Games</h5>
      <?php if (count($recentGames) > 0): ?>
        <div class="d-flex flex-column gap-2">
          <?php foreach ($recentGames as $g): ?>
            <div class="d-flex justify-content-between align-items-center p-2" style="border-left:3px solid var(--rp-accent);background:var(--rp-overlay);border-radius:6px;">
              <div>
                <strong><?= htmlspecialchars($g['GameID']) ?></strong>
                <div class="small text-muted"><?= htmlspecialchars($g['Team1ID']) ?> vs <?= htmlspecialchars($g['Team2ID']) ?></div>
              </div>
              <div class="text-end">
                <div class="small"><?= htmlspecialchars($g['GameDate']) ?></div>
                <div class="small text-muted"><?= htmlspecialchars($g['Venue']) ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="text-muted mb-0">No games recorded yet.</p>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>