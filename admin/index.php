<?php
require_once __DIR__ . '/../config.php';
include __DIR__ . '/../public/header.php';

// Get next available IDs
function getNextID($pdo, $table, $column, $prefix = '') {
  $stmt = $pdo->query("SELECT $column FROM $table ORDER BY $column DESC LIMIT 1");
  $last = $stmt->fetch();
  
  if (!$last) {
    return $prefix . '001';
  }
  
  // Extract numeric part
  $lastID = $last[$column];
  $numericPart = (int)preg_replace('/[^0-9]/', '', $lastID);
  $nextNum = $numericPart + 1;
  
  // Use 3 digit padding
  return $prefix . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
}

$nextIDs = [
  'team' => getNextID($pdo, 'Team', 'TeamID', 'TM'),
  'player' => getNextID($pdo, 'Player', 'PlayerID', 'PL'),
  'coach' => getNextID($pdo, 'Coach', 'CoachID', 'CO'),
  'tournament' => getNextID($pdo, 'Tournament', 'TournamentID', 'TR'),
  'round' => getNextID($pdo, 'Round', 'RoundID', 'RD'),
  'series' => getNextID($pdo, 'Series', 'SeriesID', 'SE'),
  'game' => getNextID($pdo, 'Game', 'GameID', 'GM'),
];

// Fetch existing data for autocomplete
$teams = $pdo->query("SELECT TeamID, TeamName FROM Team ORDER BY TeamName")->fetchAll();
$tournaments = $pdo->query("SELECT TournamentID, TournamentName FROM Tournament ORDER BY TournamentYear DESC")->fetchAll();
$rounds = $pdo->query("SELECT RoundID, RoundType FROM Round ORDER BY RoundID")->fetchAll();
$series = $pdo->query("SELECT SeriesID FROM Series ORDER BY SeriesID")->fetchAll();
$games = $pdo->query("SELECT GameID FROM Game ORDER BY GameID")->fetchAll();
$players = $pdo->query("SELECT PlayerID, PlayerFname, PlayerLname FROM Player ORDER BY PlayerLname")->fetchAll();
?>

  <div class="mb-4">
  <div class="d-flex justify-content-between align-items-center">
    <div>
      <h2 class="mb-1">Admin Panel</h2>
      <p class="text-muted mb-0">Manage teams, players, tournaments, and game data</p>
    </div>
    <a class="btn btn-outline-secondary" href="/sports/public/dashboard.php">
      <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
    </a>
  </div>
</div>

