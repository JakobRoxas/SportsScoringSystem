<?php
require_once __DIR__ . '/../config.php';
include __DIR__ . '/header.php';

$seriesID = $_GET['id'] ?? '';

if (!$seriesID) {
  echo '<div class="alert alert-danger m-4">No series ID provided.</div>';
  include __DIR__ . '/footer.php';
  exit;
}

// Fetch series details
$stmt = $pdo->prepare("
  SELECT s.*, r.RoundType, r.RoundNumber, t.TournamentName, t.TournamentYear,
         t1.TeamName as Team1Name, t1.TeamCity as Team1City,
         t2.TeamName as Team2Name, t2.TeamCity as Team2City,
         tw.TeamName as WinnerName
  FROM Series s
  JOIN Round r ON s.RoundID = r.RoundID
  JOIN Tournament t ON r.TournamentID = t.TournamentID
  JOIN Team t1 ON s.Team1ID = t1.TeamID
  JOIN Team t2 ON s.Team2ID = t2.TeamID
  LEFT JOIN Team tw ON s.WinnerTeamID = tw.TeamID
  WHERE s.SeriesID = ?
");
$stmt->execute([$seriesID]);
$series = $stmt->fetch();

if (!$series) {
  echo '<div class="alert alert-danger m-4">Series not found.</div>';
  include __DIR__ . '/footer.php';
  exit;
}

// Fetch games in this series
$gamesStmt = $pdo->prepare("
  SELECT g.*, 
         t1.TeamName as Team1Name, t2.TeamName as Team2Name,
         tw.TeamName as WinnerName,
         sc1.PointsScored as Team1Score,
         sc2.PointsScored as Team2Score
  FROM Game g
  LEFT JOIN Team tw ON g.WinnerTeamID = tw.TeamID
  JOIN Series s ON g.SeriesID = s.SeriesID
  JOIN Team t1 ON s.Team1ID = t1.TeamID
  JOIN Team t2 ON s.Team2ID = t2.TeamID
  LEFT JOIN Score sc1 ON g.GameID = sc1.GameID AND s.Team1ID = sc1.TeamID
  LEFT JOIN Score sc2 ON g.GameID = sc2.GameID AND s.Team2ID = sc2.TeamID
  WHERE g.SeriesID = ?
  ORDER BY g.GameNumber
");
$gamesStmt->execute([$seriesID]);
$games = $gamesStmt->fetchAll();

// Calculate series score
$team1Wins = 0;
$team2Wins = 0;
foreach ($games as $game) {
  if ($game['WinnerTeamID'] === $series['Team1ID']) $team1Wins++;
  if ($game['WinnerTeamID'] === $series['Team2ID']) $team2Wins++;
}
?>

  <div class="content-wrapper">
    <!-- Back Button -->
    <div class="mb-3">
      <a href="/sports/public/series.php" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left me-2"></i>Back to Series
      </a>
    </div>

    <!-- Series Header -->
    <div class="card mb-4">
      <div class="card-body">
        <div class="mb-3">
          <h6 class="text-muted mb-2">
            <i class="fas fa-trophy me-2"></i>
            <?= htmlspecialchars($series['TournamentName']) ?> (<?= htmlspecialchars($series['TournamentYear']) ?>)
          </h6>
          <span class="badge bg-secondary"><?= htmlspecialchars($series['RoundType']) ?></span>
          <span class="badge bg-info ms-2">Round <?= htmlspecialchars($series['RoundNumber']) ?></span>
        </div>

        <!-- Matchup Display -->
        <div class="series-matchup-display">
          <div class="team-display <?= $series['WinnerTeamID'] === $series['Team1ID'] ? 'winner' : '' ?>">
            <div class="team-info">
              <h4><?= htmlspecialchars($series['Team1Name']) ?></h4>
              <p class="text-muted mb-0"><?= htmlspecialchars($series['Team1City']) ?></p>
            </div>
            <div class="team-score">
              <div class="score-number"><?= $team1Wins ?></div>
              <?php if ($series['WinnerTeamID'] === $series['Team1ID']): ?>
                <i class="fas fa-crown" style="color: var(--rp-gold);"></i>
              <?php endif; ?>
            </div>
          </div>

          <div class="vs-divider">
            <span>VS</span>
          </div>

          <div class="team-display <?= $series['WinnerTeamID'] === $series['Team2ID'] ? 'winner' : '' ?>">
            <div class="team-info">
              <h4><?= htmlspecialchars($series['Team2Name']) ?></h4>
              <p class="text-muted mb-0"><?= htmlspecialchars($series['Team2City']) ?></p>
            </div>
            <div class="team-score">
              <div class="score-number"><?= $team2Wins ?></div>
              <?php if ($series['WinnerTeamID'] === $series['Team2ID']): ?>
                <i class="fas fa-crown" style="color: var(--rp-gold);"></i>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <div class="mt-3 text-muted small">
          <i class="fas fa-tag me-2"></i>Series ID: <?= htmlspecialchars($series['SeriesID']) ?>
        </div>
      </div>
    </div>

    <!-- Games in Series -->
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-gamepad me-2"></i>Games (<?= count($games) ?>)</h5>
      </div>
      <div class="card-body">
        <?php if (empty($games)): ?>
          <p class="text-muted mb-0">No games scheduled yet for this series.</p>
        <?php else: ?>
          <div class="row">
            <?php foreach ($games as $game): ?>
              <div class="col-md-6 mb-3">
                <div class="game-detail-card">
                  <div class="game-header">
                    <span class="badge bg-primary">Game <?= htmlspecialchars($game['GameNumber']) ?></span>
                    <span class="text-muted small"><?= htmlspecialchars(date('M d, Y', strtotime($game['GameDate']))) ?></span>
                  </div>
                  
                  <div class="game-score">
                    <div class="team-score-line <?= $game['WinnerTeamID'] === $series['Team1ID'] ? 'winner' : '' ?>">
                      <span class="team-name"><?= htmlspecialchars($game['Team1Name']) ?></span>
                      <span class="score"><?= $game['Team1Score'] ?? '-' ?></span>
                    </div>
                    <div class="team-score-line <?= $game['WinnerTeamID'] === $series['Team2ID'] ? 'winner' : '' ?>">
                      <span class="team-name"><?= htmlspecialchars($game['Team2Name']) ?></span>
                      <span class="score"><?= $game['Team2Score'] ?? '-' ?></span>
                    </div>
                  </div>

                  <div class="game-footer">
                    <div class="venue-info">
                      <i class="fas fa-map-marker-alt me-1"></i>
                      <?= htmlspecialchars($game['Venue']) ?>
                    </div>
                    <a href="/sports/public/game_view.php?id=<?= urlencode($game['GameID']) ?>" class="btn btn-sm btn-outline-primary">
                      Details
                    </a>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

<style>
.series-matchup-display {
  display: flex;
  align-items: center;
  gap: 2rem;
  margin: 1.5rem 0;
}
.team-display {
  flex: 1;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  background: var(--rp-surface);
  border: 2px solid var(--rp-border);
  border-radius: 12px;
  transition: all 0.3s ease;
}
.team-display.winner {
  background: linear-gradient(135deg, var(--rp-foam), var(--rp-pine));
  border-color: var(--rp-foam);
  color: var(--rp-base);
}
.team-display.winner .text-muted {
  color: rgba(255,255,255,0.8) !important;
}
.team-info h4 {
  margin: 0;
  font-size: 1.5rem;
}
.team-score {
  text-align: center;
}
.score-number {
  font-size: 2.5rem;
  font-weight: bold;
  line-height: 1;
}
.vs-divider {
  font-weight: bold;
  color: var(--rp-muted);
  font-size: 1.2rem;
}
.game-detail-card {
  background: var(--rp-surface);
  border: 1px solid var(--rp-border);
  border-radius: 8px;
  padding: 1rem;
}
.game-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid var(--rp-border);
}
.game-score {
  margin: 1rem 0;
}
.team-score-line {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem;
  margin-bottom: 0.5rem;
  background: var(--rp-overlay);
  border-radius: 6px;
}
.team-score-line.winner {
  background: var(--rp-accent);
  color: var(--rp-base);
  font-weight: bold;
}
.team-score-line .score {
  font-size: 1.25rem;
  font-weight: bold;
}
.game-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 0.75rem;
  border-top: 1px solid var(--rp-border);
}
.venue-info {
  font-size: 0.875rem;
  color: var(--rp-muted);
}
@media (max-width: 768px) {
  .series-matchup-display {
    flex-direction: column;
    gap: 1rem;
  }
  .vs-divider {
    transform: rotate(90deg);
  }
}
</style>

<?php include __DIR__ . '/footer.php'; ?>