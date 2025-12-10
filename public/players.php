<?php
require_once __DIR__ . '/../config.php';
include __DIR__ . '/header.php';

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$order = isset($_GET['order']) ? ($_GET['order'] === 'lname' ? 'PlayerLname' : 'PlayerFname') : 'PlayerFname';
$team = isset($_GET['team']) ? trim($_GET['team']) : '';

$params = [];
$sql = "SELECT p.PlayerID, p.PlayerFname, p.PlayerLname, t.TeamName, t.TeamCity
        FROM Player p JOIN Team t ON p.TeamID = t.TeamID
        WHERE 1=1";

if ($q !== '') {
    $sql .= " AND (p.PlayerFname LIKE :q OR p.PlayerLname LIKE :q OR p.PlayerID LIKE :q)";
    $params[':q'] = "%$q%";
}
if ($team !== '') {
    $sql .= " AND p.TeamID = :team";
    $params[':team'] = $team;
}

$sql .= " ORDER BY $order LIMIT 200";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

?>
<div class="mb-4">
  <h2 class="mb-3">Players</h2>
  
  <div class="card p-3 mb-4">
    <form class="row g-3 align-items-end" method="get">
      <div class="col-md-5">
        <label class="form-label small text-muted">Search</label>
        <input name="q" value="<?=htmlspecialchars($q)?>" placeholder="Player name or ID" class="form-control">
      </div>
      <div class="col-md-3">
        <label class="form-label small text-muted">Sort by</label>
        <select name="order" class="form-select">
          <option value="fname" <?= $order==='PlayerFname' ? 'selected':'' ?>>First name</option>
          <option value="lname" <?= $order==='PlayerLname' ? 'selected':'' ?>>Last name</option>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label small text-muted">Team ID</label>
        <input name="team" value="<?=htmlspecialchars($team)?>" placeholder="Team filter" class="form-control">
      </div>
      <div class="col-md-1">
        <button class="btn btn-primary w-100">Filter</button>
      </div>
    </form>
  </div>
</div>

<div class="row g-3">
  <?php if (!$rows): ?>
    <div class="col-12">
      <div class="alert alert-info">No players found. Try adjusting your search filters.</div>
    </div>
  <?php else: foreach ($rows as $r): 
    $playerName = htmlspecialchars($r['PlayerFname'] . ' ' . $r['PlayerLname']);
    $avatarUrl = 'https://ui-avatars.com/api/?name=' . urlencode($r['PlayerFname'] . '+' . $r['PlayerLname']) . '&size=96&background=eb6f92&color=ffffff&bold=true';
  ?>
    <div class="col-lg-4 col-md-6">
      <div class="card p-3">
        <div class="d-flex align-items-start gap-3">
          <img src="<?= $avatarUrl ?>" alt="<?= $playerName ?>" style="width:48px;height:48px;border-radius:12px;flex-shrink:0;" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
          <div style="width:48px;height:48px;background:linear-gradient(135deg,var(--rp-accent),var(--rp-accent-hover));border-radius:12px;display:none;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="fas fa-user" style="color:white;font-size:1.25rem"></i>
          </div>
          <div class="flex-grow-1">
            <h6 class="mb-1"><?= $playerName ?></h6>
            <div class="text-muted small mb-1"><?=htmlspecialchars($r['TeamName'])?></div>
            <div class="text-muted small">ID: <?=htmlspecialchars($r['PlayerID'])?></div>
            <a href="player_view.php?id=<?=urlencode($r['PlayerID'])?>" class="btn btn-sm btn-outline-primary mt-2">View Details</a>
          </div>
        </div>
      </div>
    </div>
  <?php endforeach; endif; ?>
</div>

<?php if (count($rows) > 0): ?>
  <div class="mt-4 text-center text-muted small">
    Showing <?= count($rows) ?> player(s)
  </div>
<?php endif; ?>

<?php include __DIR__ . '/footer.php'; ?>