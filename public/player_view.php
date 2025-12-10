<?php
require_once __DIR__ . '/../config.php';
include __DIR__ . '/header.php';

$playerID = $_GET['id'] ?? '';

if (!$playerID) {
  echo '<div class="alert alert-danger m-4">No player ID provided.</div>';
  include __DIR__ . '/footer.php';
  exit;
}

// Fetch player details
$stmt = $pdo->prepare("
  SELECT p.*, t.TeamName, t.TeamCity, t.TeamConf 
  FROM Player p 
  JOIN Team t ON p.TeamID = t.TeamID 
  WHERE p.PlayerID = ?
");
$stmt->execute([$playerID]);
$player = $stmt->fetch();

if (!$player) {
  echo '<div class="alert alert-danger m-4">Player not found.</div>';
  include __DIR__ . '/footer.php';
  exit;
}

// Fetch player stats
try {
  $statsStmt = $pdo->prepare("
    SELECT ps.*, g.GameDate, g.Venue, s.SeriesID,
           t1.TeamName as Team1Name, t2.TeamName as Team2Name
    FROM PlayerStat ps
    JOIN Game g ON ps.GameID = g.GameID
    JOIN Series s ON g.SeriesID = s.SeriesID
    JOIN Team t1 ON s.Team1ID = t1.TeamID
    JOIN Team t2 ON s.Team2ID = t2.TeamID
    WHERE ps.PlayerID = ?
    ORDER BY g.GameDate DESC
  ");
  $statsStmt->execute([$playerID]);
  $stats = $statsStmt->fetchAll();
} catch (Exception $e) {
  // If there's an error, try a simpler query
  error_log("Player stats query error: " . $e->getMessage());
  $stats = [];
}

// Calculate totals
$totalPoints = 0;
$totalRebounds = 0;
$totalAssists = 0;
$gamesPlayed = count($stats);

foreach ($stats as $stat) {
  $totalPoints += $stat['Points'];
  $totalRebounds += $stat['Rebounds'];
  $totalAssists += $stat['Assists'];
}

$avgPoints = $gamesPlayed > 0 ? round($totalPoints / $gamesPlayed, 1) : 0;
$avgRebounds = $gamesPlayed > 0 ? round($totalRebounds / $gamesPlayed, 1) : 0;
$avgAssists = $gamesPlayed > 0 ? round($totalAssists / $gamesPlayed, 1) : 0;

$playerName = htmlspecialchars($player['PlayerFname'] . ' ' . $player['PlayerLname']);
$avatarUrl = 'https://ui-avatars.com/api/?name=' . urlencode($player['PlayerFname'] . '+' . $player['PlayerLname']) . '&size=200&background=eb6f92&color=ffffff&bold=true&font-size=0.4';
?>

  <div class="content-wrapper">
    <!-- Back Button -->
    <div class="mb-3">
      <a href="/sports/public/players.php" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left me-2"></i>Back to Players
      </a>
    </div>

    <!-- Player Info Card -->
    <div class="card mb-4">
      <div class="card-body">
        <div class="row align-items-center">
          <div class="col-md-2 text-center mb-3 mb-md-0">
            <img src="<?= $avatarUrl ?>" alt="<?= $playerName ?>" class="rounded-circle" style="width:120px;height:120px;border:3px solid var(--rp-border);" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
            <div style="width:120px;height:120px;background:linear-gradient(135deg,var(--rp-accent),var(--rp-accent-hover));border-radius:50%;border:3px solid var(--rp-border);display:none;align-items:center;justify-content:center;margin:0 auto;">
              <i class="fas fa-user" style="color:white;font-size:3rem"></i>
            </div>
          </div>
          <div class="col-md-6">
            <h3 class="mb-1"><?= $playerName ?></h3>
            <p class="text-muted mb-2">
              <i class="fas fa-id-badge me-2"></i><?= htmlspecialchars($player['PlayerID']) ?>
            </p>
            <p class="mb-0">
              <i class="fas fa-shield-alt me-2"></i>
              <strong><?= htmlspecialchars($player['TeamName']) ?></strong>
              <span class="text-muted ms-2"><?= htmlspecialchars($player['TeamCity']) ?> • <?= htmlspecialchars($player['TeamConf']) ?></span>
            </p>
          </div>
          <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <div class="stat-badge">
              <div class="stat-value"><?= $gamesPlayed ?></div>
              <div class="stat-label">Games Played</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Career Statistics -->
    <div class="row mb-4">
      <div class="col-md-4 mb-3">
        <div class="stat-card">
          <div class="stat-icon" style="background: linear-gradient(135deg, var(--rp-love), var(--rp-rose));">
            <i class="fas fa-basketball-ball"></i>
          </div>
          <div class="stat-content">
            <div class="stat-value"><?= $avgPoints ?></div>
            <div class="stat-label">PPG (Avg Points)</div>
            <div class="text-muted small">Total: <?= $totalPoints ?></div>
          </div>
        </div>
      </div>
      <div class="col-md-4 mb-3">
        <div class="stat-card">
          <div class="stat-icon" style="background: linear-gradient(135deg, var(--rp-foam), var(--rp-pine));">
            <i class="fas fa-chart-line"></i>
          </div>
          <div class="stat-content">
            <div class="stat-value"><?= $avgRebounds ?></div>
            <div class="stat-label">RPG (Avg Rebounds)</div>
            <div class="text-muted small">Total: <?= $totalRebounds ?></div>
          </div>
        </div>
      </div>
      <div class="col-md-4 mb-3">
        <div class="stat-card">
          <div class="stat-icon" style="background: linear-gradient(135deg, var(--rp-iris), var(--rp-gold));">
            <i class="fas fa-hands-helping"></i>
          </div>
          <div class="stat-content">
            <div class="stat-value"><?= $avgAssists ?></div>
            <div class="stat-label">APG (Avg Assists)</div>
            <div class="text-muted small">Total: <?= $totalAssists ?></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Game-by-Game Stats -->
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Game Performance</h5>
      </div>
      <div class="card-body">
        <?php if (empty($stats)): ?>
          <p class="text-muted mb-0">No game statistics recorded yet.</p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Matchup</th>
                  <th>Venue</th>
                  <th class="text-center">PTS</th>
                  <th class="text-center">REB</th>
                  <th class="text-center">AST</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($stats as $stat): ?>
                  <tr>
                    <td><?= htmlspecialchars(date('M d, Y', strtotime($stat['GameDate']))) ?></td>
                    <td><?= htmlspecialchars($stat['Team1Name'] . ' vs ' . $stat['Team2Name']) ?></td>
                    <td><?= htmlspecialchars($stat['Venue']) ?></td>
                    <td class="text-center"><strong><?= $stat['Points'] ?></strong></td>
                    <td class="text-center"><?= $stat['Rebounds'] ?></td>
                    <td class="text-center"><?= $stat['Assists'] ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

<?php include __DIR__ . '/footer.php'; ?>
