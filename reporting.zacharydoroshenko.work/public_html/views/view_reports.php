<?php

if (!is_logged_in()) die('Unauthorized');

$valid_types = ['performance', 'behavior', 'identity'];
$filter_type = isset($_GET['type']) && in_array($_GET['type'], $valid_types) ? $_GET['type'] : '';
$search      = isset($_GET['search']) ? trim($_GET['search']) : '';

// Determine which report types this user sees
$all_types = ['performance', 'behavior', 'identity'];
if (has_role('super_admin') || has_role('analyst')) {
    $viewer_allowed_types = $all_types; 
} else {
    $viewer_allowed_types = array_filter($all_types, function($t) {
        return can_access_section($t);
    });
    $viewer_allowed_types = array_values($viewer_allowed_types);
}

$sql    = "SELECT r.id, r.title, r.report_type, r.created_at,
                  u.username AS author
           FROM saved_reports r
           LEFT JOIN users u ON u.id = r.created_by
           WHERE 1=1";
$params = [];

if (!has_role('super_admin') && !has_role('analyst')) {
    if (empty($viewer_allowed_types)) {
        // No permissions at all — return nothing
        $reports = [];
        goto render;
    }
    $placeholders = implode(',', array_fill(0, count($viewer_allowed_types), '?'));
    $sql    .= " AND r.report_type IN ($placeholders)";
    $params  = array_merge($params, $viewer_allowed_types);
}

if ($filter_type !== '') { $sql .= " AND r.report_type = ?"; $params[] = $filter_type; }
if ($search !== '')      { $sql .= " AND r.title LIKE ?";   $params[] = '%'.$search.'%'; }
$sql .= " ORDER BY r.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

render:

$type_badge = [
    'performance' => 'primary',
    'behavior'    => 'success',
    'identity'    => 'warning',
];
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 class="mb-0">Saved Reports</h2>
        <p class="text-muted mb-0 mt-1"><?= count($reports) ?> report<?= count($reports)!==1?'s':'' ?> found</p>
    </div>
    <?php if (has_role('analyst') || has_role('super_admin')): ?>
        <a href="index.php?action=create_report" class="btn btn-success">
            <i class="bi bi-plus-lg me-1"></i> New Report
        </a>
    <?php endif; ?>
</div>

<div class="card mb-4 border-0 shadow-sm">
    <div class="card-body py-3">
        <form method="GET" action="index.php" class="d-flex flex-wrap gap-2 align-items-center">
            <input type="hidden" name="action" value="view_reports">
            <div class="input-group" style="max-width:300px;">
                <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                <input type="text" name="search" class="form-control border-start-0"
                       placeholder="Search reports…" value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="btn-group">
                <button type="submit" name="type" value=""
                        class="btn btn-sm <?= $filter_type==='' ? 'btn-dark' : 'btn-outline-secondary' ?>">All</button>
                <?php foreach ($viewer_allowed_types as $t): ?>
                <button type="submit" name="type" value="<?= $t ?>"
                        class="btn btn-sm <?= $filter_type===$t ? 'btn-dark' : 'btn-outline-secondary' ?>">
                    <?= ucfirst($t) ?>
                </button>
                <?php endforeach; ?>
            </div>
            <?php if ($search || $filter_type): ?>
                <a href="index.php?action=view_reports" class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-x-lg"></i> Clear
                </a>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php if (empty($reports)): ?>
    <div class="text-center py-5 text-muted">
        <i class="bi bi-folder2-open" style="font-size:3rem;"></i>
        <p class="mt-3 fs-5"><?= $search || $filter_type ? 'No reports match your filters.' : 'No reports saved yet.' ?></p>
    </div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($reports as $r): ?>
            <?php $bs = $type_badge[$r['report_type']] ?? 'secondary'; ?>
            <div class="col-md-6 col-xl-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body d-flex flex-column gap-2">
                        <div class="d-flex justify-content-between align-items-start">
                            <h6 class="card-title mb-0 fw-bold"><?= htmlspecialchars($r['title']) ?></h6>
                            <?php if ($r['report_type']): ?>
                                <span class="badge bg-<?= $bs ?> ms-2 text-nowrap"><?= ucfirst($r['report_type']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="text-muted small d-flex gap-3 flex-wrap">
                            <span><i class="bi bi-person me-1"></i><?= htmlspecialchars($r['author'] ?? 'Unknown') ?></span>
                            <span><i class="bi bi-calendar3 me-1"></i><?= date('M j, Y', strtotime($r['created_at'])) ?></span>
                        </div>
                        <div class="mt-auto pt-2">
                            <a href="index.php?action=report_detail&id=<?= (int)$r['id'] ?>"
                               class="btn btn-sm btn-dark">
                                <i class="bi bi-eye me-1"></i> View
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>