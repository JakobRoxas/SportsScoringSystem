<?php
// admin/edit.php - Edit existing data
require_once __DIR__ . '/../config.php';
include __DIR__ . '/../public/header.php';

$type = $_GET['type'] ?? '';
$id = $_GET['id'] ?? '';

if (!$type || !$id) {
  echo '<div class="alert alert-danger m-4">Invalid parameters.</div>';
  include __DIR__ . '/../public/footer.php';
  exit;
}

$data = null;
$fields = [];

// Fetch data based on type
switch ($type) {
  case 'team':
    $stmt = $pdo->prepare("SELECT * FROM Team WHERE TeamID = ?");
    $stmt->execute([$id]);
    $data = $stmt->fetch();
    $fields = ['TeamID', 'TeamName', 'TeamCity', 'TeamConf'];
    break;
    
  case 'player':
    $stmt = $pdo->prepare("SELECT * FROM Player WHERE PlayerID = ?");
    $stmt->execute([$id]);
    $data = $stmt->fetch();
    $fields = ['PlayerID', 'PlayerFname', 'PlayerLname', 'TeamID'];
    break;
    
  case 'coach':
    $stmt = $pdo->prepare("SELECT * FROM Coach WHERE CoachID = ?");
    $stmt->execute([$id]);
    $data = $stmt->fetch();
    $fields = ['CoachID', 'CoachFname', 'CoachLname', 'TeamID'];
    break;
    
  case 'tournament':
    $stmt = $pdo->prepare("SELECT * FROM Tournament WHERE TournamentID = ?");
    $stmt->execute([$id]);
    $data = $stmt->fetch();
    $fields = ['TournamentID', 'TournamentName', 'TournamentYear'];
    break;
    
  case 'round':
    $stmt = $pdo->prepare("SELECT * FROM Round WHERE RoundID = ?");
    $stmt->execute([$id]);
    $data = $stmt->fetch();
    $fields = ['RoundID', 'RoundType', 'RoundNumber', 'TournamentID'];
    break;
    
  case 'series':
    $stmt = $pdo->prepare("SELECT * FROM Series WHERE SeriesID = ?");
    $stmt->execute([$id]);
    $data = $stmt->fetch();
    $fields = ['SeriesID', 'RoundID', 'Team1ID', 'Team2ID', 'WinnerTeamID'];
    break;
    
  case 'game':
    $stmt = $pdo->prepare("SELECT * FROM Game WHERE GameID = ?");
    $stmt->execute([$id]);
    $data = $stmt->fetch();
    $fields = ['GameID', 'SeriesID', 'GameNumber', 'GameDate', 'Venue', 'WinnerTeamID'];
    break;
    
  case 'score':
    $gameID = $_GET['gameID'] ?? '';
    $teamID = $_GET['teamID'] ?? '';
    $stmt = $pdo->prepare("SELECT * FROM Score WHERE GameID = ? AND TeamID = ?");
    $stmt->execute([$gameID, $teamID]);
    $data = $stmt->fetch();
    $fields = ['GameID', 'TeamID', 'PointsScored'];
    break;
    
  default:
    echo '<div class="alert alert-danger m-4">Unknown type.</div>';
    include __DIR__ . '/../public/footer.php';
    exit;
}

if (!$data) {
  echo '<div class="alert alert-danger m-4">Record not found.</div>';
  include __DIR__ . '/../public/footer.php';
  exit;
}

