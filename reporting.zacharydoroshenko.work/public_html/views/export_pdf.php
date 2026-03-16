<?php


if (!is_logged_in()) { http_response_code(403); die('Unauthorized'); }

$report_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$report_id) { header("Location: index.php?action=view_reports"); exit; }

// load dompdf
$autoload = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoload)) {
    die('<p style="font-family:sans-serif;padding:2rem;color:#c0392b;">
        <strong>PDF export unavailable.</strong><br>
        Dompdf is not installed. Run <code>composer require dompdf/dompdf</code>
        in the project root, then try again.
    </p>');
}
require_once $autoload;

$dompdf_is_v1 = class_exists('Dompdf\\Dompdf');

// fetch report
$stmt = $pdo->prepare(
    "SELECT r.*, u.username AS author
     FROM saved_reports r
     LEFT JOIN users u ON u.id = r.created_by
     WHERE r.id = ?"
);
$stmt->execute([$report_id]);
$report = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$report) { http_response_code(404); die('Report not found.'); }

// viewer access gate
if (!has_role('super_admin') && !has_role('analyst')) {
    $rtype = $report['report_type'] ?? '';
    if ($rtype && !can_access_section($rtype)) {
        http_response_code(403);
        die('<p style="font-family:sans-serif;padding:2rem;color:#c0392b;">
            <strong>Access denied.</strong> You do not have permission to export this report.
        </p>');
    }
}

$sections = json_decode($report['summary_text'], true);
if (!$sections || !is_array($sections)) { die('Report has no content.'); }

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
$allowed_fields = array_keys($field_permission_map);
$numeric_fields = ['screen_width','screen_height','load_time_ms','bot_score'];

function pdf_safe_col($field, $allowed) {
    return in_array($field, $allowed, true) ? '`'.$field.'`' : null;
}
// PDF export: same logic as report viewer — allow all whitelisted fields.
function pdf_can_see($field, $map) {
    return array_key_exists($field, $map);
}
function pdf_fetch_table($pdo, $config, $allowed, $numeric, $map) {
    $indep = $config['independentVar'] ?? 'url';
    $col   = pdf_safe_col($indep, $allowed);
    if (!$col) return [];
    $x_expr  = $indep === 'created_at' ? "DATE_FORMAT($col,'%Y-%m-%d')" : $col;
    $selects = ["$x_expr AS `$indep`"];
    foreach ($config['columns'] ?? [] as $c) {
        if ($c === $indep) continue;
        $sc = pdf_safe_col($c, $allowed);
        if (!$sc) continue;
        $selects[] = in_array($c, $numeric) ? "ROUND(AVG($sc),2) AS `$c`" : "COUNT(*) AS `{$c}_count`";
    }
    $where = []; $params = [];
    foreach ($config['filters'] ?? [] as $f) {
        $fc = pdf_safe_col($f['field']??'', $allowed); if (!$fc) continue;
        $ce = $f['field']==='created_at' ? "DATE_FORMAT($fc,'%Y-%m-%d')" : $fc;
        switch ($f['operator']??'eq') {
            case 'eq':        $where[] = "$ce = ?";        $params[] = $f['value']; break;
            case 'neq':       $where[] = "$ce != ?";       $params[] = $f['value']; break;
            case 'contains':  $where[] = "$ce LIKE ?";     $params[] = '%'.$f['value'].'%'; break;
            case 'ncontains': $where[] = "$ce NOT LIKE ?"; $params[] = '%'.$f['value'].'%'; break;
            case 'gt':        $where[] = "$ce > ?";        $params[] = $f['value']; break;
            case 'lt':        $where[] = "$ce < ?";        $params[] = $f['value']; break;
        }
    }
    $sortBy  = $config['sortBy'] ?? $indep;
    $sortExpr = ($sortBy === $indep) ? $x_expr : (pdf_safe_col($sortBy,$allowed) ?: $x_expr);
    $sortDir  = strtoupper($config['sortOrder']??'ASC')==='DESC'?'DESC':'ASC';
    $limit    = max(1, min((int)($config['limit']??20), 500));
    $sql  = "SELECT ".implode(',',$selects)." FROM analytics_logs";
    if ($where) $sql .= " WHERE ".implode(' AND ',$where);
    $sql .= " GROUP BY $x_expr ORDER BY $sortExpr $sortDir LIMIT $limit";
    $stmt = $pdo->prepare($sql); $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Pre-fetch table data
$table_data = [];
foreach ($sections as $i => $sec) {
    if (($sec['type']??'') === 'table' && !empty($sec['config'])) {
        $table_data[$i] = pdf_fetch_table($pdo, $sec['config'], $allowed_fields, $numeric_fields, $field_permission_map);
    }
}
function pdf_preceding_table($sections, $idx) {
    for ($i = $idx-1; $i >= 0; $i--) { if (($sections[$i]['type']??'')===  'table') return $i; }
    return -1;
}

// build html
$type_color = ['performance'=>'#2196F3','behavior'=>'#4CAF50','identity'=>'#FF9800'];
$badge_bg   = $type_color[$report['report_type']] ?? '#607d8b';

$html  = '<!DOCTYPE html><html><head><meta charset="UTF-8">';
$html .= '<style>
    body        { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; margin: 0; padding: 0; }
    .page       { padding: 30px 36px; }
    .header     { border-bottom: 2px solid #1a2332; padding-bottom: 12px; margin-bottom: 20px; }
    .header h1  { font-size: 20px; margin: 0 0 4px; color: #1a2332; }
    .meta       { font-size: 9px; color: #666; }
    .badge      { display:inline-block; padding:2px 8px; border-radius:8px; color:#fff; font-size:9px; font-weight:bold; }
    h2          { font-size: 14px; font-weight: bold; color: #1a2332; border-bottom: 1px solid #ddd; padding-bottom: 4px; margin: 20px 0 8px; }
    p           { line-height: 1.7; margin: 0 0 12px; }
    table       { width: 100%; border-collapse: collapse; margin-bottom: 16px; font-size: 10px; }
    th          { background: #1a2332; color: #fff; padding: 6px 10px; text-align: left; }
    td          { padding: 5px 10px; border-bottom: 1px solid #eee; }
    tr:nth-child(even) td { background: #f8f9fa; }
    .chart-note { background: #f1f3f5; border: 1px solid #dee2e6; border-radius: 4px; padding: 10px 14px; margin-bottom:14px; font-size:10px; color:#555; }
    .chart-note strong { color: #1a2332; }
    .empty      { color: #aaa; font-style: italic; }
</style></head><body><div class="page">';

// Header
$html .= '<div class="header">';
$html .= '<h1>' . htmlspecialchars($report['title']) . '</h1>';
$html .= '<div class="meta">';
if ($report['report_type']) {
    $html .= '<span class="badge" style="background:' . $badge_bg . ';">' . ucfirst($report['report_type']) . '</span> &nbsp;';
}
$html .= htmlspecialchars($report['author'] ?? 'Unknown') . ' &mdash; ';
$html .= date('F j, Y', strtotime($report['created_at']));
$html .= '</div></div>';

// Sections
foreach ($sections as $sec_i => $sec) {
    $type = $sec['type'] ?? '';

    if ($type === 'title') {
        $html .= '<h2>' . htmlspecialchars($sec['content'] ?? '') . '</h2>';

    } elseif ($type === 'text') {
        $text = htmlspecialchars($sec['content'] ?? '');
        $html .= '<p>' . nl2br($text) . '</p>';

    } elseif ($type === 'table') {
        $cfg    = $sec['config'] ?? [];
        if (!empty($cfg['hidden'])) continue;
        $rows = $table_data[$sec_i] ?? [];
        if (empty($rows)) {
            $html .= '<p class="empty">No data returned for this table.</p>';
        } else {
            $keys  = array_keys($rows[0]);
            $html .= '<table><thead><tr>';
            foreach ($keys as $k) {
                $html .= '<th>' . htmlspecialchars(str_replace('_',' ',ucfirst($k))) . '</th>';
            }
            $html .= '</tr></thead><tbody>';
            foreach ($rows as $row) {
                $html .= '<tr>';
                foreach ($keys as $k) $html .= '<td>' . htmlspecialchars((string)($row[$k]??'')) . '</td>';
                $html .= '</tr>';
            }
            $html .= '</tbody></table>';
        }

    } elseif ($type === 'chart') {
        // NOTE Dompdf cannot render Highcharts 
        $cc      = $sec['chartConfig'] ?? null;
        $tbl_idx = pdf_preceding_table($sections, $sec_i);
        if (!$cc || $tbl_idx === -1) {
            $html .= '<div class="chart-note">Chart data unavailable in PDF.</div>';
            continue;
        }
        $tbl_cfg   = $sections[$tbl_idx]['config'] ?? [];
        $indep_var = $tbl_cfg['independentVar'] ?? 'url';
        $y_field   = $cc['yField'] ?? '';
        $rows      = $table_data[$tbl_idx] ?? [];
        $chart_type = ucfirst($cc['chartType'] ?? 'chart');
        $chart_title = $cc['title'] ? htmlspecialchars($cc['title']) : ($chart_type . ' — ' . htmlspecialchars($y_field));

        $html .= '<div class="chart-note">';
        $html .= '<strong>' . $chart_title . '</strong> (' . $chart_type . ' — rendered as data table in PDF)<br>';
        if (empty($rows)) {
            $html .= '<span class="empty">No data available.</span>';
        } else {
            $html .= '<table style="margin-top:6px;"><thead><tr>';
            $html .= '<th>' . htmlspecialchars(str_replace('_',' ',ucfirst($indep_var))) . '</th>';
            $html .= '<th>' . htmlspecialchars(str_replace('_',' ',ucfirst($y_field))) . '</th>';
            $html .= '</tr></thead><tbody>';
            foreach ($rows as $row) {
                $x_val = (string)($row[$indep_var] ?? '');
                $y_val = $row[$y_field] ?? $row[$y_field.'_count'] ?? '—';
                $html .= '<tr><td>' . htmlspecialchars($x_val) . '</td><td>' . htmlspecialchars((string)$y_val) . '</td></tr>';
            }
            $html .= '</tbody></table>';
        }
        $html .= '</div>';
    }
}

$html .= '</div></body></html>';

// Render with Dompdf 
$safe_title = preg_replace('/[^a-z0-9_-]/i', '_', $report['title']);
$safe_title = trim(preg_replace('/_+/', '_', $safe_title), '_');
$filename   = 'report_' . $report_id . '_' . $safe_title . '.pdf';

if ($dompdf_is_v1) {
    // Dompdf v1+ 
    $options = new Dompdf\Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', false);
    $options->set('defaultFont', 'DejaVu Sans');
    $pdf = new Dompdf\Dompdf($options);
    $pdf->loadHtml($html);
    $pdf->setPaper('A4', 'portrait');
    $pdf->render();
    $pdf->stream($filename, ['Attachment' => true]);
} else {
    // Dompdf v0.6.x
    $pdf = new DOMPDF();
    $pdf->load_html($html);
    $pdf->set_paper('A4', 'portrait');
    $pdf->render();
    $pdf->stream($filename, ['Attachment' => 1]);
}
exit;