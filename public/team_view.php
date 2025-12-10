<?php
require_once __DIR__ . '/../config.php';
include __DIR__ . '/header.php';

$teamID = $_GET['id'] ?? '';

if (!$teamID) {
  echo '<div class="alert alert-danger m-4">No team ID provided.</div>';
  include __DIR__ . '/footer.php';
  exit;
}

// Fetch team details
$stmt = $pdo->prepare("SELECT * FROM Team WHERE TeamID = ?");
$stmt->execute([$teamID]);
$team = $stmt->fetch();

if (!$team) {
  echo '<div class="alert alert-danger m-4">Team not found.</div>';
  include __DIR__ . '/footer.php';
  exit;
}

// Fetch players
$playersStmt = $pdo->prepare("
  SELECT PlayerID, PlayerFname, PlayerLname 
  FROM Player 
  WHERE TeamID = ? 
  ORDER BY PlayerLname, PlayerFname
");
$playersStmt->execute([$teamID]);
$players = $playersStmt->fetchAll();

// Fetch coaches
$coachesStmt = $pdo->prepare("
  SELECT CoachID, CoachFname, CoachLname 
  FROM Coach 
  WHERE TeamID = ? 
  ORDER BY CoachLname, CoachFname
");
$coachesStmt->execute([$teamID]);
$coaches = $coachesStmt->fetchAll();

// Fetch recent games/series
$seriesStmt = $pdo->prepare("
  SELECT s.SeriesID, s.Team1ID, s.Team2ID, s.WinnerTeamID,
         t1.TeamName as Team1Name, t2.TeamName as Team2Name,
         r.RoundType, r.RoundNumber, t.TournamentName, t.TournamentYear
  FROM Series s
  JOIN Round r ON s.RoundID = r.RoundID
  JOIN Tournament t ON r.TournamentID = t.TournamentID
  JOIN Team t1 ON s.Team1ID = t1.TeamID
  JOIN Team t2 ON s.Team2ID = t2.TeamID
  WHERE s.Team1ID = ? OR s.Team2ID = ?
  ORDER BY t.TournamentYear DESC, r.RoundNumber DESC
  LIMIT 10
");
$seriesStmt->execute([$teamID, $teamID]);
$series = $seriesStmt->fetchAll();

$teamName = htmlspecialchars($team['TeamName']);
$avatarUrl = 'https://ui-avatars.com/api/?name=' . urlencode($team['TeamName']) . '&size=200&background=3e8fb0&color=ffffff&bold=true&font-size=0.4';
?>

  <div class="content-wrapper">
    <!-- Back Button -->
    <div class="mb-3">
      <a href="/sports/public/teams.php" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left me-2"></i>Back to Teams
      </a>
    </div>

    <!-- Team Info Card -->
    <div class="card mb-4">
      <div class="card-body">
        <div class="row align-items-center">
          <div class="col-md-2 text-center mb-3 mb-md-0">
            <img src="<?= $avatarUrl ?>" alt="<?= $teamName ?>" class="rounded-3" style="width:140px;height:140px;border:3px solid var(--rp-border);" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
            <div style="width:140px;height:140px;background:linear-gradient(135deg,var(--rp-pine),var(--rp-foam));border-radius:12px;border:3px solid var(--rp-border);display:none;align-items:center;justify-content:center;margin:0 auto;">
              <i class="fas fa-shield-alt" style="color:white;font-size:4rem"></i>
            </div>
          </div>
          <div class="col-md-6">
            <h3 class="mb-2"><?= $teamName ?></h3>
            <p class="text-muted mb-2">
              <i class="fas fa-id-badge me-2"></i><?= htmlspecialchars($team['TeamID']) ?>
            </p>
            <p class="mb-2">
              <i class="fas fa-map-marker-alt me-2"></i>
              <strong><?= htmlspecialchars($team['TeamCity']) ?></strong>
            </p>
            <p class="mb-0">
              <span class="badge" style="background:var(--rp-<?= strtolower($team['TeamConf']) === 'east' ? 'love' : 'gold' ?>);color:white;font-size:0.9rem;padding:0.5rem 1rem;">
                <?= htmlspecialchars($team['TeamConf']) ?> Conference
              </span>
            </p>
          </div>
          <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <div class="row g-2">
              <div class="col-6">
                <div class="stat-badge text-center p-3 rounded" style="background:var(--rp-overlay);">
                  <div class="stat-value" style="font-size:2rem;font-weight:700;color:var(--rp-text);"><?= count($players) ?></div>
                  <div class="stat-label" style="font-size:0.75rem;color:var(--rp-subtle);">PLAYERS</div>
                </div>
              </div>
              <div class="col-6">
                <div class="stat-badge text-center p-3 rounded" style="background:var(--rp-overlay);">
                  <div class="stat-value" style="font-size:2rem;font-weight:700;color:var(--rp-text);"><?= count($coaches) ?></div>
                  <div class="stat-label" style="font-size:0.75rem;color:var(--rp-subtle);">COACHES</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Roster & Series -->
    <div class="row">
      <!-- Players -->
      <div class="col-md-6 mb-4">
        <div class="card">
          <div class="card-body">
            <h5 class="mb-3"><i class="fas fa-users me-2 text-muted"></i>Roster</h5>
            <?php if (count($players) === 0): ?>
              <p class="text-muted">No players on this team.</p>
            <?php else: ?>
              <div class="list-group list-group-flush">
                <?php foreach ($players as $p): 
                  $playerName = htmlspecialchars($p['PlayerFname'] . ' ' . $p['PlayerLname']);
                ?>
                  <a href="player_view.php?id=<?= urlencode($p['PlayerID']) ?>" class="list-group-item list-group-item-action d-flex align-items-center gap-2 border-0" style="padding:0.75rem 0;">
                    <i class="fas fa-user-circle text-muted" style="font-size:1.5rem;"></i>
                    <div>
                      <div style="font-weight:500;"><?= $playerName ?></div>
                      <small class="text-muted"><?= htmlspecialchars($p['PlayerID']) ?></small>
                    </div>
                  </a>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Coaches -->
      <div class="col-md-6 mb-4">
        <div class="card">
          <div class="card-body">
            <h5 class="mb-3"><i class="fas fa-chalkboard-teacher me-2 text-muted"></i>Coaching Staff</h5>
            <?php if (count($coaches) === 0): ?>
              <p class="text-muted">No coaches for this team.</p>
            <?php else: ?>
              <div class="list-group list-group-flush">
                <?php foreach ($coaches as $c): 
                  $coachName = htmlspecialchars($c['CoachFname'] . ' ' . $c['CoachLname']);
                ?>
                  <div class="list-group-item border-0 d-flex align-items-center gap-2" style="padding:0.75rem 0;">
                    <i class="fas fa-user-tie text-muted" style="font-size:1.5rem;"></i>
                    <div>
                      <div style="font-weight:500;"><?= $coachName ?></div>
                      <small class="text-muted"><?= htmlspecialchars($c['CoachID']) ?></small>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Series -->
    <div class="card">
      <div class="card-body">
        <h5 class="mb-3"><i class="fas fa-history me-2 text-muted"></i>Recent Series</h5>
        <?php if (count($series) === 0): ?>
          <p class="text-muted">No series history for this team.</p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th>Tournament</th>
                  <th>Round</th>
                  <th>Matchup</th>
                  <th>Result</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($series as $s): ?>
                  <tr>
                    <td><?= htmlspecialchars($s['TournamentName']) ?> <span class="text-muted">(<?= $s['TournamentYear'] ?>)</span></td>
                    <td><?= htmlspecialchars($s['RoundType']) ?></td>
                    <td>
                      <strong><?= htmlspecialchars($s['Team1Name']) ?></strong> vs <strong><?= htmlspecialchars($s['Team2Name']) ?></strong>
                    </td>
                    <td>
                      <?php if ($s['WinnerTeamID']): ?>
                        <?php if ($s['WinnerTeamID'] === $teamID): ?>
                          <span class="badge" style="background:var(--rp-foam);color:white;">WIN</span>
                        <?php else: ?>
                          <span class="badge" style="background:var(--rp-muted);color:white;">LOSS</span>
                        <?php endif; ?>
                      <?php else: ?>
                        <span class="text-muted">Pending</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <a href="series_view.php?id=<?= urlencode($s['SeriesID']) ?>" class="btn btn-sm btn-outline-secondary">View</a>
                    </td>
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
