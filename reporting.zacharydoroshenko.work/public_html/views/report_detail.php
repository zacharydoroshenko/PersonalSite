<?php
// public_html/views/report_detail.php

if (!is_logged_in()) die('Unauthorized');

$report_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$report_id) { echo '<p>Invalid report ID.</p>'; return; }

$stmt = $pdo->prepare(
    "SELECT r.*, u.username AS author
     FROM saved_reports r
     LEFT JOIN users u ON u.id = r.created_by
     WHERE r.id = ?"
);
$stmt->execute([$report_id]);
$report = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$report) { echo '<p>Report not found.</p>'; return; }

// ── Viewer access gate ───────────────────────────────────────────────────────
// Analysts and admins can view any saved report.
// Viewers may only view reports whose type matches their section permissions.
if (!has_role('super_admin') && !has_role('analyst')) {
    $rtype = $report['report_type'] ?? '';
    if ($rtype && !can_access_section($rtype)) {
        http_response_code(403);
        include(__DIR__ . '/403.php');
        return;
    }
}

$sections = json_decode($report['summary_text'], true);
if (!$sections || !is_array($sections)) { echo '<p>This report has no content.</p>'; return; }

// ── Permission check ─────────────────────────────────────────────────────────
$field_permission_map = [
    'url'            => ['performance','behavior'],
    'session_id'     => ['behavior','identity'],
    'event_type'     => ['behavior'],
    'user_agent'     => ['identity'],
    'language'       => ['identity'],
    'js_enabled'     => ['performance'],
    'images_enabled' => ['performance'],
    'css_enabled'    => ['performance'],
    'screen_width'   => ['performance','identity'],
    'screen_height'  => ['performance','identity'],
    'load_time_ms'   => ['performance'],
    'bot_score'      => ['identity'],
    'created_at'     => ['performance','behavior'],
];

function user_can_see_field($field, $field_permission_map) {
    // Allow all whitelisted fields
    return array_key_exists($field, $field_permission_map);
}

// Whitelist of allowed analytics column names
$allowed_fields  = array_keys($field_permission_map);
$numeric_fields  = ['screen_width','screen_height','load_time_ms','bot_score'];

function safe_col($field, $allowed) {
    return in_array($field, $allowed, true) ? '`'.$field.'`' : null;
}

