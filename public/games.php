<?php
require_once __DIR__ . '/../config.php';
include __DIR__ . '/header.php';

$series = $_GET['series'] ?? '';
$date_from = $_GET['from'] ?? '';
$date_to = $_GET['to'] ?? '';
$venue = $_GET['venue'] ?? '';

$params = [];
$sql = "SELECT g.GameID, g.SeriesID, g.GameNumber, g.GameDate, g.Venue, g.WinnerTeamID
        FROM Game g WHERE 1=1";
if ($series) { $sql .= " AND g.SeriesID = :series"; $params[':series']=$series; }
if ($date_from) { $sql .= " AND g.GameDate >= :from"; $params[':from']=$date_from; }
if ($date_to) { $sql .= " AND g.GameDate <= :to"; $params[':to']=$date_to; }
if ($venue) { $sql .= " AND g.Venue LIKE :venue"; $params[':venue']="%$venue%"; }
$sql .= " ORDER BY g.GameDate DESC LIMIT 200";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
?>

<div class="mb-4">
  <h2 class="mb-3">Games</h2>
  
  <div class="card p-3 mb-4">
    <form class="row g-3 align-items-end" method="get">
      <div class="col-md-3">
        <label class="form-label small text-muted">Series ID</label>
        <input name="series" value="<?=htmlspecialchars($series)?>" class="form-control" placeholder="Filter by series">
      </div>
      <div class="col-md-2">
        <label class="form-label small text-muted">From Date</label>
        <input name="from" value="<?=htmlspecialchars($date_from)?>" type="date" class="form-control">
      </div>
      <div class="col-md-2">
        <label class="form-label small text-muted">To Date</label>
        <input name="to" value="<?=htmlspecialchars($date_to)?>" type="date" class="form-control">
      </div>
      <div class="col-md-3">
        <label class="form-label small text-muted">Venue</label>
        <input name="venue" value="<?=htmlspecialchars($venue)?>" class="form-control" placeholder="Search venue">
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
      <div class="alert alert-info">No games found. Try adjusting your search filters.</div>
    </div>
  <?php else: foreach ($rows as $r): ?>
    <div class="col-lg-6">
      <div class="card p-3">
        <div class="d-flex align-items-start gap-3">
          <div style="width:48px;height:48px;background:linear-gradient(135deg,var(--rp-accent),var(--rp-accent-hover));border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="fas fa-gamepad" style="color:white;font-size:1.25rem"></i>
          </div>
          <div class="flex-grow-1">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <div>
                <h6 class="mb-1"><?=htmlspecialchars($r['GameID'])?></h6>
                <span class="badge bg-secondary">Game #<?=htmlspecialchars($r['GameNumber'])?></span>
              </div>
              <?php if ($r['WinnerTeamID']): ?>
                <span class="badge" style="background:linear-gradient(135deg,var(--rp-gold),#d4820f);color:white;">
                  <i class="fas fa-trophy"></i> <?=htmlspecialchars($r['WinnerTeamID'])?>
                </span>
              <?php endif; ?>
            </div>
            <div style="background:var(--rp-overlay);padding:0.75rem;border-radius:8px;margin-bottom:0.75rem;">
              <div class="d-flex justify-content-between text-muted small mb-1">
                <span><i class="fas fa-calendar me-1"></i> <?=htmlspecialchars($r['GameDate'])?></span>
                <span><i class="fas fa-map-marker-alt me-1"></i> <?=htmlspecialchars($r['Venue'])?></span>
              </div>
              <div class="text-muted small">
                <i class="fas fa-layer-group me-1"></i> Series: <?=htmlspecialchars($r['SeriesID'])?>
              </div>
            </div>
            <a href="game_view.php?id=<?=urlencode($r['GameID'])?>" class="btn btn-sm btn-outline-primary w-100">View Details</a>
          </div>
        </div>
      </div>
    </div>
  <?php endforeach; endif; ?>
</div>

<?php if (count($rows) > 0): ?>
  <div class="mt-4 text-center text-muted small">
    Showing <?= count($rows) ?> game(s)
  </div>
<?php endif; ?>

<?php include __DIR__ . '/footer.php'; ?>