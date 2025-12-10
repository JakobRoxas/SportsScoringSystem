<?php
require_once __DIR__ . '/../config.php';
include __DIR__ . '/header.php';

$team = $_GET['team'] ?? '';
$q = $_GET['q'] ?? '';

$params = [];
$sql = "SELECT s.SeriesID, s.Team1ID, s.Team2ID, s.WinnerTeamID, r.RoundType
        FROM Series s JOIN Round r ON s.RoundID = r.RoundID
        WHERE 1=1";
if ($team) { $sql .= " AND (s.Team1ID = :team OR s.Team2ID = :team)"; $params[':team']=$team; }
if ($q) { $sql .= " AND s.SeriesID = :q"; $params[':q']=$q; }
$sql .= " ORDER BY s.SeriesID LIMIT 200";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
?>

<div class="mb-4">
  <h2 class="mb-3">Series</h2>
  
  <div class="card p-3 mb-4">
    <form class="row g-3 align-items-end" method="get">
      <div class="col-md-5">
        <label class="form-label small text-muted">Series ID</label>
        <input name="q" value="<?=htmlspecialchars($q)?>" class="form-control" placeholder="Search by series ID">
      </div>
      <div class="col-md-5">
        <label class="form-label small text-muted">Team ID</label>
        <input name="team" value="<?=htmlspecialchars($team)?>" class="form-control" placeholder="Filter by team">
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
      <div class="alert alert-info">No series found. Try adjusting your search filters.</div>
    </div>
  <?php else: foreach ($rows as $r): ?>
    <div class="col-lg-6">
      <div class="card p-3">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <div class="d-flex align-items-center gap-2">
            <div style="width:40px;height:40px;background:linear-gradient(135deg,var(--rp-accent),var(--rp-accent-hover));border-radius:8px;display:flex;align-items:center;justify-content:center;">
              <i class="fas fa-layer-group" style="color:white"></i>
            </div>
            <div>
              <h6 class="mb-0"><?=htmlspecialchars($r['SeriesID'])?></h6>
              <span class="badge bg-secondary"><?=htmlspecialchars($r['RoundType'])?></span>
            </div>
          </div>
        </div>
        <div class="d-flex align-items-center justify-content-between" style="background:var(--rp-overlay);padding:0.75rem;border-radius:8px;">
          <div class="text-center flex-grow-1">
            <strong><?=htmlspecialchars($r['Team1ID'])?></strong>
          </div>
          <div class="text-muted px-2">vs</div>
          <div class="text-center flex-grow-1">
            <strong><?=htmlspecialchars($r['Team2ID'])?></strong>
          </div>
        </div>
        <?php if ($r['WinnerTeamID']): ?>
          <div class="mt-2 text-center">
            <span class="badge" style="background:linear-gradient(135deg,var(--rp-gold),#d4820f);color:white;padding:0.5rem 1rem;">
              <i class="fas fa-trophy me-1"></i> Winner: <?=htmlspecialchars($r['WinnerTeamID'])?>
            </span>
          </div>
        <?php else: ?>
          <div class="mt-2 text-center text-muted small">No winner yet</div>
        <?php endif; ?>
        <a href="series_view.php?id=<?=urlencode($r['SeriesID'])?>" class="btn btn-sm btn-outline-primary mt-2 w-100">View Details</a>
      </div>
    </div>
  <?php endforeach; endif; ?>
</div>

<?php if (count($rows) > 0): ?>
  <div class="mt-4 text-center text-muted small">
    Showing <?= count($rows) ?> series
  </div>
<?php endif; ?>

<?php include __DIR__ . '/footer.php'; ?>