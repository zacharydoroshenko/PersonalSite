<?php
//  outputs JSON and exits.

header('Content-Type: application/json');
if (!is_logged_in()) { echo json_encode(['error'=>'Unauthorized']); exit; }

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

function safe_col_prev($field, $allowed) {
    return in_array($field, $allowed, true) ? '`'.$field.'`' : null;
}
function can_see_prev($field, $map) {
    foreach ($map[$field] ?? [] as $p) { if (can_access_section($p)) return true; }
    return false;
}

$config = json_decode($_POST['config'] ?? '{}', true);
if (!$config) { echo json_encode(['error'=>'Invalid config']); exit; }

$indep_field = $config['independentVar'] ?? 'url';
$indep       = safe_col_prev($indep_field, $allowed_fields);
if (!$indep || !can_see_prev($indep_field, $field_permission_map)) {
    echo json_encode(['error'=>'Field not permitted']); exit;
}

$x_expr  = ($indep_field === 'created_at') ? "DATE_FORMAT($indep,'%Y-%m-%d')" : $indep;
$x_alias = '`'.$indep_field.'`';

$requested_cols = $config['columns'] ?? [];
$select_parts   = ["$x_expr AS $x_alias"];
foreach ($requested_cols as $col) {
    if ($col === $indep_field) continue;
    if (!can_see_prev($col, $field_permission_map)) continue;
    $safe = safe_col_prev($col, $allowed_fields);
    if (!$safe) continue;
    $select_parts[] = in_array($col, $numeric_fields)
        ? "ROUND(AVG($safe),2) AS `$col`"
        : "COUNT(*) AS `{$col}_count`";
}

$where_parts = []; $params = [];
foreach ($config['filters'] ?? [] as $f) {
    $fc = safe_col_prev($f['field']??'', $allowed_fields);
    if (!$fc) continue;
    $ce = ($f['field']==='created_at') ? "DATE_FORMAT($fc,'%Y-%m-%d')" : $fc;
    switch ($f['operator']??'eq') {
        case 'eq':        $where_parts[] = "$ce = ?";        $params[] = $f['value']; break;
        case 'neq':       $where_parts[] = "$ce != ?";       $params[] = $f['value']; break;
        case 'contains':  $where_parts[] = "$ce LIKE ?";     $params[] = '%'.$f['value'].'%'; break;
        case 'ncontains': $where_parts[] = "$ce NOT LIKE ?"; $params[] = '%'.$f['value'].'%'; break;
        case 'gt':        $where_parts[] = "$ce > ?";        $params[] = $f['value']; break;
        case 'lt':        $where_parts[] = "$ce < ?";        $params[] = $f['value']; break;
    }
}

$sort_by = $config['sortBy'] ?? $indep_field;
$sort_expr = ($sort_by === $indep_field) ? $x_expr : (safe_col_prev($sort_by,$allowed_fields) ?: $x_expr);
$sort_dir  = strtoupper($config['sortOrder']??'ASC')==='DESC' ? 'DESC' : 'ASC';

$sql  = "SELECT ".implode(', ',$select_parts)." FROM analytics_logs";
if ($where_parts) $sql .= " WHERE ".implode(' AND ',$where_parts);
$sql .= " GROUP BY $x_expr ORDER BY $sort_expr $sort_dir LIMIT 5";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['rows' => $rows]);
exit;