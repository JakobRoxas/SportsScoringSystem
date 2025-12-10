<?php
require_once __DIR__ . '/../config.php';
include __DIR__ . '/header.php';

$tournament = isset($_GET['tournament']) ? trim($_GET['tournament']) : '';

// Get list of tournaments for filter
$tournamentsStmt = $pdo->query("
  SELECT TournamentID, TournamentName, TournamentYear 
  FROM Tournament 
  ORDER BY TournamentYear DESC, TournamentName
");
$tournaments = $tournamentsStmt->fetchAll();

// If tournament selected, get standings
$standings = [];
$selectedTournament = null;

if ($tournament !== '') {
  // Get tournament info
  $stmt = $pdo->prepare("SELECT * FROM Tournament WHERE TournamentID = ?");
  $stmt->execute([$tournament]);
  $selectedTournament = $stmt->fetch();

  if ($selectedTournament) {
    // Get all teams in tournament with their stats
    $standingsStmt = $pdo->prepare("
      SELECT 
        t.TeamID,
        t.TeamName,
        t.TeamCity,
        t.TeamConf,
        tt.Seed,
        COUNT(DISTINCT CASE WHEN s.WinnerTeamID = t.TeamID THEN s.SeriesID END) as SeriesWins,
        COUNT(DISTINCT CASE WHEN (s.Team1ID = t.TeamID OR s.Team2ID = t.TeamID) AND s.WinnerTeamID IS NOT NULL AND s.WinnerTeamID != t.TeamID THEN s.SeriesID END) as SeriesLosses,
        COUNT(DISTINCT CASE WHEN g.WinnerTeamID = t.TeamID THEN g.GameID END) as GameWins,
        COUNT(DISTINCT CASE WHEN (sc1.TeamID = t.TeamID OR sc2.TeamID = t.TeamID) AND g.WinnerTeamID IS NOT NULL AND g.WinnerTeamID != t.TeamID THEN g.GameID END) as GameLosses,
        COALESCE(SUM(CASE WHEN sc.TeamID = t.TeamID THEN sc.PointsScored END), 0) as PointsFor,
        COALESCE(SUM(CASE WHEN sc.TeamID != t.TeamID AND (g.SeriesID IN (SELECT SeriesID FROM Series WHERE Team1ID = t.TeamID OR Team2ID = t.TeamID)) THEN sc.PointsScored END), 0) as PointsAgainst
      FROM TournamentTeam tt
      JOIN Team t ON tt.TeamID = t.TeamID
      LEFT JOIN Series s ON (s.Team1ID = t.TeamID OR s.Team2ID = t.TeamID)
      LEFT JOIN Round r ON s.RoundID = r.RoundID AND r.TournamentID = tt.TournamentID
      LEFT JOIN Game g ON g.SeriesID = s.SeriesID
      LEFT JOIN Score sc ON g.GameID = sc.GameID
      LEFT JOIN Score sc1 ON g.GameID = sc1.GameID AND sc1.TeamID = t.TeamID
      LEFT JOIN Score sc2 ON g.GameID = sc2.GameID AND sc2.TeamID != t.TeamID
      WHERE tt.TournamentID = ?
      GROUP BY t.TeamID, t.TeamName, t.TeamCity, t.TeamConf, tt.Seed
      ORDER BY SeriesWins DESC, GameWins DESC, PointsFor DESC
    ");
    $standingsStmt->execute([$tournament]);
    $standings = $standingsStmt->fetchAll();
  }
}

// Group standings by conference
$eastStandings = [];
$westStandings = [];
foreach ($standings as $team) {
  if ($team['TeamConf'] === 'EAST') {
    $eastStandings[] = $team;
  } else {
    $westStandings[] = $team;
  }
}
?>

<div class="mb-4">
  <h2 class="mb-3"><i class="fas fa-list-ol me-2"></i>Standings</h2>
  
  <div class="card p-3 mb-4">
    <form class="row g-3 align-items-end" method="get">
      <div class="col-md-10">
        <label class="form-label small text-muted">Select Tournament</label>
        <select name="tournament" class="form-select" required>
          <option value="">Choose a tournament...</option>
          <?php foreach ($tournaments as $t): ?>
            <option value="<?= htmlspecialchars($t['TournamentID']) ?>" <?= $tournament === $t['TournamentID'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($t['TournamentName']) ?> (<?= $t['TournamentYear'] ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <button class="btn btn-primary w-100">View Standings</button>
      </div>
    </form>
  </div>
</div>

<?php if ($selectedTournament && count($standings) > 0): ?>
  <div class="card mb-4 p-4">
    <h4 class="mb-3">
      <i class="fas fa-trophy me-2 text-muted"></i>
      <?= htmlspecialchars($selectedTournament['TournamentName']) ?>
      <span class="text-muted ms-2"><?= $selectedTournament['TournamentYear'] ?></span>
    </h4>
    
    <div class="row">
      <!-- EAST Conference -->
      <?php if (count($eastStandings) > 0): ?>
        <div class="col-lg-6 mb-4">
          <div class="card">
            <div class="card-body">
              <h5 class="mb-3">
                <span class="badge" style="background:var(--rp-love);color:white;font-size:1rem;">EAST Conference</span>
              </h5>
              <div class="table-responsive">
                <table class="table table-sm">
                  <thead>
                    <tr>
                      <th>Rank</th>
                      <th>Team</th>
                      <th class="text-center">Seed</th>
                      <th class="text-center">Series W-L</th>
                      <th class="text-center">Game W-L</th>
                      <th class="text-center">PF</th>
                      <th class="text-center">PA</th>
                      <th class="text-center">Diff</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php 
                    $rank = 1;
                    foreach ($eastStandings as $team): 
                      $diff = $team['PointsFor'] - $team['PointsAgainst'];
                      $diffClass = $diff > 0 ? 'text-success' : ($diff < 0 ? 'text-danger' : 'text-muted');
                    ?>
                      <tr>
                        <td><strong><?= $rank++ ?></strong></td>
                        <td>
                          <a href="team_view.php?id=<?= urlencode($team['TeamID']) ?>" class="text-decoration-none">
                            <?= htmlspecialchars($team['TeamName']) ?>
                          </a>
                        </td>
                        <td class="text-center"><?= $team['Seed'] ?></td>
                        <td class="text-center"><?= $team['SeriesWins'] ?>-<?= $team['SeriesLosses'] ?></td>
                        <td class="text-center"><?= $team['GameWins'] ?>-<?= $team['GameLosses'] ?></td>
                        <td class="text-center"><?= $team['PointsFor'] ?></td>
                        <td class="text-center"><?= $team['PointsAgainst'] ?></td>
                        <td class="text-center <?= $diffClass ?>"><?= $diff > 0 ? '+' : '' ?><?= $diff ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <!-- WEST Conference -->
      <?php if (count($westStandings) > 0): ?>
        <div class="col-lg-6 mb-4">
          <div class="card">
            <div class="card-body">
              <h5 class="mb-3">
                <span class="badge" style="background:var(--rp-gold);color:white;font-size:1rem;">WEST Conference</span>
              </h5>
              <div class="table-responsive">
                <table class="table table-sm">
                  <thead>
                    <tr>
                      <th>Rank</th>
                      <th>Team</th>
                      <th class="text-center">Seed</th>
                      <th class="text-center">Series W-L</th>
                      <th class="text-center">Game W-L</th>
                      <th class="text-center">PF</th>
                      <th class="text-center">PA</th>
                      <th class="text-center">Diff</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php 
                    $rank = 1;
                    foreach ($westStandings as $team): 
                      $diff = $team['PointsFor'] - $team['PointsAgainst'];
                      $diffClass = $diff > 0 ? 'text-success' : ($diff < 0 ? 'text-danger' : 'text-muted');
                    ?>
                      <tr>
                        <td><strong><?= $rank++ ?></strong></td>
                        <td>
                          <a href="team_view.php?id=<?= urlencode($team['TeamID']) ?>" class="text-decoration-none">
                            <?= htmlspecialchars($team['TeamName']) ?>
                          </a>
                        </td>
                        <td class="text-center"><?= $team['Seed'] ?></td>
                        <td class="text-center"><?= $team['SeriesWins'] ?>-<?= $team['SeriesLosses'] ?></td>
                        <td class="text-center"><?= $team['GameWins'] ?>-<?= $team['GameLosses'] ?></td>
                        <td class="text-center"><?= $team['PointsFor'] ?></td>
                        <td class="text-center"><?= $team['PointsAgainst'] ?></td>
                        <td class="text-center <?= $diffClass ?>"><?= $diff > 0 ? '+' : '' ?><?= $diff ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <!-- Overall Combined Standings -->
    <div class="card mt-3">
      <div class="card-body">
        <h5 class="mb-3">
          <i class="fas fa-globe me-2 text-muted"></i>Overall Standings
        </h5>
        <div class="table-responsive">
          <table class="table table-sm">
            <thead>
              <tr>
                <th>Rank</th>
                <th>Team</th>
                <th class="text-center">Conference</th>
                <th class="text-center">Seed</th>
                <th class="text-center">Series W-L</th>
                <th class="text-center">Game W-L</th>
                <th class="text-center">Points For</th>
                <th class="text-center">Points Against</th>
                <th class="text-center">Diff</th>
              </tr>
            </thead>
            <tbody>
              <?php 
              $rank = 1;
              foreach ($standings as $team): 
                $diff = $team['PointsFor'] - $team['PointsAgainst'];
                $diffClass = $diff > 0 ? 'text-success' : ($diff < 0 ? 'text-danger' : 'text-muted');
              ?>
                <tr>
                  <td><strong><?= $rank++ ?></strong></td>
                  <td>
                    <a href="team_view.php?id=<?= urlencode($team['TeamID']) ?>" class="text-decoration-none">
                      <?= htmlspecialchars($team['TeamName']) ?>
                    </a>
                  </td>
                  <td class="text-center">
                    <span class="badge" style="background:var(--rp-<?= strtolower($team['TeamConf']) === 'east' ? 'love' : 'gold' ?>);color:white;font-size:0.7rem;">
                      <?= htmlspecialchars($team['TeamConf']) ?>
                    </span>
                  </td>
                  <td class="text-center"><?= $team['Seed'] ?></td>
                  <td class="text-center"><strong><?= $team['SeriesWins'] ?>-<?= $team['SeriesLosses'] ?></strong></td>
                  <td class="text-center"><?= $team['GameWins'] ?>-<?= $team['GameLosses'] ?></td>
                  <td class="text-center"><?= $team['PointsFor'] ?></td>
                  <td class="text-center"><?= $team['PointsAgainst'] ?></td>
                  <td class="text-center <?= $diffClass ?>"><strong><?= $diff > 0 ? '+' : '' ?><?= $diff ?></strong></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Legend -->
    <div class="mt-3 text-muted small">
      <strong>Legend:</strong> PF = Points For, PA = Points Against, Diff = Point Differential
    </div>
  </div>
<?php elseif ($selectedTournament): ?>
  <div class="alert alert-info">
    <i class="fas fa-info-circle me-2"></i>No teams found for this tournament.
  </div>
<?php else: ?>
  <div class="alert alert-info">
    <i class="fas fa-info-circle me-2"></i>Select a tournament above to view standings.
  </div>
<?php endif; ?>

<?php include __DIR__ . '/footer.php'; ?>
