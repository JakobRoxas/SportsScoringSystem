<?php
require_once __DIR__ . '/../config.php';
include __DIR__ . '/header.php';

$gameID = $_GET['id'] ?? '';

if (!$gameID) {
  echo '<div class="alert alert-danger m-4">No game ID provided.</div>';
  include __DIR__ . '/footer.php';
  exit;
}

// Fetch game details
$stmt = $pdo->prepare("
  SELECT g.*, 
         s.SeriesID, r.RoundType, t.TournamentName, t.TournamentYear,
         t1.TeamName as Team1Name, t1.TeamCity as Team1City, t1.TeamID as Team1ID,
         t2.TeamName as Team2Name, t2.TeamCity as Team2City, t2.TeamID as Team2ID,
         tw.TeamName as WinnerName,
         sc1.PointsScored as Team1Score,
         sc2.PointsScored as Team2Score
  FROM Game g
  JOIN Series s ON g.SeriesID = s.SeriesID
  JOIN Round r ON s.RoundID = r.RoundID
  JOIN Tournament t ON r.TournamentID = t.TournamentID
  JOIN Team t1 ON s.Team1ID = t1.TeamID
  JOIN Team t2 ON s.Team2ID = t2.TeamID
  LEFT JOIN Team tw ON g.WinnerTeamID = tw.TeamID
  LEFT JOIN Score sc1 ON g.GameID = sc1.GameID AND s.Team1ID = sc1.TeamID
  LEFT JOIN Score sc2 ON g.GameID = sc2.GameID AND s.Team2ID = sc2.TeamID
  WHERE g.GameID = ?
");
$stmt->execute([$gameID]);
$game = $stmt->fetch();

if (!$game) {
  echo '<div class="alert alert-danger m-4">Game not found.</div>';
  include __DIR__ . '/footer.php';
  exit;
}

// Fetch player stats for this game
$statsStmt = $pdo->prepare("
  SELECT ps.*, p.PlayerFname, p.PlayerLname, p.TeamID, t.TeamName
  FROM PlayerStat ps
  JOIN Player p ON ps.PlayerID = p.PlayerID
  JOIN Team t ON p.TeamID = t.TeamID
  WHERE ps.GameID = ?
  ORDER BY ps.Points DESC
");
$statsStmt->execute([$gameID]);
$playerStats = $statsStmt->fetchAll();

// Group stats by team
$team1Stats = [];
$team2Stats = [];
foreach ($playerStats as $stat) {
  if ($stat['TeamID'] === $game['Team1ID']) {
    $team1Stats[] = $stat;
  } else {
    $team2Stats[] = $stat;
  }
}
?>

  <div class="content-wrapper">
    <!-- Back Button -->
    <div class="mb-3">
      <a href="/sports/public/games.php" class="btn btn-sm btn-outline-secondary me-2">
        <i class="fas fa-arrow-left me-2"></i>Back to Games
      </a>
      <a href="/sports/public/series_view.php?id=<?= urlencode($game['SeriesID']) ?>" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-layer-group me-2"></i>View Series
      </a>
    </div>

    <!-- Game Header -->
    <div class="card mb-4">
      <div class="card-body">
        <div class="mb-3">
          <h6 class="text-muted mb-2">
            <i class="fas fa-trophy me-2"></i>
            <?= htmlspecialchars($game['TournamentName']) ?> (<?= htmlspecialchars($game['TournamentYear']) ?>)
          </h6>
          <span class="badge bg-secondary"><?= htmlspecialchars($game['RoundType']) ?></span>
          <span class="badge bg-info ms-2">Game <?= htmlspecialchars($game['GameNumber']) ?></span>
        </div>

        <!-- Score Display -->
        <div class="game-score-display">
          <div class="team-score-display <?= $game['WinnerTeamID'] === $game['Team1ID'] ? 'winner' : '' ?>">
            <div class="team-info">
              <h3><?= htmlspecialchars($game['Team1Name']) ?></h3>
              <p class="text-muted mb-0"><?= htmlspecialchars($game['Team1City']) ?></p>
            </div>
            <div class="final-score">
              <?= $game['Team1Score'] ?? '-' ?>
            </div>
          </div>

          <div class="score-divider">FINAL</div>

          <div class="team-score-display <?= $game['WinnerTeamID'] === $game['Team2ID'] ? 'winner' : '' ?>">
            <div class="team-info">
              <h3><?= htmlspecialchars($game['Team2Name']) ?></h3>
              <p class="text-muted mb-0"><?= htmlspecialchars($game['Team2City']) ?></p>
            </div>
            <div class="final-score">
              <?= $game['Team2Score'] ?? '-' ?>
            </div>
          </div>
        </div>

        <!-- Game Info -->
        <div class="row mt-4">
          <div class="col-md-6">
            <p class="mb-2">
              <i class="fas fa-calendar me-2"></i>
              <strong>Date:</strong> <?= htmlspecialchars(date('l, F j, Y', strtotime($game['GameDate']))) ?>
            </p>
          </div>
          <div class="col-md-6">
            <p class="mb-2">
              <i class="fas fa-map-marker-alt me-2"></i>
              <strong>Venue:</strong> <?= htmlspecialchars($game['Venue']) ?>
            </p>
          </div>
        </div>

        <div class="mt-2 text-muted small">
          <i class="fas fa-tag me-2"></i>Game ID: <?= htmlspecialchars($game['GameID']) ?>
        </div>
      </div>
    </div>

    <!-- Player Statistics -->
    <div class="row">
      <!-- Team 1 Stats -->
      <div class="col-lg-6 mb-4">
        <div class="card">
          <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><?= htmlspecialchars($game['Team1Name']) ?> - Player Stats</h5>
          </div>
          <div class="card-body">
            <?php if (empty($team1Stats)): ?>
              <p class="text-muted mb-0">No player statistics recorded.</p>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table table-sm">
                  <thead>
                    <tr>
                      <th>Player</th>
                      <th class="text-center">PTS</th>
                      <th class="text-center">REB</th>
                      <th class="text-center">AST</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($team1Stats as $stat): ?>
                      <tr>
                        <td>
                          <a href="/sports/public/player_view.php?id=<?= urlencode($stat['PlayerID']) ?>">
                            <?= htmlspecialchars($stat['PlayerFname'] . ' ' . $stat['PlayerLname']) ?>
                          </a>
                        </td>
                        <td class="text-center"><strong><?= $stat['Points'] ?></strong></td>
                        <td class="text-center"><?= $stat['Rebounds'] ?></td>
                        <td class="text-center"><?= $stat['Assists'] ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                  <tfoot>
                    <tr class="table-secondary">
                      <td><strong>Total</strong></td>
                      <td class="text-center"><strong><?= array_sum(array_column($team1Stats, 'Points')) ?></strong></td>
                      <td class="text-center"><strong><?= array_sum(array_column($team1Stats, 'Rebounds')) ?></strong></td>
                      <td class="text-center"><strong><?= array_sum(array_column($team1Stats, 'Assists')) ?></strong></td>
                    </tr>
                  </tfoot>
                </table>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Team 2 Stats -->
      <div class="col-lg-6 mb-4">
        <div class="card">
          <div class="card-header bg-danger text-white">
            <h5 class="mb-0"><?= htmlspecialchars($game['Team2Name']) ?> - Player Stats</h5>
          </div>
          <div class="card-body">
            <?php if (empty($team2Stats)): ?>
              <p class="text-muted mb-0">No player statistics recorded.</p>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table table-sm">
                  <thead>
                    <tr>
                      <th>Player</th>
                      <th class="text-center">PTS</th>
                      <th class="text-center">REB</th>
                      <th class="text-center">AST</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($team2Stats as $stat): ?>
                      <tr>
                        <td>
                          <a href="/sports/public/player_view.php?id=<?= urlencode($stat['PlayerID']) ?>">
                            <?= htmlspecialchars($stat['PlayerFname'] . ' ' . $stat['PlayerLname']) ?>
                          </a>
                        </td>
                        <td class="text-center"><strong><?= $stat['Points'] ?></strong></td>
                        <td class="text-center"><?= $stat['Rebounds'] ?></td>
                        <td class="text-center"><?= $stat['Assists'] ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                  <tfoot>
                    <tr class="table-secondary">
                      <td><strong>Total</strong></td>
                      <td class="text-center"><strong><?= array_sum(array_column($team2Stats, 'Points')) ?></strong></td>
                      <td class="text-center"><strong><?= array_sum(array_column($team2Stats, 'Rebounds')) ?></strong></td>
                      <td class="text-center"><strong><?= array_sum(array_column($team2Stats, 'Assists')) ?></strong></td>
                    </tr>
                  </tfoot>
                </table>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

<style>
.game-score-display {
  display: flex;
  align-items: center;
  gap: 2rem;
  margin: 1.5rem 0;
}
.team-score-display {
  flex: 1;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 2rem;
  background: var(--rp-surface);
  border: 3px solid var(--rp-border);
  border-radius: 12px;
  transition: all 0.3s ease;
}
.team-score-display.winner {
  background: linear-gradient(135deg, var(--rp-foam), var(--rp-pine));
  border-color: var(--rp-foam);
  color: var(--rp-base);
  box-shadow: 0 0 20px rgba(156, 207, 216, 0.3);
}
.team-score-display.winner .text-muted {
  color: rgba(255,255,255,0.8) !important;
}
.team-score-display h3 {
  margin: 0;
  font-size: 1.75rem;
}
.final-score {
  font-size: 4rem;
  font-weight: bold;
  line-height: 1;
}
.score-divider {
  font-weight: bold;
  color: var(--rp-muted);
  font-size: 0.875rem;
  writing-mode: vertical-rl;
  text-orientation: upright;
}
@media (max-width: 768px) {
  .game-score-display {
    flex-direction: column;
    gap: 1rem;
  }
  .score-divider {
    writing-mode: horizontal-tb;
    text-orientation: initial;
  }
  .final-score {
    font-size: 3rem;
  }
}
</style>

<?php include __DIR__ . '/footer.php'; ?>