<!-- Manage Existing Data Section -->
<div class="card mb-4">
  <div class="card-header">
    <h5 class="mb-0"><i class="fas fa-database me-2"></i>Manage Existing Data</h5>
  </div>
  <div class="card-body">
    <div class="row g-3">
      <div class="col-md-3">
        <a href="/sports/admin/manage.php?type=team" class="btn btn-outline-primary w-100">
          <i class="fas fa-shield-alt me-2"></i>Teams
        </a>
      </div>
      <div class="col-md-3">
        <a href="/sports/admin/manage.php?type=player" class="btn btn-outline-primary w-100">
          <i class="fas fa-user me-2"></i>Players
        </a>
      </div>
      <div class="col-md-3">
        <a href="/sports/admin/manage.php?type=coach" class="btn btn-outline-primary w-100">
          <i class="fas fa-user-tie me-2"></i>Coaches
        </a>
      </div>
      <div class="col-md-3">
        <a href="/sports/admin/manage.php?type=tournament" class="btn btn-outline-primary w-100">
          <i class="fas fa-trophy me-2"></i>Tournaments
        </a>
      </div>
      <div class="col-md-3">
        <a href="/sports/admin/manage.php?type=round" class="btn btn-outline-primary w-100">
          <i class="fas fa-layer-group me-2"></i>Rounds
        </a>
      </div>
      <div class="col-md-3">
        <a href="/sports/admin/manage.php?type=series" class="btn btn-outline-primary w-100">
          <i class="fas fa-sitemap me-2"></i>Series
        </a>
      </div>
      <div class="col-md-3">
        <a href="/sports/admin/manage.php?type=game" class="btn btn-outline-primary w-100">
          <i class="fas fa-gamepad me-2"></i>Games
        </a>
      </div>
      <div class="col-md-3">
        <a href="/sports/admin/manage.php?type=score" class="btn btn-outline-primary w-100">
          <i class="fas fa-hashtag me-2"></i>Scores
        </a>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  <!-- COLUMN 1: Teams & Players -->
  <div class="col-lg-4 col-md-6">
    <div class="card p-4 mb-3">
      <h5 class="mb-3"><i class="fas fa-users me-2 text-muted"></i>Add Team</h5>
      <form method="post" action="actions.php" class="row g-3">
        <input type="hidden" name="action" value="add_team">
        <div class="col-12">
          <label class="form-label small text-muted">Team ID</label>
          <input name="TeamID" id="teamID" class="form-control" placeholder="TM001" onfocus="if(!this.value) this.value='<?= htmlspecialchars($nextIDs['team']) ?>'" required>
        </div>
        <div class="col-12">
          <label class="form-label small text-muted">Team Name</label>
          <input name="TeamName" class="form-control" placeholder="Team name" required>
        </div>
        <div class="col-12">
          <label class="form-label small text-muted">City</label>
          <input name="TeamCity" class="form-control" placeholder="City" required>
        </div>
        <div class="col-12">
          <label class="form-label small text-muted">Conference</label>
          <select name="TeamConf" class="form-select" required>
            <option value="">Select conference...</option>
            <option value="WEST">WEST</option>
            <option value="EAST">EAST</option>
          </select>
        </div>
        <div class="col-12"><button class="btn btn-primary w-100"><i class="fas fa-plus me-2"></i>Add Team</button></div>
      </form>
    </div>

    <div class="card p-4 mb-3">
      <h5 class="mb-3"><i class="fas fa-user me-2 text-muted"></i>Add Player</h5>
      <form method="post" action="actions.php" class="row g-3">
        <input type="hidden" name="action" value="add_player">
        <div class="col-12">
          <label class="form-label small text-muted">Player ID</label>
          <input name="PlayerID" id="playerID" class="form-control" placeholder="PL001" onfocus="if(!this.value) this.value='<?= htmlspecialchars($nextIDs['player']) ?>'" required>
        </div>
        <div class="col-6">
          <label class="form-label small text-muted">First Name</label>
          <input name="PlayerFname" class="form-control" placeholder="First name" required>
        </div>
        <div class="col-6">
          <label class="form-label small text-muted">Last Name</label>
          <input name="PlayerLname" class="form-control" placeholder="Last name" required>
        </div>
        <div class="col-12">
          <label class="form-label small text-muted">Team ID</label>
          <input name="TeamID" class="form-control" placeholder="TM001" list="teamList" required>
        </div>
        <div class="col-12"><button class="btn btn-primary w-100"><i class="fas fa-plus me-2"></i>Add Player</button></div>
      </form>
    </div>

    <div class="card p-4 mb-3">
      <h5 class="mb-3"><i class="fas fa-chalkboard-teacher me-2 text-muted"></i>Add Coach</h5>
      <form method="post" action="actions.php" class="row g-3">
        <input type="hidden" name="action" value="add_coach">
        <div class="col-12">
          <label class="form-label small text-muted">Coach ID</label>
          <input name="CoachID" id="coachID" class="form-control" placeholder="CO001" onfocus="if(!this.value) this.value='<?= htmlspecialchars($nextIDs['coach']) ?>'" required>
        </div>
        <div class="col-6">
          <label class="form-label small text-muted">First Name</label>
          <input name="CoachFname" class="form-control" placeholder="First name" required>
        </div>
        <div class="col-6">
          <label class="form-label small text-muted">Last Name</label>
          <input name="CoachLname" class="form-control" placeholder="Last name" required>
        </div>
        <div class="col-12">
          <label class="form-label small text-muted">Team ID</label>
          <input name="TeamID" class="form-control" placeholder="TM001" list="teamList" required>
        </div>
        <div class="col-12"><button class="btn btn-primary w-100"><i class="fas fa-plus me-2"></i>Add Coach</button></div>
      </form>
    </div>
  </div>

  <!-- COLUMN 2: Tournaments & Rounds -->
  <div class="col-lg-4 col-md-6">
    <div class="card p-4 mb-3">
      <h5 class="mb-3"><i class="fas fa-trophy me-2 text-muted"></i>Add Tournament</h5>
      <form method="post" action="actions.php" class="row g-3">
        <input type="hidden" name="action" value="add_tournament">
        <div class="col-12">
          <label class="form-label small text-muted">Tournament ID</label>
          <input name="TournamentID" id="tournamentID" class="form-control" placeholder="TR001" onfocus="if(!this.value) this.value='<?= htmlspecialchars($nextIDs['tournament']) ?>'" required>
        </div>
        <div class="col-12">
          <label class="form-label small text-muted">Tournament Name</label>
          <input name="TournamentName" class="form-control" placeholder="Tournament Name" required>
        </div>
        <div class="col-12">
          <label class="form-label small text-muted">Year</label>
          <input name="TournamentYear" type="number" class="form-control" placeholder="2025" required>
        </div>
        <div class="col-12"><button class="btn btn-primary w-100"><i class="fas fa-plus me-2"></i>Add Tournament</button></div>
      </form>
    </div>

    <div class="card p-4 mb-3">
      <h5 class="mb-3"><i class="fas fa-clipboard-list me-2 text-muted"></i>Add Tournament Team</h5>
      <form method="post" action="actions.php" class="row g-3">
        <input type="hidden" name="action" value="add_tournament_team">
        <div class="col-6">
          <label class="form-label small text-muted">Tournament ID</label>
          <input name="TournamentID" class="form-control" placeholder="TR001" list="tournamentList" required>
        </div>
        <div class="col-6">
          <label class="form-label small text-muted">Team ID</label>
          <input name="TeamID" class="form-control" placeholder="TM001" list="teamList" required>
        </div>
        <div class="col-12">
          <label class="form-label small text-muted">Seed</label>
          <input name="Seed" type="number" class="form-control" placeholder="Seed (1-16)" required>
        </div>
        <div class="col-12"><button class="btn btn-primary w-100"><i class="fas fa-plus me-2"></i>Add to Tournament</button></div>
      </form>
    </div>

    <div class="card p-4 mb-3">
      <h5 class="mb-3"><i class="fas fa-circle-notch me-2 text-muted"></i>Add Round</h5>
      <form method="post" action="actions.php" class="row g-3">
        <input type="hidden" name="action" value="add_round">
        <div class="col-12">
          <label class="form-label small text-muted">Round ID</label>
          <input name="RoundID" id="roundID" class="form-control" placeholder="RD001" onfocus="if(!this.value) this.value='<?= htmlspecialchars($nextIDs['round']) ?>'" required>
        </div>
        <div class="col-12">
          <label class="form-label small text-muted">Round Type</label>
          <select name="RoundType" class="form-select" required>
            <option value="">Select type...</option>
            <option value="PRELIMINARIES">PRELIMINARIES</option>
            <option value="SEMI-FINALS">SEMI-FINALS</option>
            <option value="FINALS">FINALS</option>
          </select>
        </div>
        <div class="col-6">
          <label class="form-label small text-muted">Round Number</label>
          <input name="RoundNumber" type="number" class="form-control" placeholder="1" required>
        </div>
        <div class="col-6">
          <label class="form-label small text-muted">Tournament ID</label>
          <input name="TournamentID" class="form-control" placeholder="TR001" list="tournamentList" required>
        </div>
        <div class="col-12"><button class="btn btn-primary w-100"><i class="fas fa-plus me-2"></i>Add Round</button></div>
      </form>
    </div>

    <div class="card p-4 mb-3">
      <h5 class="mb-3"><i class="fas fa-layer-group me-2 text-muted"></i>Add Series</h5>
      <form method="post" action="actions.php" class="row g-3">
        <input type="hidden" name="action" value="add_series">
        <div class="col-12">
          <label class="form-label small text-muted">Series ID</label>
          <input name="SeriesID" id="seriesID" class="form-control" placeholder="SE001" onfocus="if(!this.value) this.value='<?= htmlspecialchars($nextIDs['series']) ?>'" required>
        </div>
        <div class="col-12">
          <label class="form-label small text-muted">Round ID</label>
          <input name="RoundID" class="form-control" placeholder="RD001" list="roundList" required>
        </div>
        <div class="col-6">
          <label class="form-label small text-muted">Team 1 ID</label>
          <input name="Team1ID" class="form-control" placeholder="TM001" list="teamList" required>
        </div>
        <div class="col-6">
          <label class="form-label small text-muted">Team 2 ID</label>
          <input name="Team2ID" class="form-control" placeholder="TM002" list="teamList" required>
        </div>
        <div class="col-12"><button class="btn btn-primary w-100"><i class="fas fa-plus me-2"></i>Add Series</button></div>
      </form>
    </div>
  </div>

  <!-- COLUMN 3: Games & Scores -->
  <div class="col-lg-4 col-md-12">
    <div class="card p-4 mb-3">
      <h5 class="mb-3"><i class="fas fa-gamepad me-2 text-muted"></i>Add Game</h5>
      <form method="post" action="actions.php" class="row g-3">
        <input type="hidden" name="action" value="add_game">
        <div class="col-6">
          <label class="form-label small text-muted">Game ID</label>
          <input name="GameID" id="gameID" class="form-control" placeholder="GM001" onfocus="if(!this.value) this.value='<?= htmlspecialchars($nextIDs['game']) ?>'" required>
        </div>
        <div class="col-6">
          <label class="form-label small text-muted">Series ID</label>
          <input name="SeriesID" class="form-control" placeholder="SE001" list="seriesList" required>
        </div>
        <div class="col-4">
          <label class="form-label small text-muted">Game #</label>
          <input name="GameNumber" type="number" class="form-control" placeholder="1-7" min="1" max="7" required>
        </div>
        <div class="col-8">
          <label class="form-label small text-muted">Date</label>
          <input name="GameDate" type="date" class="form-control" required>
        </div>
        <div class="col-12">
          <label class="form-label small text-muted">Venue</label>
          <input name="Venue" class="form-control" placeholder="Arena/Venue" required>
        </div>
        <div class="col-12"><button class="btn btn-primary w-100"><i class="fas fa-plus me-2"></i>Add Game</button></div>
      </form>
    </div>

    <div class="card p-4 mb-3">
      <h5 class="mb-3"><i class="fas fa-calculator me-2 text-muted"></i>Add/Update Score</h5>
      <form method="post" action="actions.php" class="row g-3">
        <input type="hidden" name="action" value="add_score">
        <div class="col-6">
          <label class="form-label small text-muted">Game ID</label>
          <input name="GameID" class="form-control" placeholder="GM001" list="gameList" required>
        </div>
        <div class="col-6">
          <label class="form-label small text-muted">Team ID</label>
          <input name="TeamID" class="form-control" placeholder="TM001" list="teamList" required>
        </div>
        <div class="col-12">
          <label class="form-label small text-muted">Points Scored</label>
          <input name="PointsScored" type="number" class="form-control" placeholder="Points" min="0" required>
        </div>
        <div class="col-12"><button class="btn btn-primary w-100"><i class="fas fa-save me-2"></i>Save Score</button></div>
      </form>
    </div>

    <div class="card p-4 mb-3">
      <h5 class="mb-3"><i class="fas fa-chart-bar me-2 text-muted"></i>Add Player Stats</h5>
      <form method="post" action="actions.php" class="row g-3">
        <input type="hidden" name="action" value="add_player_stat">
        <div class="col-6">
          <label class="form-label small text-muted">Game ID</label>
          <input name="GameID" class="form-control" placeholder="GM001" list="gameList" required>
        </div>
        <div class="col-6">
          <label class="form-label small text-muted">Player ID</label>
          <input name="PlayerID" class="form-control" placeholder="PL001" list="playerList" required>
        </div>
        <div class="col-4">
          <label class="form-label small text-muted">Points</label>
          <input name="Points" type="number" class="form-control" placeholder="Pts" min="0" required>
        </div>
        <div class="col-4">
          <label class="form-label small text-muted">Rebounds</label>
          <input name="Rebounds" type="number" class="form-control" placeholder="Reb" min="0" required>
        </div>
        <div class="col-4">
          <label class="form-label small text-muted">Assists</label>
          <input name="Assists" type="number" class="form-control" placeholder="Ast" min="0" required>
        </div>
        <div class="col-12"><button class="btn btn-primary w-100"><i class="fas fa-plus me-2"></i>Add Stats</button></div>
      </form>
    </div>
  </div>
