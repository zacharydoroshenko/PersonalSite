<?php

if (!has_role('analyst') && !has_role('super_admin')) die('Unauthorized');

$allowed = [
    'performance' => can_access_section('performance'),
    'behavior'    => can_access_section('behavior'),
    'identity'    => can_access_section('identity'),
];

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

$accessible_fields = [];
foreach ($field_permission_map as $field => $perms) {
    foreach ($perms as $p) {
        if (!empty($allowed[$p])) { $accessible_fields[] = $field; break; }
    }
}

// save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_report'])) {
    csrf_verify();
    $title        = mb_substr(trim($_POST['report_title'] ?? ''), 0, 255);
    if ($title === '') { echo "<p style='color:red;'>Report title is required.</p>"; return; }
    $content_json = $_POST['report_content_json'];
    $author_id    = $_SESSION['user_id'];

    // Auto-detect report_type 
    $sections_data = json_decode($content_json, true) ?: [];
    $cat_scores    = ['performance'=>0,'behavior'=>0,'identity'=>0];
    foreach ($sections_data as $sec) {
        $fields_used = [];
        if (($sec['type']??'') === 'table' && !empty($sec['config'])) {
            $fields_used[] = $sec['config']['independentVar'] ?? '';
            foreach ($sec['config']['columns'] ?? [] as $c) $fields_used[] = $c;
        }
        if (($sec['type']??'') === 'chart' && !empty($sec['chartConfig'])) {
            $fields_used[] = $sec['chartConfig']['yField'] ?? '';
        }
        foreach ($fields_used as $f) {
            foreach ($field_permission_map[$f] ?? [] as $cat) $cat_scores[$cat]++;
        }
    }
    arsort($cat_scores);
    $report_type = (max($cat_scores) > 0) ? array_key_first($cat_scores) : null;

    $stmt = $pdo->prepare("INSERT INTO saved_reports (title, summary_text, report_type, chart_config, created_by) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$title, $content_json, $report_type, null, $author_id]);
    echo "<p style='color:green;padding:10px;border:1px solid green;border-radius:5px;'>Report Published Successfully! (Type auto-detected: <strong>" . htmlspecialchars($report_type ?? 'none') . "</strong>)</p>";
}
?>

<script src="https://code.highcharts.com/highcharts.js"></script>

