<?php
// List and manage existing records
require_once __DIR__ . '/../config.php';
include __DIR__ . '/../public/header.php';

$type = $_GET['type'] ?? 'team';
$validTypes = ['team', 'player', 'coach', 'tournament', 'round', 'series', 'game', 'score'];

if (!in_array($type, $validTypes)) {
  $type = 'team';
}

$title = match($type) {
  'team' => 'Teams',
  'player' => 'Players',
  'coach' => 'Coaches',
  'tournament' => 'Tournaments',
  'round' => 'Rounds',
  'series' => 'Series',
  'game' => 'Games',
  'score' => 'Scores',
  default => ucfirst($type) . 's'
};
$data = [];

// Fetch data based on type
switch ($type) {
  case 'team':
    $data = $pdo->query("SELECT * FROM Team ORDER BY TeamName")->fetchAll();
    $columns = ['TeamID', 'TeamName', 'TeamCity', 'TeamConf'];
    break;
    
  case 'player':
    $data = $pdo->query("SELECT p.*, t.TeamName FROM Player p JOIN Team t ON p.TeamID = t.TeamID ORDER BY p.PlayerLname, p.PlayerFname")->fetchAll();
    $columns = ['PlayerID', 'PlayerFname', 'PlayerLname', 'TeamName'];
    break;
    
  case 'coach':
    $data = $pdo->query("SELECT c.*, t.TeamName FROM Coach c JOIN Team t ON c.TeamID = t.TeamID ORDER BY c.CoachLname, c.CoachFname")->fetchAll();
    $columns = ['CoachID', 'CoachFname', 'CoachLname', 'TeamName'];
    break;
    
  case 'tournament':
    $data = $pdo->query("SELECT * FROM Tournament ORDER BY TournamentYear DESC, TournamentName")->fetchAll();
    $columns = ['TournamentID', 'TournamentName', 'TournamentYear'];
    break;
    
  case 'round':
    $data = $pdo->query("SELECT r.*, t.TournamentName FROM Round r JOIN Tournament t ON r.TournamentID = t.TournamentID ORDER BY r.RoundID")->fetchAll();
    $columns = ['RoundID', 'RoundType', 'RoundNumber', 'TournamentName'];
    break;
    
  case 'series':
    $data = $pdo->query("
      SELECT s.*, 
             t1.TeamName as Team1Name, 
             t2.TeamName as Team2Name,
             tw.TeamName as WinnerName,
             r.RoundType
      FROM Series s
      JOIN Team t1 ON s.Team1ID = t1.TeamID
      JOIN Team t2 ON s.Team2ID = t2.TeamID
      LEFT JOIN Team tw ON s.WinnerTeamID = tw.TeamID
      JOIN Round r ON s.RoundID = r.RoundID
      ORDER BY s.SeriesID
    ")->fetchAll();
    $columns = ['SeriesID', 'Team1Name', 'Team2Name', 'WinnerName', 'RoundType'];
    break;
    
  case 'game':
    $data = $pdo->query("
      SELECT g.*, 
             s.SeriesID,
             t1.TeamName as Team1Name, 
             t2.TeamName as Team2Name,
             tw.TeamName as WinnerName
      FROM Game g
      JOIN Series s ON g.SeriesID = s.SeriesID
      JOIN Team t1 ON s.Team1ID = t1.TeamID
      JOIN Team t2 ON s.Team2ID = t2.TeamID
      LEFT JOIN Team tw ON g.WinnerTeamID = tw.TeamID
      ORDER BY g.GameDate DESC, g.GameID
    ")->fetchAll();
    $columns = ['GameID', 'GameDate', 'Team1Name', 'Team2Name', 'WinnerName', 'Venue'];
    break;
    
  case 'score':
    $data = $pdo->query("
      SELECT sc.*, t.TeamName, g.GameDate
      FROM Score sc
      JOIN Team t ON sc.TeamID = t.TeamID
      JOIN Game g ON sc.GameID = g.GameID
      ORDER BY g.GameDate DESC, sc.GameID, t.TeamName
    ")->fetchAll();
    $columns = ['GameID', 'TeamName', 'PointsScored', 'GameDate'];
    break;
}
?>

  <div class="content-wrapper">
    <div class="mb-3">
      <a href="/sports/admin/index.php" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left me-2"></i>Back to Admin
      </a>
    </div>

    <div class="card">
      <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
          <h4 class="mb-0">
            <i class="fas fa-list me-2"></i>
            Manage <?= htmlspecialchars($title) ?>
          </h4>
          <span class="badge bg-primary"><?= count($data) ?> records</span>
        </div>
      </div>
      <div class="card-body">
        <?php if (empty($data)): ?>
          <p class="text-muted mb-0">No <?= htmlspecialchars(strtolower($title)) ?> found. Add some using the forms on the admin page.</p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <?php foreach ($columns as $col): ?>
                    <th><?= htmlspecialchars($col) ?></th>
                  <?php endforeach; ?>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($data as $row): ?>
                  <tr>
                    <?php foreach ($columns as $col): ?>
                      <td><?= htmlspecialchars($row[$col] ?? '-') ?></td>
                    <?php endforeach; ?>
                    <td class="text-end">
                      <?php
                      // Determine ID field based on type
                      $idField = match($type) {
                        'team' => 'TeamID',
                        'player' => 'PlayerID',
                        'coach' => 'CoachID',
                        'tournament' => 'TournamentID',
                        'round' => 'RoundID',
                        'series' => 'SeriesID',
                        'game' => 'GameID',
                        'score' => 'GameID',
                        default => 'ID'
                      };
                      
                      $editUrl = "/sports/admin/edit.php?type=$type&id=" . urlencode($row[$idField]);
                      
                      if ($type === 'score') {
                        $editUrl .= "&gameID=" . urlencode($row['GameID']) . "&teamID=" . urlencode($row['TeamID']);
                      }
                      ?>
                      
                      <?php if ($type === 'series'): ?>
                        <!-- Set Winner Form for Series -->
                        <form method="POST" action="actions.php" style="display: inline-block; margin-right: 5px;">
                          <input type="hidden" name="action" value="set_winner">
                          <input type="hidden" name="SeriesID" value="<?= htmlspecialchars($row['SeriesID']) ?>">
                          <select name="WinnerTeamID" class="form-select form-select-sm d-inline-block" style="width: auto; max-width: 150px;" onchange="this.form.submit()">
                            <option value="">Set Winner...</option>
                            <option value="<?= htmlspecialchars($row['Team1ID']) ?>" <?= ($row['WinnerTeamID'] ?? '') === $row['Team1ID'] ? 'selected' : '' ?>>
                              <?= htmlspecialchars($row['Team1Name']) ?>
                            </option>
                            <option value="<?= htmlspecialchars($row['Team2ID']) ?>" <?= ($row['WinnerTeamID'] ?? '') === $row['Team2ID'] ? 'selected' : '' ?>>
                              <?= htmlspecialchars($row['Team2Name']) ?>
                            </option>
                          </select>
                        </form>
                      <?php endif; ?>
                      
                      <a href="<?= $editUrl ?>" class="btn btn-sm btn-outline-primary me-1">
                        <i class="fas fa-edit"></i>
                      </a>
                      <form method="POST" action="actions.php" style="display: inline;" 
                            onsubmit="return confirm('Are you sure you want to delete this record?');">
                        <input type="hidden" name="action" value="delete_<?= htmlspecialchars($type) ?>">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($row[$idField]) ?>">
                        <?php if ($type === 'score'): ?>
                          <input type="hidden" name="gameID" value="<?= htmlspecialchars($row['GameID']) ?>">
                          <input type="hidden" name="teamID" value="<?= htmlspecialchars($row['TeamID']) ?>">
                        <?php endif; ?>
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                          <i class="fas fa-trash"></i>
                        </button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
    
    <!-- Quick Navigation -->
    <div class="card mt-4">
      <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Switch Data Type</h5>
      </div>
      <div class="card-body">
        <div class="btn-group flex-wrap" role="group">
          <?php foreach ($validTypes as $t): 
            $label = match($t) {
              'team' => 'Teams',
              'player' => 'Players',
              'coach' => 'Coaches',
              'tournament' => 'Tournaments',
              'round' => 'Rounds',
              'series' => 'Series',
              'game' => 'Games',
              'score' => 'Scores',
              default => ucfirst($t) . 's'
            };
          ?>
            <a href="?type=<?= htmlspecialchars($t) ?>" 
               class="btn btn-<?= $t === $type ? 'primary' : 'outline-primary' ?>">
              <?= $label ?>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

<?php include __DIR__ . '/../public/footer.php'; ?>