</div>

<!-- Datalists for autocomplete -->
<datalist id="teamList">
  <?php foreach ($teams as $t): ?>
    <option value="<?= htmlspecialchars($t['TeamID']) ?>"><?= htmlspecialchars($t['TeamName']) ?></option>
  <?php endforeach; ?>
</datalist>

<datalist id="tournamentList">
  <?php foreach ($tournaments as $t): ?>
    <option value="<?= htmlspecialchars($t['TournamentID']) ?>"><?= htmlspecialchars($t['TournamentName']) ?></option>
  <?php endforeach; ?>
</datalist>

<datalist id="roundList">
  <?php foreach ($rounds as $r): ?>
    <option value="<?= htmlspecialchars($r['RoundID']) ?>"><?= htmlspecialchars($r['RoundType']) ?></option>
  <?php endforeach; ?>
</datalist>

<datalist id="seriesList">
  <?php foreach ($series as $s): ?>
    <option value="<?= htmlspecialchars($s['SeriesID']) ?>"></option>
  <?php endforeach; ?>
</datalist>

<datalist id="gameList">
  <?php foreach ($games as $g): ?>
    <option value="<?= htmlspecialchars($g['GameID']) ?>"></option>
  <?php endforeach; ?>
</datalist>

<datalist id="playerList">
  <?php foreach ($players as $p): ?>
    <option value="<?= htmlspecialchars($p['PlayerID']) ?>"><?= htmlspecialchars($p['PlayerFname'] . ' ' . $p['PlayerLname']) ?></option>
  <?php endforeach; ?>
</datalist>

<?php include __DIR__ . '/../public/footer.php'; ?>