// Fetch helper data for dropdowns
$teams = $pdo->query("SELECT TeamID, TeamName FROM Team ORDER BY TeamName")->fetchAll();
$tournaments = $pdo->query("SELECT TournamentID, TournamentName FROM Tournament ORDER BY TournamentYear DESC")->fetchAll();
$rounds = $pdo->query("SELECT RoundID, RoundType, TournamentID FROM Round ORDER BY RoundID")->fetchAll();
$series = $pdo->query("SELECT SeriesID FROM Series ORDER BY SeriesID")->fetchAll();
?>

  <div class="content-wrapper">
    <div class="mb-3">
      <a href="/sports/admin/index.php" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left me-2"></i>Back to Admin
      </a>
    </div>

    <div class="card">
      <div class="card-header">
        <h4 class="mb-0">
          <i class="fas fa-edit me-2"></i>
          Edit <?= ucfirst($type) ?>: <?= htmlspecialchars($id) ?>
        </h4>
      </div>
      <div class="card-body">
        <form method="POST" action="actions.php">
          <input type="hidden" name="action" value="edit_<?= htmlspecialchars($type) ?>">
          
          <?php foreach ($fields as $field): ?>
            <div class="mb-3">
              <label class="form-label fw-bold"><?= htmlspecialchars($field) ?></label>
              
              <?php if ($field === 'TeamID' && in_array($type, ['player', 'coach'])): ?>
                <select name="<?= htmlspecialchars($field) ?>" class="form-select" required>
                  <?php foreach ($teams as $team): ?>
                    <option value="<?= htmlspecialchars($team['TeamID']) ?>" 
                            <?= $data[$field] === $team['TeamID'] ? 'selected' : '' ?>>
                      <?= htmlspecialchars($team['TeamName']) ?> (<?= htmlspecialchars($team['TeamID']) ?>)
                    </option>
                  <?php endforeach; ?>
                </select>
                
              <?php elseif ($field === 'TournamentID' && $type === 'round'): ?>
                <select name="<?= htmlspecialchars($field) ?>" class="form-select" required>
                  <?php foreach ($tournaments as $t): ?>
                    <option value="<?= htmlspecialchars($t['TournamentID']) ?>" 
                            <?= $data[$field] === $t['TournamentID'] ? 'selected' : '' ?>>
                      <?= htmlspecialchars($t['TournamentName']) ?> (<?= htmlspecialchars($t['TournamentID']) ?>)
                    </option>
                  <?php endforeach; ?>
                </select>
                
              <?php elseif ($field === 'RoundID' && $type === 'series'): ?>
                <select name="<?= htmlspecialchars($field) ?>" class="form-select" required>
                  <?php foreach ($rounds as $r): ?>
                    <option value="<?= htmlspecialchars($r['RoundID']) ?>" 
                            <?= $data[$field] === $r['RoundID'] ? 'selected' : '' ?>>
                      <?= htmlspecialchars($r['RoundID']) ?> - <?= htmlspecialchars($r['RoundType']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                
              <?php elseif ($field === 'SeriesID' && $type === 'game'): ?>
                <select name="<?= htmlspecialchars($field) ?>" class="form-select" required>
                  <?php foreach ($series as $s): ?>
                    <option value="<?= htmlspecialchars($s['SeriesID']) ?>" 
                            <?= $data[$field] === $s['SeriesID'] ? 'selected' : '' ?>>
                      <?= htmlspecialchars($s['SeriesID']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                
              <?php elseif (in_array($field, ['Team1ID', 'Team2ID', 'WinnerTeamID'])): ?>
                <select name="<?= htmlspecialchars($field) ?>" class="form-select" 
                        <?= $field !== 'WinnerTeamID' ? 'required' : '' ?>>
                  <?php if ($field === 'WinnerTeamID'): ?>
                    <option value="">-- No winner yet --</option>
                  <?php endif; ?>
                  <?php foreach ($teams as $team): ?>
                    <option value="<?= htmlspecialchars($team['TeamID']) ?>" 
                            <?= $data[$field] === $team['TeamID'] ? 'selected' : '' ?>>
                      <?= htmlspecialchars($team['TeamName']) ?> (<?= htmlspecialchars($team['TeamID']) ?>)
                    </option>
                  <?php endforeach; ?>
                </select>
                
              <?php elseif ($field === 'TeamConf'): ?>
                <select name="<?= htmlspecialchars($field) ?>" class="form-select" required>
                  <option value="EAST" <?= $data[$field] === 'EAST' ? 'selected' : '' ?>>EAST</option>
                  <option value="WEST" <?= $data[$field] === 'WEST' ? 'selected' : '' ?>>WEST</option>
                </select>
                
              <?php elseif ($field === 'RoundType'): ?>
                <select name="<?= htmlspecialchars($field) ?>" class="form-select" required>
                  <option value="PRELIMINARIES" <?= $data[$field] === 'PRELIMINARIES' ? 'selected' : '' ?>>PRELIMINARIES</option>
                  <option value="SEMI-FINALS" <?= $data[$field] === 'SEMI-FINALS' ? 'selected' : '' ?>>SEMI-FINALS</option>
                  <option value="FINALS" <?= $data[$field] === 'FINALS' ? 'selected' : '' ?>>FINALS</option>
                </select>
                
              <?php elseif ($field === 'GameDate'): ?>
                <input type="date" name="<?= htmlspecialchars($field) ?>" 
                       value="<?= htmlspecialchars($data[$field]) ?>" 
                       class="form-control" required>
                       
              <?php elseif (strpos($field, 'ID') !== false && $field === $fields[0]): ?>
                <input type="text" name="<?= htmlspecialchars($field) ?>" 
                       value="<?= htmlspecialchars($data[$field]) ?>" 
                       class="form-control" readonly>
                <small class="text-muted">ID cannot be changed</small>
                
              <?php else: ?>
                <input type="<?= in_array($field, ['TournamentYear', 'RoundNumber', 'GameNumber', 'PointsScored', 'Seed']) ? 'number' : 'text' ?>" 
                       name="<?= htmlspecialchars($field) ?>" 
                       value="<?= htmlspecialchars($data[$field] ?? '') ?>" 
                       class="form-control" 
                       <?= !in_array($field, ['WinnerTeamID']) ? 'required' : '' ?>>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
          
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-save me-2"></i>Save Changes
            </button>
            <a href="/sports/admin/index.php" class="btn btn-outline-secondary">Cancel</a>
          </div>
        </form>
      </div>
    </div>
    
    <!-- Delete Section -->
    <div class="card mt-4 border-danger">
      <div class="card-header bg-danger text-white">
        <h5 class="mb-0"><i class="fas fa-trash me-2"></i>Danger Zone</h5>
      </div>
      <div class="card-body">
        <p class="text-muted">Deleting this <?= htmlspecialchars($type) ?> cannot be undone.</p>
        <form method="POST" action="actions.php" onsubmit="return confirm('Are you sure you want to delete this <?= htmlspecialchars($type) ?>? This action cannot be undone.');">
          <input type="hidden" name="action" value="delete_<?= htmlspecialchars($type) ?>">
          <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">
          <?php if ($type === 'score'): ?>
            <input type="hidden" name="gameID" value="<?= htmlspecialchars($_GET['gameID'] ?? '') ?>">
            <input type="hidden" name="teamID" value="<?= htmlspecialchars($_GET['teamID'] ?? '') ?>">
          <?php endif; ?>
          <button type="submit" class="btn btn-danger">
            <i class="fas fa-trash me-2"></i>Delete <?= ucfirst($type) ?>
          </button>
        </form>
      </div>
    </div>
  </div>

<?php include __DIR__ . '/../public/footer.php'; ?>
