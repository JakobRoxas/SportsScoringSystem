<?php
require_once __DIR__ . '/../config.php';
include __DIR__ . '/header.php';

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$conf = isset($_GET['conf']) ? trim($_GET['conf']) : '';
$order = isset($_GET['order']) ? ($_GET['order'] === 'city' ? 'TeamCity' : 'TeamName') : 'TeamName';

$params = [];
$sql = "SELECT TeamID, TeamName, TeamCity, TeamConf,
        (SELECT COUNT(*) FROM Player WHERE Player.TeamID = Team.TeamID) as PlayerCount
        FROM Team
        WHERE 1=1";

if ($q !== '') {
    $sql .= " AND (TeamName LIKE :q OR TeamCity LIKE :q OR TeamID LIKE :q)";
    $params[':q'] = "%$q%";
}
if ($conf !== '' && in_array($conf, ['EAST', 'WEST'])) {
    $sql .= " AND TeamConf = :conf";
    $params[':conf'] = $conf;
}

$sql .= " ORDER BY $order LIMIT 200";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

?>
<div class="mb-4">
  <h2 class="mb-3">Teams</h2>
  
  <div class="card p-3 mb-4">
    <form class="row g-3 align-items-end" method="get">
      <div class="col-md-4">
        <label class="form-label small text-muted">Search</label>
        <input name="q" value="<?=htmlspecialchars($q)?>" placeholder="Team name, city, or ID" class="form-control">
      </div>
      <div class="col-md-3">
        <label class="form-label small text-muted">Conference</label>
        <select name="conf" class="form-select">
          <option value="">All Conferences</option>
          <option value="EAST" <?= $conf==='EAST' ? 'selected':'' ?>>EAST</option>
          <option value="WEST" <?= $conf==='WEST' ? 'selected':'' ?>>WEST</option>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label small text-muted">Sort by</label>
        <select name="order" class="form-select">
          <option value="name" <?= $order==='TeamName' ? 'selected':'' ?>>Team name</option>
          <option value="city" <?= $order==='TeamCity' ? 'selected':'' ?>>City</option>
        </select>
      </div>
      <div class="col-md-2">
        <button class="btn btn-primary w-100">Filter</button>
      </div>
    </form>
  </div>
</div>

<div class="row g-3">
  <?php if (!$rows): ?>
    <div class="col-12">
      <div class="alert alert-info">No teams found. Try adjusting your search filters.</div>
    </div>
  <?php else: foreach ($rows as $r): 
    $teamName = htmlspecialchars($r['TeamName']);
    $teamCity = htmlspecialchars($r['TeamCity']);
    $avatarUrl = 'https://ui-avatars.com/api/?name=' . urlencode($r['TeamName']) . '&size=96&background=3e8fb0&color=ffffff&bold=true';
  ?>
    <div class="col-lg-4 col-md-6">
      <div class="card p-3">
        <div class="d-flex align-items-start gap-3">
          <img src="<?= $avatarUrl ?>" alt="<?= $teamName ?>" style="width:56px;height:56px;border-radius:12px;flex-shrink:0;" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
          <div style="width:56px;height:56px;background:linear-gradient(135deg,var(--rp-pine),var(--rp-foam));border-radius:12px;display:none;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="fas fa-shield-alt" style="color:white;font-size:1.5rem"></i>
          </div>
          <div class="flex-grow-1">
            <h6 class="mb-1"><?= $teamName ?></h6>
            <div class="text-muted small mb-1">
              <i class="fas fa-map-marker-alt me-1"></i><?= $teamCity ?>
            </div>
            <div class="mb-2">
              <span class="badge" style="background:var(--rp-<?= strtolower($r['TeamConf']) === 'east' ? 'love' : 'gold' ?>);color:white;">
                <?= htmlspecialchars($r['TeamConf']) ?>
              </span>
              <span class="badge bg-secondary ms-1">
                <?= $r['PlayerCount'] ?> Players
              </span>
            </div>
            <div class="text-muted small mb-2">ID: <?=htmlspecialchars($r['TeamID'])?></div>
            <a href="team_view.php?id=<?=urlencode($r['TeamID'])?>" class="btn btn-sm btn-outline-primary">View Details</a>
          </div>
        </div>
      </div>
    </div>
  <?php endforeach; endif; ?>
</div>

<?php if (count($rows) > 0): ?>
  <div class="mt-4 text-center text-muted small">
    Showing <?= count($rows) ?> team(s)
  </div>
<?php endif; ?>

<?php include __DIR__ . '/footer.php'; ?>