<style>
    .report-section        { background:#fff; border:1px solid #ddd; padding:15px; margin-bottom:15px; border-radius:8px; position:relative; }
    .section-controls      { margin-bottom:10px; display:flex; gap:10px; align-items:center; background:#f8f9fa; padding:10px; border-radius:4px; }
    .btn-delete            { color:#dc3545; cursor:pointer; font-weight:bold; margin-left:auto; }
    .section-input         { width:100%; padding:8px; border:1px solid #ccc; border-radius:4px; font-family:inherit; }
    .chart-host            { height:400px; width:100%; display:block; }
    .btn-add               { background:#28a745; color:white; padding:10px 20px; border:none; border-radius:4px; cursor:pointer; margin-top:10px; }
    .filter-row            { display:flex; gap:8px; align-items:center; margin-bottom:6px; }
    .filter-row select,
    .filter-row input      { padding:5px 7px; border:1px solid #ccc; border-radius:4px; font-family:inherit; font-size:.9em; }
    .filter-row select     { flex:1; }
    .filter-row input[type="text"] { flex:2; }
    .btn-remove-filter     { background:none; border:none; color:#dc3545; font-size:1.1em; cursor:pointer; padding:0 4px; line-height:1; }
    .btn-add-filter        { background:#6c757d; color:white; border:none; border-radius:4px; padding:5px 12px; cursor:pointer; font-size:.85em; margin-top:4px; }
    .chart-editor          { background:#f1f1f1; padding:15px; border-radius:4px; font-size:.9em; margin-bottom:10px; }
    .chart-editor-grid     { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
    .editor-label          { display:block; font-weight:bold; margin-bottom:3px; font-size:.85em; }
    .editor-input          { width:100%; padding:5px 7px; border:1px solid #ccc; border-radius:4px; font-family:inherit; font-size:.9em; box-sizing:border-box; }
    .color-swatch-row      { display:flex; flex-wrap:wrap; gap:6px; margin-top:4px; align-items:center; }
    .color-swatch-row input[type="color"] { width:36px; height:28px; padding:1px; border:1px solid #ccc; border-radius:3px; cursor:pointer; }
    .chart-section-note    { color:#888; font-style:italic; font-size:.82em; margin-top:6px; }
    .hidden-badge          { display:inline-block; background:#6c757d; color:#fff; font-size:.75em; padding:2px 7px; border-radius:10px; margin-left:6px; vertical-align:middle; }
    .editor-tabs           { display:flex; border-bottom:2px solid #ddd; margin-bottom:12px; }
    .editor-tab            { padding:6px 14px; cursor:pointer; border:1px solid transparent; border-bottom:none; border-radius:4px 4px 0 0; font-size:.85em; background:#e9ecef; margin-right:3px; user-select:none; }
    .editor-tab.active     { background:#fff; border-color:#ddd; border-bottom-color:#fff; font-weight:bold; }
    .editor-tab-panel      { display:none; }
    .editor-tab-panel.active { display:block; }
    .tbl-preview-wrap      { margin-top:12px; border-top:1px solid #ddd; padding-top:10px; }
    .tbl-preview           { width:100%; border-collapse:collapse; font-size:.82em; }
    .tbl-preview th        { background:#2c3e50; color:#fff; padding:6px 10px; text-align:left; }
    .tbl-preview td        { padding:5px 10px; border-bottom:1px solid #eee; }
    .tbl-preview tr:last-child td { border-bottom:none; }
    .btn-preview           { background:#17a2b8; color:white; border:none; border-radius:4px; padding:5px 12px; cursor:pointer; font-size:.85em; margin-top:8px; }
</style>

<h2>Narrative Report Builder</h2>

<form method="POST" id="reportForm" onsubmit="prepareSave()">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
    <section style="background:white;padding:20px;border-radius:8px;margin-bottom:20px;border:1px solid #ddd;">
        <label><strong>Report Title:</strong></label>
        <input type="text" name="report_title" style="width:100%;padding:10px;margin:10px 0;" placeholder="e.g., Weekly Traffic Analysis" required>
        <label><strong>Data Context:</strong></label><br>
        <?php foreach ($allowed as $cat => $is_allowed): ?>
            <label style="margin-right:15px;color:<?= $is_allowed ? 'black' : '#ccc' ?>;">
                <input type="checkbox" name="categories[]" value="<?= $cat ?>" <?= $is_allowed ? '' : 'disabled' ?>>
                <?= ucfirst($cat) ?>
            </label>
        <?php endforeach; ?>
        <p style="margin:8px 0 0;font-size:.82em;color:#888;">Report type will be auto-detected from the fields you use.</p>
    </section>

    <div id="sections-container"></div>
    <button type="button" class="btn-add" onclick="addSection()">+ Add Section</button>
    <input type="hidden" name="report_content_json" id="report_content_json">

    <div style="margin-top:40px;border-top:2px solid #eee;padding-top:20px;">
        <button type="submit" name="save_report" style="background:#2c3e50;color:white;padding:15px 30px;border:none;border-radius:4px;cursor:pointer;">
            Save Final Report
        </button>
    </div>
</form>

<script>
var sections = [];
var activeChartTabs = {};
var tablePreviewCache = {};

// fields filtered
var availableFields = <?php
    $all_fields = [
        ['id'=>'url',            'label'=>'URL'],
        ['id'=>'session_id',     'label'=>'Session ID'],
        ['id'=>'event_type',     'label'=>'Event Type'],
        ['id'=>'user_agent',     'label'=>'User Agent'],
        ['id'=>'language',       'label'=>'Language'],
        ['id'=>'js_enabled',     'label'=>'JS Enabled'],
        ['id'=>'images_enabled', 'label'=>'Images Enabled'],
        ['id'=>'css_enabled',    'label'=>'CSS Enabled'],
        ['id'=>'screen_width',   'label'=>'Screen Width'],
        ['id'=>'screen_height',  'label'=>'Screen Height'],
        ['id'=>'load_time_ms',   'label'=>'Load Time (ms)'],
        ['id'=>'bot_score',      'label'=>'Bot Score'],
        ['id'=>'created_at',     'label'=>'Created At'],
    ];
    $visible = array_filter($all_fields, function($f) use ($accessible_fields) {
        return in_array($f['id'], $accessible_fields);
    });
    echo json_encode(array_values($visible), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?>;

var DEFAULT_PALETTE = ['#2196F3','#4CAF50','#FF9800','#E91E63','#9C27B0',
                       '#00BCD4','#FF5722','#607D8B','#795548','#FFC107'];
var PLACEHOLDER_VALUES = [42, 67, 31, 88, 55];

// escape header
function esc(v) {
    return String(v == null ? '' : v)
        .replace(/&/g,'&amp;').replace(/"/g,'&quot;')
        .replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// Crud
function addSection() {
    var id = Date.now();
    sections.push({ id:id, type:'text', content:'' });
    renderSections();
}
function removeSection(id) {
    sections = sections.filter(function(s){ return s.id !== id; });
    delete activeChartTabs[id];
    renderSections();
}
function updateSectionType(id, newType) {
    var s = sections.find(function(s){ return s.id === id; });
    s.type = newType;
    if (newType !== 'table') delete s.config;
    if (newType !== 'chart') { delete s.chartConfig; delete activeChartTabs[id]; }
    renderSections();
}
function updateSectionContent(id, value) {
    sections.find(function(s){ return s.id === id; }).content = value;
}

// tab switching
function switchEditorTab(sectionId, tabName) {
    activeChartTabs[sectionId] = tabName;
    var prefix = 'etab-' + sectionId + '-';
    ['data','style','axes'].forEach(function(t) {
        var btn   = document.getElementById(prefix + t + '-btn');
        var panel = document.getElementById(prefix + t + '-panel');
        if (!btn || !panel) return;
        btn.classList.toggle('active', t === tabName);
        panel.classList.toggle('active', t === tabName);
    });
}

// chart config helpers
function updateChartConfig(id, key, value) {
    sections.find(function(s){ return s.id === id; }).chartConfig[key] = value;
    refreshChartPreview(id);
}
function updateChartColor(id, ci, value) {
    sections.find(function(s){ return s.id === id; }).chartConfig.colors[ci] = value;
    refreshChartPreview(id);
}
function addChartColor(id) {
    sections.find(function(s){ return s.id === id; }).chartConfig.colors.push('#999999');
    renderSections();
}
function removeChartColor(id, ci) {
    var s = sections.find(function(s){ return s.id === id; });
    if (s.chartConfig.colors.length > 1) { s.chartConfig.colors.splice(ci, 1); renderSections(); }
}
function refreshChartPreview(id) {
    var s = sections.find(function(s2){ return s2.id === id; });
    if (!s || s.type !== 'chart') return;
    var chartId = 'chart-' + id;
    for (var i = 0; i < Highcharts.charts.length; i++) {
        var hc = Highcharts.charts[i];
        if (hc && hc.renderTo && hc.renderTo.id === chartId) { hc.destroy(); break; }
    }
    renderHighchart(s, sections.indexOf(s));
}

// filter helpers
function addFilter(sid) {
    sections.find(function(s){ return s.id === sid; }).config.filters.push({ field: availableFields[0].id, operator:'eq', value:'' });
    renderSections();
}
function removeFilter(sid, fi) {
    sections.find(function(s){ return s.id === sid; }).config.filters.splice(fi, 1);
    renderSections();
}
function updateFilter(sid, fi, key, value) {
    sections.find(function(s){ return s.id === sid; }).config.filters[fi][key] = value;
    if (key !== 'value') renderSections();
}

// table config helpers
function updateTableConfig(id, key, value) {
    sections.find(function(s){ return s.id === id; }).config[key] = value;
    renderSections();
}
function updateTableColumns(id, field, checked) {
    var s = sections.find(function(s){ return s.id === id; });
    if (checked) { if (!s.config.columns.includes(field)) s.config.columns.push(field); }
    else { s.config.columns = s.config.columns.filter(function(c){ return c !== field; }); }
}
function updateTableHidden(id, checked) {
    sections.find(function(s){ return s.id === id; }).config.hidden = checked;
    renderSections();
}

// nearest upper table
function getPrecedingTableIndex(chartIndex) {
    for (var i = chartIndex - 1; i >= 0; i--) {
        if (sections[i].type === 'table') return i;
    }
    return -1;
}

// table preview
function loadTablePreview(sectionId) {
    var s = sections.find(function(s){ return s.id === sectionId; });
    if (!s || !s.config) return;
    var previewDiv = document.getElementById('tblprev-' + sectionId);
    if (!previewDiv) return;
    previewDiv.innerHTML = '<em style="color:#888;">Loading preview...</em>';

    fetch('index.php?action=table_preview', {
        method:  'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body:    'config=' + encodeURIComponent(JSON.stringify(s.config))
    })
    .then(function(r){ return r.json(); })
    .then(function(data) {
        if (data.error) { previewDiv.innerHTML = '<em style="color:#c0392b;">' + esc(data.error) + '</em>'; return; }
        if (!data.rows || data.rows.length === 0) { previewDiv.innerHTML = '<em style="color:#888;">No rows returned.</em>'; return; }
        // Cache the real rows
        tablePreviewCache[sectionId] = data.rows;
        // Refresh any chart
        sections.forEach(function(sec, idx) {
            if (sec.type === 'chart' && getPrecedingTableIndex(idx) === sections.indexOf(s)) {
                refreshChartPreview(sec.id);
            }
        });
        var keys = Object.keys(data.rows[0]);
        var html = '<div style="overflow-x:auto;"><table class="tbl-preview"><thead><tr>'
            + keys.map(function(k){ return '<th>' + esc(k.replace(/_/g,' ')) + '</th>'; }).join('')
            + '</tr></thead><tbody>'
            + data.rows.map(function(row){
                return '<tr>' + keys.map(function(k){ return '<td>' + esc(String(row[k] ?? '')); + '</td>'; }).join('') + '</tr>';
              }).join('')
            + '</tbody></table></div>'
            + '<p style="color:#888;font-size:.8em;margin:4px 0 0;">Showing ' + data.rows.length + ' rows (preview limit: 5)</p>';
        previewDiv.innerHTML = html;
    })
    .catch(function(e){ previewDiv.innerHTML = '<em style="color:#c0392b;">Preview failed: ' + esc(e.message) + '</em>'; });
}

// filter logic
function buildFilterRowHtml(sid, fr, fi) {
    var fieldOpts = availableFields.map(function(af) {
        return '<option value="' + af.id + '"' + (fr.field === af.id ? ' selected':'') + '>' + af.label + '</option>';
    }).join('');
    var ops = [
        {id:'eq',label:'= equals'},{id:'neq',label:'\u2260 not equals'},
        {id:'contains',label:'contains'},{id:'ncontains',label:'not contains'},
        {id:'gt',label:'> greater than'},{id:'lt',label:'< less than'}
    ];
    var opOpts = ops.map(function(op) {
        return '<option value="' + op.id + '"' + (fr.operator === op.id ? ' selected':'') + '>' + op.label + '</option>';
    }).join('');
    var safeVal = (fr.value || '').replace(/"/g,'&quot;');
    return '<div class="filter-row">'
        + '<select onchange="updateFilter(' + sid + ',' + fi + ',\'field\',this.value)">' + fieldOpts + '</select>'
        + '<select onchange="updateFilter(' + sid + ',' + fi + ',\'operator\',this.value)">' + opOpts + '</select>'
        + '<input type="text" placeholder="value..." value="' + safeVal + '" onchange="updateFilter(' + sid + ',' + fi + ',\'value\',this.value)">'
        + '<button type="button" class="btn-remove-filter" onclick="removeFilter(' + sid + ',' + fi + ')">&times;</button>'
        + '</div>';
}

// table section
function buildTableHtml(s) {
    var defaultField = availableFields.length > 0 ? availableFields[0].id : 'url';
    if (!s.config) s.config = { independentVar:defaultField, columns:[defaultField], sortBy:defaultField, sortOrder:'asc', limit:20, filters:[], hidden:false };
    if (!s.config.filters) s.config.filters = [];
    if (s.config.hidden === undefined) s.config.hidden = false;

    var indepOpts = availableFields.map(function(f) {
        return '<option value="' + f.id + '"' + (s.config.independentVar === f.id ? ' selected':'') + '>' + f.label + '</option>';
    }).join('');
    var sortOpts = availableFields.map(function(f) {
        return '<option value="' + f.id + '"' + (s.config.sortBy === f.id ? ' selected':'') + '>' + f.label + '</option>';
    }).join('');
    var colChecks = availableFields.map(function(f) {
        return '<label style="font-weight:normal;">'
            + '<input type="checkbox" value="' + f.id + '"' + (s.config.columns.includes(f.id) ? ' checked':'')
            + ' onchange="updateTableColumns(' + s.id + ',this.value,this.checked)"> '
            + f.label + '</label>';
    }).join('');
    var filterRows = s.config.filters.map(function(fr, fi) { return buildFilterRowHtml(s.id, fr, fi); }).join('');
    var filterNote = s.config.filters.length > 0 ? '<span style="margin-left:8px;color:#555;font-size:.85em;">Combined with AND logic.</span>' : '';
    var previewNote = s.config.filters.length > 0 ? ', with ' + s.config.filters.length + ' filter(s)' : '';

    return '<div style="background:#f1f1f1;padding:15px;border-radius:4px;font-size:.9em;">'
        + '<div style="margin-bottom:10px;padding-bottom:10px;border-bottom:1px solid #ddd;">'
        +   '<label style="font-weight:normal;display:flex;align-items:center;gap:6px;cursor:pointer;">'
        +     '<input type="checkbox"' + (s.config.hidden ? ' checked':'') + ' onchange="updateTableHidden(' + s.id + ',this.checked)">'
        +     '<strong>Hide this table in the final report</strong>'
        +     '<span style="color:#888;font-size:.85em;font-weight:normal;">(data still available to charts)</span>'
        +   '</label>'
        + '</div>'
        + '<div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;">'
        +   '<div><label><strong>Independent Variable (Rows):</strong></label><br>'
        +     '<select class="section-input" onchange="updateTableConfig(' + s.id + ',\'independentVar\',this.value)">' + indepOpts + '</select></div>'
        +   '<div><label><strong>Sort By:</strong></label><br>'
        +     '<div style="display:flex;gap:5px;">'
        +       '<select class="section-input" onchange="updateTableConfig(' + s.id + ',\'sortBy\',this.value)">' + sortOpts + '</select>'
        +       '<select class="section-input" style="width:100px;" onchange="updateTableConfig(' + s.id + ',\'sortOrder\',this.value)">'
        +         '<option value="asc"'  + (s.config.sortOrder==='asc'  ? ' selected':'') + '>Asc</option>'
        +         '<option value="desc"' + (s.config.sortOrder==='desc' ? ' selected':'') + '>Desc</option>'
        +       '</select>'
        +     '</div></div>'
        + '</div>'
        + '<div style="margin-top:10px;"><label><strong>Included Columns:</strong></label><br>'
        +   '<div style="display:flex;flex-wrap:wrap;gap:10px;margin-top:5px;">' + colChecks + '</div></div>'
        + '<div style="margin-top:15px;border-top:1px solid #ddd;padding-top:12px;">'
        +   '<label><strong>Row Filters:</strong></label>'
        +   '<div style="margin-top:6px;">' + filterRows + '</div>'
        +   '<button type="button" class="btn-add-filter" onclick="addFilter(' + s.id + ')">+ Add Filter</button>'
        +   filterNote
        + '</div>'
        + '<div style="margin-top:10px;color:#666;font-style:italic;">'
        +   'Preview: up to 20 rows grouped by ' + s.config.independentVar + previewNote + '.'
        + '</div>'
        // Live preview 
        + '<div class="tbl-preview-wrap">'
        +   '<button type="button" class="btn-preview" onclick="loadTablePreview(' + s.id + ')">&#128269; Load Data Preview</button>'
        +   '<div id="tblprev-' + s.id + '" style="margin-top:8px;"></div>'
        + '</div>'
        + '</div>';
}

// chart builder
function buildChartHtml(s, sectionIndex) {
    if (!s.chartConfig) {
        var defaultY = availableFields.length > 0 ? availableFields[0].id : 'url';
        s.chartConfig = {
            chartType:'bar', title:'', subtitle:'', xAxisLabel:'', yAxisLabel:'',
            yField:defaultY, colors:DEFAULT_PALETTE.slice(),
            bgColor:'#ffffff', plotBgColor:'#ffffff',
            legendEnabled:true, dataLabels:false, yMin:'', yMax:'', tooltipSuffix:'',
        };
    }
    var cc = s.chartConfig;
    var tableIdx = getPrecedingTableIndex(sectionIndex);
    var tableNote, xAxisInfo;

    // Y field options
    var linkedCols = [];
    if (tableIdx !== -1) {
        var tblCfg = sections[tableIdx].config || {};
        linkedCols = (tblCfg.columns || []).slice();
        if (tblCfg.independentVar && !linkedCols.includes(tblCfg.independentVar)) {
            linkedCols.unshift(tblCfg.independentVar);
        }
        var indepVarLabel = availableFields.find(function(f){ return f.id === tblCfg.independentVar; });
        var indepName = indepVarLabel ? indepVarLabel.label : tblCfg.independentVar || '—';
        tableNote = '<div class="chart-section-note">&#8593; Data source: Section #' + (tableIdx+1) + ' (table)</div>';
        xAxisInfo = '<div style="background:#e8f4fd;border:1px solid #b3d7f0;border-radius:4px;padding:8px 10px;margin-top:10px;">'
            + '<span class="editor-label" style="margin:0;">X Axis (auto)</span>'
            + '<div style="margin-top:3px;color:#555;">Always uses the linked table\'s Independent Variable: <strong>' + esc(indepName) + '</strong></div>'
            + '</div>';
    } else {
        tableNote = '<div class="chart-section-note" style="color:#c0392b;">&#9888; No table found above this chart.</div>';
        xAxisInfo = '';
        linkedCols = availableFields.map(function(f){ return f.id; });
    }

    // Filter y field opts 
    var yFieldPool = availableFields.filter(function(f){
        return linkedCols.includes(f.id);
    });
    if (yFieldPool.length === 0) yFieldPool = availableFields;

    var chartTypeOpts = ['bar','line','pie'].map(function(t) {
        return '<option value="' + t + '"' + (cc.chartType===t ? ' selected':'') + '>' + t.charAt(0).toUpperCase() + t.slice(1) + ' Chart</option>';
    }).join('');
    var yFieldOpts = yFieldPool.map(function(f) {
        return '<option value="' + f.id + '"' + (cc.yField===f.id ? ' selected':'') + '>' + f.label + '</option>';
    }).join('');
    var swatches = cc.colors.map(function(c, ci) {
        return '<input type="color" value="' + c + '" title="Color ' + (ci+1) + '" onchange="updateChartColor(' + s.id + ',' + ci + ',this.value)">'
            + '<button type="button" style="background:none;border:none;color:#dc3545;cursor:pointer;font-size:.8em;padding:0 2px;" onclick="removeChartColor(' + s.id + ',' + ci + ')">&#x2715;</button>';
    }).join('');

    var activeTab = activeChartTabs[s.id] || 'data';
    var pid = 'etab-' + s.id;
    function tabBtn(id, label) {
        return '<div id="' + pid + '-' + id + '-btn" class="editor-tab' + (activeTab===id ? ' active':'') + '" onclick="switchEditorTab(' + s.id + ',\'' + id + '\')">' + label + '</div>';
    }
    function tabPanel(id, content) {
        return '<div id="' + pid + '-' + id + '-panel" class="editor-tab-panel' + (activeTab===id ? ' active':'') + '">' + content + '</div>';
    }

    var dataPanel =
        '<div class="chart-editor-grid">'
        +   '<div><span class="editor-label">Chart Type</span>'
        +     '<select class="editor-input" onchange="updateChartConfig(' + s.id + ',\'chartType\',this.value)">' + chartTypeOpts + '</select></div>'
        +   '<div><span class="editor-label">Chart Title</span>'
        +     '<input type="text" class="editor-input" placeholder="Optional title..." value="' + esc(cc.title) + '" oninput="updateChartConfig(' + s.id + ',\'title\',this.value)"></div>'
        + '</div>'
        + xAxisInfo
        + '<div class="chart-editor-grid" style="margin-top:10px;">'
        +   '<div><span class="editor-label">Y Axis / Value Field</span>'
        +     '<select class="editor-input" onchange="updateChartConfig(' + s.id + ',\'yField\',this.value)">' + yFieldOpts + '</select></div>'
        + '</div>';

    var stylePanel =
        '<div class="chart-editor-grid">'
        +   '<div><span class="editor-label">Subtitle</span>'
        +     '<input type="text" class="editor-input" placeholder="Optional subtitle..." value="' + esc(cc.subtitle) + '" oninput="updateChartConfig(' + s.id + ',\'subtitle\',this.value)"></div>'
        +   '<div><span class="editor-label">Tooltip Suffix</span>'
        +     '<input type="text" class="editor-input" placeholder="e.g. ms, %, px" value="' + esc(cc.tooltipSuffix) + '" oninput="updateChartConfig(' + s.id + ',\'tooltipSuffix\',this.value)"></div>'
        + '</div>'
        + '<div class="chart-editor-grid" style="margin-top:10px;">'
        +   '<div><span class="editor-label">Chart Background</span>'
        +     '<input type="color" class="editor-input" style="height:34px;padding:2px;" value="' + cc.bgColor + '" onchange="updateChartConfig(' + s.id + ',\'bgColor\',this.value)"></div>'
        +   '<div><span class="editor-label">Plot Background</span>'
        +     '<input type="color" class="editor-input" style="height:34px;padding:2px;" value="' + cc.plotBgColor + '" onchange="updateChartConfig(' + s.id + ',\'plotBgColor\',this.value)"></div>'
        + '</div>'
        + '<div style="margin-top:10px;"><span class="editor-label">Series / Slice Colors</span>'
        +   '<div class="color-swatch-row">' + swatches + '</div>'
        +   '<button type="button" class="btn-add-filter" style="margin-top:6px;" onclick="addChartColor(' + s.id + ')">+ Add Color</button>'
        + '</div>'
        + '<div class="chart-editor-grid" style="margin-top:10px;">'
        +   '<div><label style="font-weight:normal;display:flex;align-items:center;gap:6px;">'
        +     '<input type="checkbox"' + (cc.legendEnabled ? ' checked':'') + ' onchange="updateChartConfig(' + s.id + ',\'legendEnabled\',this.checked)">'
        +     '<strong>Show Legend</strong></label></div>'
        +   '<div><label style="font-weight:normal;display:flex;align-items:center;gap:6px;">'
        +     '<input type="checkbox"' + (cc.dataLabels ? ' checked':'') + ' onchange="updateChartConfig(' + s.id + ',\'dataLabels\',this.checked)">'
        +     '<strong>Show Data Labels</strong></label></div>'
        + '</div>';

    var axesPanel =
        '<div class="chart-editor-grid">'
        +   '<div><span class="editor-label">X Axis Label</span>'
        +     '<input type="text" class="editor-input" placeholder="X axis title..." value="' + esc(cc.xAxisLabel) + '" oninput="updateChartConfig(' + s.id + ',\'xAxisLabel\',this.value)"></div>'
        +   '<div><span class="editor-label">Y Axis Label</span>'
        +     '<input type="text" class="editor-input" placeholder="Y axis title..." value="' + esc(cc.yAxisLabel) + '" oninput="updateChartConfig(' + s.id + ',\'yAxisLabel\',this.value)"></div>'
        + '</div>'
        + '<div class="chart-editor-grid" style="margin-top:10px;">'
        +   '<div><span class="editor-label">Y Axis Min</span>'
        +     '<input type="number" class="editor-input" placeholder="auto" value="' + esc(cc.yMin) + '" oninput="updateChartConfig(' + s.id + ',\'yMin\',this.value)"></div>'
        +   '<div><span class="editor-label">Y Axis Max</span>'
        +     '<input type="number" class="editor-input" placeholder="auto" value="' + esc(cc.yMax) + '" oninput="updateChartConfig(' + s.id + ',\'yMax\',this.value)"></div>'
        + '</div>'
        + '<div class="chart-section-note" style="margin-top:8px;">Y axis min/max ignored for pie charts.</div>';

    return '<div class="chart-editor">'
        + tableNote
        + '<div class="editor-tabs" style="margin-top:10px;">'
        +   tabBtn('data','Data') + tabBtn('style','Style &amp; Colors') + tabBtn('axes','Axes &amp; Scale')
        + '</div>'
        + tabPanel('data', dataPanel) + tabPanel('style', stylePanel) + tabPanel('axes', axesPanel)
        + '</div>'
        + '<div class="chart-host" id="chart-' + s.id + '"></div>';
}

// render
function renderSections() {
    var container = document.getElementById('sections-container');
    container.innerHTML = '';
    sections.forEach(function(s, index) {
        var div = document.createElement('div');
        div.className = 'report-section';
        var hiddenBadge = (s.type === 'table' && s.config && s.config.hidden) ? '<span class="hidden-badge">hidden</span>' : '';
        var contentHtml = '';
        if      (s.type === 'title') contentHtml = '<input type="text" class="section-input" placeholder="Enter Section Title..." data-section-id="' + s.id + '" oninput="updateSectionContent(' + s.id + ',this.value)">';
        else if (s.type === 'text')  contentHtml = '<textarea class="section-input" rows="4" placeholder="Enter Analyst Analysis..." oninput="updateSectionContent(' + s.id + ',this.value)">' + esc(s.content) + '</textarea>';
        else if (s.type === 'table') contentHtml = buildTableHtml(s);
        else if (s.type === 'chart') contentHtml = buildChartHtml(s, index);

        div.innerHTML = '<div class="section-controls">'
            + '<span>Section #' + (index+1) + '</span>' + hiddenBadge
            + '<select onchange="updateSectionType(' + s.id + ',this.value)">'
            +   '<option value="text"'  + (s.type==='text'  ? ' selected':'') + '>Text Paragraph</option>'
            +   '<option value="title"' + (s.type==='title' ? ' selected':'') + '>Section Title</option>'
            +   '<option value="chart"' + (s.type==='chart' ? ' selected':'') + '>Highchart Visual</option>'
            +   '<option value="table"' + (s.type==='table' ? ' selected':'') + '>Data Table</option>'
            + '</select>'
            + '<span class="btn-delete" onclick="removeSection(' + s.id + ')">Remove</span>'
            + '</div>' + contentHtml;
        container.appendChild(div);
        if (s.type === 'title') div.querySelector('[data-section-id="' + s.id + '"]').value = s.content || '';
        if (s.type === 'chart') renderHighchart(s, index);
    });
}

// sample categories
function buildSampleCategories(fieldId, count) {
    var result = [], i;
    if (fieldId === 'created_at') {
        var now = new Date();
        for (i = count-1; i >= 0; i--) { var d = new Date(now); d.setDate(d.getDate()-i);
            result.push(d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0')); }
    } else if (fieldId==='url')            { var p=['/index.html','/about','/contact','/blog','/products']; for(i=0;i<count;i++) result.push(p[i%p.length]); }
    else if (fieldId==='event_type')       { var e=['page_view','page_exit','click','scroll','form_submit']; for(i=0;i<count;i++) result.push(e[i%e.length]); }
    else if (fieldId==='language')         { var l=['en','fr','de','es','zh']; for(i=0;i<count;i++) result.push(l[i%l.length]); }
    else if (fieldId==='session_id')       { for(i=0;i<count;i++) result.push('sess_'+(1000+i)); }
    else if (fieldId==='screen_width')     { var w=[1920,1440,1366,1280,768]; for(i=0;i<count;i++) result.push(String(w[i%w.length])); }
    else if (fieldId==='screen_height')    { var h=[1080,900,768,1024,720]; for(i=0;i<count;i++) result.push(String(h[i%h.length])); }
    else if (fieldId==='load_time_ms')     { var t=[320,480,210,670,150]; for(i=0;i<count;i++) result.push(String(t[i%t.length])+'ms'); }
    else if (fieldId==='bot_score')        { for(i=0;i<count;i++) result.push(String(i*25)); }
    else if (fieldId==='user_agent')       { var u=['Chrome/120','Firefox/121','Safari/17','Edge/119']; for(i=0;i<count;i++) result.push(u[i%u.length]); }
    else if (['js_enabled','images_enabled','css_enabled'].includes(fieldId)) { result=['true','false']; while(result.length<count) result.push(result[result.length%2]); }
    else { for(i=0;i<count;i++) result.push(fieldId+'_'+(i+1)); }
    return result;
}

// highcharts preview
function buildChartDataFromRows(rows, indepVar, yField) {
    var numericFields = ['screen_width','screen_height','load_time_ms','bot_score'];
    var categories = [], seriesData = [];
    rows.forEach(function(row) {
        categories.push(String(row[indepVar] != null ? row[indepVar] : ''));
        var yKey = yField + '_count';
        if (numericFields.indexOf(yField) !== -1 && row[yField] != null) {
            seriesData.push(parseFloat(row[yField]));
        } else if (row[yKey] != null) {
            seriesData.push(parseInt(row[yKey], 10));
        } else if (row[yField] != null) {
            var v = row[yField];
            seriesData.push(isNaN(parseFloat(v)) ? 0 : parseFloat(v));
        } else {
            seriesData.push(0);
        }
    });
    return { categories: categories, values: seriesData };
}

function renderHighchart(s, sectionIndex) {
    var cc = s.chartConfig;
    var el = document.getElementById('chart-' + s.id);
    if (!el) return;

    var tableIdx = getPrecedingTableIndex(sectionIndex);
    var categories = [], seriesData = [];

    if (tableIdx !== -1) {
        var tbl      = sections[tableIdx];
        var tCfg     = tbl.config || {};
        var cachedRows = tablePreviewCache[tbl.id];

        if (cachedRows && cachedRows.length > 0) {
            // Use real data from cache
            var chartData = buildChartDataFromRows(cachedRows, tCfg.independentVar || 'url', cc.yField || 'load_time_ms');
            categories = chartData.categories;
            seriesData = chartData.values;
        } else {
            // No cache yet — auto-fetch real data for this table, then re-render
            // Show placeholder in the meantime
            var rowCount = Math.min(tCfg.limit || 5, 5);
            categories   = buildSampleCategories(tCfg.independentVar || 'url', rowCount);
            for (var j = 0; j < rowCount; j++) seriesData.push(PLACEHOLDER_VALUES[j % PLACEHOLDER_VALUES.length]);

            // Silently fetch real data and update chart once it arrives
            if (tbl.config) {
                fetch('index.php?action=table_preview', {
                    method:  'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body:    'config=' + encodeURIComponent(JSON.stringify(tbl.config))
                })
                .then(function(r){ return r.json(); })
                .then(function(data) {
                    if (data.rows && data.rows.length > 0) {
                        tablePreviewCache[tbl.id] = data.rows;
                        refreshChartPreview(s.id);
                    }
                })
                .catch(function(){});
            }
        }
    } else {
        categories = ['Jan','Feb','Mar','Apr'];
        seriesData = [42,67,31,88];
    }

    var isPie   = cc.chartType === 'pie';
    var yMinVal = (cc.yMin !== '' && cc.yMin != null) ? parseFloat(cc.yMin) : null;
    var yMaxVal = (cc.yMax !== '' && cc.yMax != null) ? parseFloat(cc.yMax) : null;
    var safeColors = (cc.colors && cc.colors.length) ? cc.colors : undefined;

    Highcharts.chart(el, {
        chart: { type: isPie?'pie':cc.chartType, height:400, width:null,
                 backgroundColor:cc.bgColor, plotBackgroundColor:cc.plotBgColor, animation:false },
        colors:   safeColors,
        title:    { text: cc.title    || null },
        subtitle: { text: cc.subtitle || null },
        legend:   { enabled: !!cc.legendEnabled },
        tooltip:  { valueSuffix: cc.tooltipSuffix || '' },
        credits:  { enabled: false },
        xAxis: isPie ? undefined : {
            categories: categories,
            title: { text: cc.xAxisLabel || null },
            labels: { rotation: categories.length > 6 ? -45 : 0, style: { fontSize:'11px' } }
        },
        yAxis: isPie ? undefined : {
            title: { text: cc.yAxisLabel || null },
            min: yMinVal, max: yMaxVal
        },
        plotOptions: {
            series: { dataLabels:{ enabled:!!cc.dataLabels }, animation:false },
            pie:    { dataLabels:{ enabled:!!cc.dataLabels }, allowPointSelect:true }
        },
        series: isPie
            ? [{ type:'pie', name:cc.yField||'Value', data:categories.map(function(cat,i){ return {name:cat,y:seriesData[i]}; }) }]
            : [{ name:cc.yField||'Value', data:seriesData }]
    });
}

// save
function prepareSave() {
    document.getElementById('report_content_json').value = JSON.stringify(sections);
}

window.onload = function() {
};
</script>