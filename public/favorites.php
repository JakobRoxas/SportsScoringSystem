<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
  header('Location: /sports/login.php');
  exit;
}

require_once __DIR__ . '/../config.php';

$usersFile = __DIR__ . '/../data/users.json';

if (!file_exists($usersFile)) {
  echo '<div class="alert alert-danger m-4">User data file not found.</div>';
  exit;
}

$users = json_decode(file_get_contents($usersFile), true);
$currentUser = null;
$userIndex = null;

foreach ($users as $index => &$user) {
  if ($user['id'] == $_SESSION['user_id']) {
    $currentUser = &$user;
    $userIndex = $index;
    break;
  }
}

if (!$currentUser) {
  header('Location: /sports/logout.php');
  exit;
}

$favoritePlayerIDs = $currentUser['favorites'] ?? [];
$favoritePlayers = [];

if (!empty($favoritePlayerIDs)) {
  $placeholders = str_repeat('?,', count($favoritePlayerIDs) - 1) . '?';
  $stmt = $pdo->prepare("
    SELECT p.*, t.TeamName, t.TeamCity, t.TeamConf
    FROM Player p
    JOIN Team t ON p.TeamID = t.TeamID
    WHERE p.PlayerID IN ($placeholders)
  ");
  $stmt->execute($favoritePlayerIDs);
  $favoritePlayers = $stmt->fetchAll();
}

// Get all players for adding favorites
$allPlayersStmt = $pdo->query("
  SELECT p.*, t.TeamName
  FROM Player p
  JOIN Team t ON p.TeamID = t.TeamID
  ORDER BY p.PlayerLname, p.PlayerFname
");
$allPlayers = $allPlayersStmt->fetchAll();

include __DIR__ . '/header.php';
?>

<div class="content-wrapper">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-star me-2"></i>My Favorites</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFavoriteModal">
      <i class="fas fa-plus me-2"></i>Add Favorite Player
    </button>
  </div>

  <?php if (isset($_SESSION['flash'])): ?>
    <div class="alert alert-<?= $_SESSION['flash']['level'] ?> alert-dismissible fade show">
      <?= htmlspecialchars($_SESSION['flash']['msg']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['flash']); ?>
  <?php endif; ?>

  <?php if (empty($favoritePlayers)): ?>
    <div class="card">
      <div class="card-body text-center py-5">
        <i class="fas fa-star fa-3x text-muted mb-3"></i>
        <h4 class="text-muted">No Favorite Players Yet</h4>
        <p class="text-muted">Add your favorite players to keep track of them!</p>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFavoriteModal">
          <i class="fas fa-plus me-2"></i>Add Your First Favorite
        </button>
      </div>
    </div>
  <?php else: ?>
    <div class="row">
      <?php foreach ($favoritePlayers as $player): 
        $playerName = htmlspecialchars($player['PlayerFname'] . ' ' . $player['PlayerLname']);
        $avatarUrl = 'https://ui-avatars.com/api/?name=' . urlencode($player['PlayerFname'] . '+' . $player['PlayerLname']) . '&size=200&background=eb6f92&color=ffffff&bold=true&font-size=0.4';
      ?>
        <div class="col-md-6 col-lg-4 mb-4">
          <div class="card h-100">
            <div class="card-body">
              <div class="d-flex align-items-center mb-3">
                <img src="<?= $avatarUrl ?>" alt="<?= $playerName ?>" class="rounded-circle me-3" style="width:60px;height:60px;">
                <div class="flex-grow-1">
                  <h5 class="mb-0"><?= $playerName ?></h5>
                  <small class="text-muted"><?= htmlspecialchars($player['PlayerID']) ?></small>
                </div>
              </div>
              <p class="mb-2">
                <i class="fas fa-shield-alt me-2"></i>
                <strong><?= htmlspecialchars($player['TeamName']) ?></strong>
              </p>
              <p class="text-muted small mb-3">
                <?= htmlspecialchars($player['TeamCity']) ?> • <?= htmlspecialchars($player['TeamConf']) ?>
              </p>
              <div class="d-flex gap-2">
                <a href="/sports/public/player_view.php?id=<?= urlencode($player['PlayerID']) ?>" class="btn btn-sm btn-outline-primary flex-grow-1">
                  <i class="fas fa-eye me-1"></i>View
                </a>
                <form method="POST" action="/sports/user_actions.php" class="flex-grow-1">
                  <input type="hidden" name="action" value="remove_favorite">
                  <input type="hidden" name="player_id" value="<?= htmlspecialchars($player['PlayerID']) ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger w-100" onclick="return confirm('Remove from favorites?')">
                    <i class="fas fa-trash me-1"></i>Remove
                  </button>
                </form>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<div class="modal fade" id="addFavoriteModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content" style="background: var(--rp-bg); border: 1px solid var(--rp-border);">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-star me-2"></i>Add Favorite Player</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="/sports/user_actions.php">
        <div class="modal-body">
          <input type="hidden" name="action" value="add_favorite">
          <div class="mb-3">
            <label for="player_id" class="form-label">Select Player</label>
            <select class="form-select" id="player_id" name="player_id" required>
              <option value="">Choose a player...</option>
              <?php foreach ($allPlayers as $player): 
                if (!in_array($player['PlayerID'], $favoritePlayerIDs)):
              ?>
                <option value="<?= htmlspecialchars($player['PlayerID']) ?>">
                  <?= htmlspecialchars($player['PlayerFname'] . ' ' . $player['PlayerLname']) ?> 
                  (<?= htmlspecialchars($player['TeamName']) ?>)
                </option>
              <?php 
                endif;
              endforeach; ?>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Add Favorite</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
