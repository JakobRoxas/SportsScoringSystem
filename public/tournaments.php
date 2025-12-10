<?php
require_once __DIR__ . '/../config.php';
include __DIR__ . '/header.php';

$year = isset($_GET['year']) ? (int)$_GET['year'] : 0;
$q = isset($_GET['q']) ? trim($_GET['q']) : '';

$params = [];
$sql = "SELECT TournamentID, TournamentName, TournamentYear FROM Tournament WHERE 1=1";
if ($year) { $sql .= " AND TournamentYear = :year"; $params[':year']=$year; }
if ($q) { $sql .= " AND TournamentName LIKE :q"; $params[':q']="%$q%"; }
$sql .= " ORDER BY TournamentYear DESC, TournamentName LIMIT 200";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
?>

<div class="mb-4">
  <h2 class="mb-3">Tournaments</h2>
  
  <div class="card p-3 mb-4">
    <form class="row g-3 align-items-end" method="get">
      <div class="col-md-6">
        <label class="form-label small text-muted">Tournament Name</label>
        <input name="q" value="<?=htmlspecialchars($q)?>" class="form-control" placeholder="Search by name">
      </div>
      <div class="col-md-4">
        <label class="form-label small text-muted">Year</label>
        <input name="year" value="<?= $year?:'' ?>" class="form-control" placeholder="Filter by year" type="number">
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
      <div class="alert alert-info">No tournaments found. Try adjusting your search filters.</div>
    </div>
  <?php else: foreach ($rows as $r): ?>
    <div class="col-lg-6">
      <div class="card p-3">
        <div class="d-flex align-items-start gap-3">
          <div style="width:56px;height:56px;background:linear-gradient(135deg,var(--rp-gold),#d4820f);border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="fas fa-trophy" style="color:white;font-size:1.5rem"></i>
          </div>
          <div class="flex-grow-1">
            <h5 class="mb-1"><?=htmlspecialchars($r['TournamentName'])?></h5>
            <div class="text-muted mb-2">Year: <strong><?=htmlspecialchars($r['TournamentYear'])?></strong></div>
            <div class="d-flex gap-2">
              <span class="badge bg-secondary"><?=htmlspecialchars($r['TournamentID'])?></span>
              <a href="tournament_view.php?id=<?=urlencode($r['TournamentID'])?>" class="btn btn-sm btn-outline-primary">View Details</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  <?php endforeach; endif; ?>
</div>

<?php if (count($rows) > 0): ?>
  <div class="mt-4 text-center text-muted small">
    Showing <?= count($rows) ?> tournament(s)
  </div>
<?php endif; ?>

<?php include __DIR__ . '/footer.php'; ?>