//Query one table section 
function fetch_table_data($pdo, $config, $allowed_fields, $numeric_fields, $field_permission_map) {
    $indep_field = $config['independentVar'] ?? 'url';
    $indep       = safe_col($indep_field, $allowed_fields);
    if (!$indep) return []; 


    $x_expr  = ($indep_field === 'created_at')
                 ? "DATE_FORMAT($indep, '%Y-%m-%d')"
                 : $indep;
    $x_alias = '`'.$indep_field.'`';

    $requested_cols = $config['columns'] ?? [];
    $select_parts   = ["$x_expr AS $x_alias"];

    foreach ($requested_cols as $col) {
        if ($col === $indep_field) continue;
        // No per-viewer permission filter in report viewer — safe_col() handles injection
        $safe = safe_col($col, $allowed_fields);
        if (!$safe) continue;
        if (in_array($col, $numeric_fields)) {
            $select_parts[] = "ROUND(AVG($safe), 2) AS `$col`";
        } else {
            // COUNT(*) gives "rows per group"
            // COUNT(DISTINCT col) on the same col as the GROUP BY = always 1.
            $select_parts[] = "COUNT(*) AS `{$col}_count`";
        }
    }

    // WHERE
    $where_parts = [];
    $params      = [];
    foreach ($config['filters'] ?? [] as $f) {
        $fc = safe_col($f['field'] ?? '', $allowed_fields);
        if (!$fc) continue;
        $col_expr = ($f['field'] === 'created_at') ? "DATE_FORMAT($fc,'%Y-%m-%d')" : $fc;
        switch ($f['operator'] ?? 'eq') {
            case 'eq':        $where_parts[] = "$col_expr = ?";        $params[] = $f['value']; break;
            case 'neq':       $where_parts[] = "$col_expr != ?";       $params[] = $f['value']; break;
            case 'contains':  $where_parts[] = "$col_expr LIKE ?";     $params[] = '%'.$f['value'].'%'; break;
            case 'ncontains': $where_parts[] = "$col_expr NOT LIKE ?"; $params[] = '%'.$f['value'].'%'; break;
            case 'gt':        $where_parts[] = "$col_expr > ?";        $params[] = $f['value']; break;
            case 'lt':        $where_parts[] = "$col_expr < ?";        $params[] = $f['value']; break;
        }
    }

    // Sort: when sorting by the independent variable, sort by the same grouped expression (x_expr) to avoid ordering by raw timestamp.
    $sort_by_field = $config['sortBy'] ?? $indep_field;
    if ($sort_by_field === $indep_field) {
        $sort_expr = $x_expr;
    } else {
        $sort_col_safe = safe_col($sort_by_field, $allowed_fields);
        $sort_expr     = $sort_col_safe ?: $x_expr;
    }
    $sort_dir = strtoupper($config['sortOrder'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
    $limit    = max(1, min((int)($config['limit'] ?? 20), 500));

    $sql  = "SELECT " . implode(', ', $select_parts) . " FROM analytics_logs";
    if ($where_parts) $sql .= " WHERE " . implode(' AND ', $where_parts);
    $sql .= " GROUP BY $x_expr ORDER BY $sort_expr $sort_dir LIMIT $limit";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

//Build chart data from table rows 
function build_chart_data($rows, $indep_var, $y_field, $numeric_fields) {
    $categories = [];
    $values     = [];
    foreach ($rows as $row) {
        $categories[] = (string)($row[$indep_var] ?? '');
        if (in_array($y_field, $numeric_fields) && array_key_exists($y_field, $row)) {
            $values[] = (float)$row[$y_field];
        } elseif (array_key_exists($y_field.'_count', $row)) {
            $values[] = (int)$row[$y_field.'_count'];
        } elseif (array_key_exists($y_field, $row)) {
            $v = $row[$y_field];
            $values[] = is_numeric($v) ? (float)$v : 0;
        } else {
            $values[] = 0;
        }
    }
    return ['categories' => $categories, 'values' => $values];
}

// prefetch table data
$table_data_by_index = [];
foreach ($sections as $i => $sec) {
    if (($sec['type']??'') === 'table' && !empty($sec['config'])) {
        $table_data_by_index[$i] = fetch_table_data($pdo, $sec['config'], $allowed_fields, $numeric_fields, $field_permission_map);
    }
}
function preceding_table_index($sections, $chart_index) {
    for ($i = $chart_index - 1; $i >= 0; $i--) {
        if (($sections[$i]['type']??'') === 'table') return $i;
    }
    return -1;
}
?>

<script src="https://code.highcharts.com/highcharts.js"></script>

<style>
    .rs-title     { font-size:1.4rem; font-weight:700; color:#1a2332; margin:2rem 0 .5rem; padding-bottom:.5rem; border-bottom:2px solid #dee2e6; }
    .rs-text      { line-height:1.8; color:#333; white-space:pre-wrap; }
    .rs-chart-wrap{ border-radius:.5rem; overflow:hidden; border:1px solid #dee2e6; margin-bottom:1.5rem; }
    .no-data-note { color:#c0392b; font-style:italic; font-size:.88em; }
    @media print  { .no-print { display:none !important; } }
</style>

<div class="report-detail-wrap">

    <a href="index.php?action=view_reports" class="back-link no-print">&#8592; Back to Reports</a>

    <div class="report-meta-bar">
        <strong><?= htmlspecialchars($report['title']) ?></strong>
        <?php if ($report['report_type']): ?>
            <span style="background:#2c3e50;color:#fff;padding:2px 10px;border-radius:10px;font-size:.82em;">
                <?= ucfirst($report['report_type']) ?>
            </span>
        <?php endif; ?>
        <span>&#128100; <?= htmlspecialchars($report['author'] ?? 'Unknown') ?></span>
        <span>&#128197; <?= date('F j, Y \a\t g:i a', strtotime($report['created_at'])) ?></span>
        <button onclick="window.print()" class="no-print"
                style="margin-left:auto;background:#6c757d;color:#fff;border:none;border-radius:4px;padding:6px 14px;cursor:pointer;font-size:.85em;">
            &#128424; Print / Save PDF
        </button>
    </div>

    <?php foreach ($sections as $sec_index => $section):
        $type = $section['type'] ?? '';
    ?>

        <?php if ($type === 'title'): ?>
            <div class="rs-title"><?= htmlspecialchars($section['content'] ?? '') ?></div>

        <?php elseif ($type === 'text'): ?>
            <p class="rs-text"><?= htmlspecialchars($section['content'] ?? '') ?></p>

        <?php elseif ($type === 'table'):
            $cfg    = $section['config'] ?? [];
            $hidden = !empty($cfg['hidden']);
            if (!$hidden):
                $rows = $table_data_by_index[$sec_index] ?? [];
        ?>
            <div class="table-responsive mb-4 rounded shadow-sm border">
                <?php if (empty($rows)): ?>
                    <div class="p-4 text-center text-muted fst-italic">No data returned for this table.</div>
                <?php else:
                    $actual_keys = array_keys($rows[0]);
                ?>
                    <table class="table table-hover table-sm mb-0">
                        <thead><tr>
                            <?php foreach ($actual_keys as $key): ?>
                                <th><?= htmlspecialchars(str_replace('_', ' ', ucfirst($key))) ?></th>
                            <?php endforeach; ?>
                        </tr></thead>
                        <tbody>
                            <?php foreach ($rows as $row): ?>
                                <tr>
                                    <?php foreach ($actual_keys as $key): ?>
                                        <td><?= htmlspecialchars((string)($row[$key] ?? '')) ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php elseif ($type === 'chart'):
            $cc      = $section['chartConfig'] ?? null;
            $tbl_idx = preceding_table_index($sections, $sec_index);
            $chart_uid = 'hc_' . (int)$section['id'];
            if (!$cc): ?>
                <div class="no-data-note">&#9888; Chart has no configuration.</div>
            <?php elseif ($tbl_idx === -1): ?>
                <div class="no-data-note">&#9888; No table section found above this chart.</div>
            <?php else:
                $tbl_cfg    = $sections[$tbl_idx]['config'] ?? [];
                $indep_var  = $tbl_cfg['independentVar'] ?? 'url';
                $y_field    = $cc['yField'] ?? 'load_time_ms';
                $tbl_rows   = $table_data_by_index[$tbl_idx] ?? [];
                $chart_data = build_chart_data($tbl_rows, $indep_var, $y_field, $numeric_fields);
                $chart_json = json_encode($chart_data, JSON_HEX_TAG | JSON_HEX_AMP);
                $cc_json    = json_encode($cc,         JSON_HEX_TAG | JSON_HEX_AMP);
            ?>
            <div class="rs-chart-wrap">
                <div id="<?= $chart_uid ?>" style="width:100%;height:400px;"></div>
            </div>
            <script>
            (function() {
                var data = <?= $chart_json ?>;
                var cc   = <?= $cc_json ?>;
                var isPie      = cc.chartType === 'pie';
                var safeColors = (cc.colors && cc.colors.length) ? cc.colors : undefined;
                var yMinVal    = (cc.yMin !== '' && cc.yMin != null) ? parseFloat(cc.yMin) : null;
                var yMaxVal    = (cc.yMax !== '' && cc.yMax != null) ? parseFloat(cc.yMax) : null;

                Highcharts.chart(<?= json_encode($chart_uid) ?>, {
                    chart: { type: isPie?'pie':cc.chartType, height:400, width:null,
                             backgroundColor:cc.bgColor||'#ffffff', plotBackgroundColor:cc.plotBgColor||'#ffffff' },
                    colors:   safeColors,
                    title:    { text: cc.title    || null },
                    subtitle: { text: cc.subtitle || null },
                    legend:   { enabled: !!cc.legendEnabled },
                    tooltip:  { valueSuffix: cc.tooltipSuffix || '' },
                    credits:  { enabled: false },
                    xAxis: isPie ? undefined : {
                        categories: data.categories,
                        title: { text: cc.xAxisLabel || null },
                        labels: { rotation: data.categories.length > 6 ? -45 : 0, style: { fontSize:'11px' } }
                    },
                    yAxis: isPie ? undefined : {
                        title: { text: cc.yAxisLabel || null },
                        min: yMinVal, max: yMaxVal
                    },
                    plotOptions: {
                        series: { dataLabels:{ enabled:!!cc.dataLabels } },
                        pie:    { dataLabels:{ enabled:!!cc.dataLabels }, allowPointSelect:true }
                    },
                    series: isPie
                        ? [{ type:'pie', name:cc.yField||'Value',
                             data: data.categories.map(function(cat,i){ return {name:cat,y:data.values[i]}; }) }]
                        : [{ name:cc.yField||'Value', data:data.values }]
                });
            })();
            </script>
            <?php endif; ?>
        <?php endif; ?>
    <?php endforeach; ?>
</div>