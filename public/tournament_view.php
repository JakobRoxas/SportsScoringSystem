<?php
require_once __DIR__ . '/../config.php';
include __DIR__ . '/header.php';

$tournamentID = $_GET['id'] ?? '';

if (!$tournamentID) {
  echo '<div class="alert alert-danger m-4">No tournament ID provided.</div>';
  include __DIR__ . '/footer.php';
  exit;
}

// Fetch tournament details
$stmt = $pdo->prepare("SELECT * FROM Tournament WHERE TournamentID = ?");
$stmt->execute([$tournamentID]);
$tournament = $stmt->fetch();

if (!$tournament) {
  echo '<div class="alert alert-danger m-4">Tournament not found.</div>';
  include __DIR__ . '/footer.php';
  exit;
}

// Fetch participating teams
$teamsStmt = $pdo->prepare("
  SELECT tt.*, t.TeamName, t.TeamCity, t.TeamConf 
  FROM TournamentTeam tt 
  JOIN Team t ON tt.TeamID = t.TeamID 
  WHERE tt.TournamentID = ?
  ORDER BY tt.Seed
");
$teamsStmt->execute([$tournamentID]);
$teams = $teamsStmt->fetchAll();

// Fetch rounds
$roundsStmt = $pdo->prepare("
  SELECT r.*, COUNT(s.SeriesID) as SeriesCount
  FROM Round r
  LEFT JOIN Series s ON r.RoundID = s.RoundID
  WHERE r.TournamentID = ?
  GROUP BY r.RoundID
  ORDER BY r.RoundNumber
");
$roundsStmt->execute([$tournamentID]);
$rounds = $roundsStmt->fetchAll();

// Fetch all series with details
$seriesStmt = $pdo->prepare("
  SELECT s.*, r.RoundType, r.RoundNumber,
         t1.TeamName as Team1Name, t2.TeamName as Team2Name,
         tw.TeamName as WinnerName
  FROM Series s
  JOIN Round r ON s.RoundID = r.RoundID
  JOIN Team t1 ON s.Team1ID = t1.TeamID
  JOIN Team t2 ON s.Team2ID = t2.TeamID
  LEFT JOIN Team tw ON s.WinnerTeamID = tw.TeamID
  WHERE r.TournamentID = ?
  ORDER BY r.RoundNumber, s.SeriesID
");
$seriesStmt->execute([$tournamentID]);
$allSeries = $seriesStmt->fetchAll();

// Get champion (winner of FINALS)
$championStmt = $pdo->prepare("
  SELECT t.TeamName, t.TeamCity
  FROM Series s
  JOIN Round r ON s.RoundID = r.RoundID
  JOIN Team t ON s.WinnerTeamID = t.TeamID
  WHERE r.TournamentID = ? AND r.RoundType = 'FINALS'
  LIMIT 1
");
$championStmt->execute([$tournamentID]);
$champion = $championStmt->fetch();
?>

  <div class="content-wrapper">
    <!-- Back Button -->
    <div class="mb-3">
      <a href="/sports/public/tournaments.php" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left me-2"></i>Back to Tournaments
      </a>
    </div>

    <!-- Tournament Header -->
    <div class="card mb-4">
      <div class="card-body">
        <div class="row align-items-center">
          <div class="col-md-8">
            <h3 class="mb-1">
              <i class="fas fa-trophy me-2" style="color: var(--rp-gold);"></i>
              <?= htmlspecialchars($tournament['TournamentName']) ?>
            </h3>
            <p class="text-muted mb-2">
              <i class="fas fa-calendar me-2"></i><?= htmlspecialchars($tournament['TournamentYear']) ?>
            </p>
            <p class="mb-0">
              <i class="fas fa-tag me-2"></i>ID: <?= htmlspecialchars($tournament['TournamentID']) ?>
            </p>
          </div>
          <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <?php if ($champion): ?>
              <div class="champion-badge">
                <div class="trophy-icon">🏆</div>
                <div class="champion-text">
                  <div class="small text-muted">Champion</div>
                  <div class="fw-bold"><?= htmlspecialchars($champion['TeamName']) ?></div>
                </div>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Stats Overview -->
    <div class="row mb-4">
      <div class="col-md-4 mb-3">
        <div class="stat-card">
          <div class="stat-icon" style="background: linear-gradient(135deg, var(--rp-foam), var(--rp-pine));">
            <i class="fas fa-users"></i>
          </div>
          <div class="stat-content">
            <div class="stat-value"><?= count($teams) ?></div>
            <div class="stat-label">Teams</div>
          </div>
        </div>
      </div>
      <div class="col-md-4 mb-3">
        <div class="stat-card">
          <div class="stat-icon" style="background: linear-gradient(135deg, var(--rp-iris), var(--rp-gold));">
            <i class="fas fa-layer-group"></i>
          </div>
          <div class="stat-content">
            <div class="stat-value"><?= count($rounds) ?></div>
            <div class="stat-label">Rounds</div>
          </div>
        </div>
      </div>
      <div class="col-md-4 mb-3">
        <div class="stat-card">
          <div class="stat-icon" style="background: linear-gradient(135deg, var(--rp-love), var(--rp-rose));">
            <i class="fas fa-gamepad"></i>
          </div>
          <div class="stat-content">
            <div class="stat-value"><?= count($allSeries) ?></div>
            <div class="stat-label">Series</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Participating Teams -->
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Participating Teams</h5>
      </div>
      <div class="card-body">
        <?php if (empty($teams)): ?>
          <p class="text-muted mb-0">No teams registered yet.</p>
        <?php else: ?>
          <div class="row">
            <?php foreach ($teams as $team): ?>
              <div class="col-md-6 col-lg-4 mb-3">
                <div class="team-seed-card">
                  <div class="seed-badge">#<?= $team['Seed'] ?></div>
                  <div>
                    <div class="fw-bold"><?= htmlspecialchars($team['TeamName']) ?></div>
                    <div class="text-muted small"><?= htmlspecialchars($team['TeamCity']) ?> • <?= htmlspecialchars($team['TeamConf']) ?></div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Tournament Bracket / Series -->
    <?php if (!empty($allSeries)): ?>
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0"><i class="fas fa-sitemap me-2"></i>Tournament Bracket</h5>
        </div>
        <div class="card-body">
          <?php
          $currentRound = null;
          foreach ($allSeries as $series):
            if ($currentRound !== $series['RoundType']):
              if ($currentRound !== null) echo '</div>';
              $currentRound = $series['RoundType'];
              ?>
              <h6 class="mt-3 mb-3">
                <span class="badge bg-secondary"><?= htmlspecialchars($series['RoundType']) ?></span>
              </h6>
              <div class="row">
            <?php endif; ?>
            
            <div class="col-md-6 mb-3">
              <div class="series-bracket-card">
                <div class="matchup">
                  <div class="team-matchup <?= $series['WinnerTeamID'] === $series['Team1ID'] ? 'winner' : '' ?>">
                    <?= htmlspecialchars($series['Team1Name']) ?>
                    <?php if ($series['WinnerTeamID'] === $series['Team1ID']): ?>
                      <i class="fas fa-crown ms-2" style="color: var(--rp-gold);"></i>
                    <?php endif; ?>
                  </div>
                  <div class="vs-text">VS</div>
                  <div class="team-matchup <?= $series['WinnerTeamID'] === $series['Team2ID'] ? 'winner' : '' ?>">
                    <?= htmlspecialchars($series['Team2Name']) ?>
                    <?php if ($series['WinnerTeamID'] === $series['Team2ID']): ?>
                      <i class="fas fa-crown ms-2" style="color: var(--rp-gold);"></i>
                    <?php endif; ?>
                  </div>
                </div>
                <div class="series-info">
                  <small class="text-muted">Series ID: <?= htmlspecialchars($series['SeriesID']) ?></small>
                  <a href="/sports/public/series_view.php?id=<?= urlencode($series['SeriesID']) ?>" class="btn btn-sm btn-outline-primary">View Games</a>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>

<style>
.champion-badge {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: linear-gradient(135deg, var(--rp-gold), var(--rp-rose));
  border-radius: 12px;
  color: var(--rp-base);
}
.trophy-icon {
  font-size: 2rem;
}
.team-seed-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.75rem;
  background: var(--rp-surface);
  border: 1px solid var(--rp-border);
  border-radius: 8px;
}
.seed-badge {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--rp-accent);
  color: var(--rp-base);
  border-radius: 50%;
  font-weight: bold;
}
.series-bracket-card {
  padding: 1rem;
  background: var(--rp-surface);
  border: 1px solid var(--rp-border);
  border-radius: 8px;
}
.matchup {
  margin-bottom: 0.75rem;
}
.team-matchup {
  padding: 0.5rem;
  background: var(--rp-overlay);
  border-radius: 6px;
  margin-bottom: 0.25rem;
}
.team-matchup.winner {
  background: linear-gradient(135deg, var(--rp-foam), var(--rp-pine));
  color: var(--rp-base);
  font-weight: bold;
}
.vs-text {
  text-align: center;
  font-size: 0.75rem;
  color: var(--rp-muted);
  margin: 0.25rem 0;
}
.series-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
</style>

<?php include __DIR__ . '/footer.php'; ?>
