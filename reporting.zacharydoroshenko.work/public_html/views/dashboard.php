<?php
// views/dashboard.php
require_once(__DIR__ . '/../../db_config.php');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch the last 20 entries from your collector table
    $stmt = $pdo->query("SELECT id, session_id, event_type, url, load_time_ms, created_at FROM analytics_logs ORDER BY created_at DESC LIMIT 20");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<h2>Analytics Data Grid</h2>
<p>Displaying the most recent 20 entries collected from Wrecked Tech.</p>

<?php if (isset($error)): ?>
    <p style="color: red;"><?php echo $error; ?></p>
<?php else: ?>
    <table border="1" style="width: 100%; border-collapse: collapse; background: white;">
        <thead style="background: #eee;">
            <tr>
                <th>ID</th>
                <th>Session ID</th>
                <th>Event</th>
                <th>URL</th>
                <th>Load Time (ms)</th>
                <th>Timestamp</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['id']); ?></td>
                <td style="font-family: monospace; font-size: 0.8em;"><?php echo htmlspecialchars($row['session_id']); ?></td>
                <td><?php echo htmlspecialchars($row['event_type']); ?></td>
                <td><?php echo htmlspecialchars($row['url']); ?></td>
                <td><?php echo htmlspecialchars($row['load_time_ms']); ?></td>
                <td><?php echo htmlspecialchars($row['created_at']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
<hr style="margin: 40px 0;">
<h2>Event Distribution</h2>
<div style="width: 400px; height: 400px; background: white; padding: 20px;">
    <canvas id="eventChart"></canvas>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<?php
// Aggregate data for the chart (Count events by type)
$stmt = $pdo->query("SELECT event_type, COUNT(*) as count FROM analytics_logs GROUP BY event_type");
$chartData = $stmt->fetchAll(PDO::FETCH_ASSOC);

$labels = json_encode(array_column($chartData, 'event_type'));
$counts = json_encode(array_column($chartData, 'count'));
?>

<script>
const ctx = document.getElementById('eventChart').getContext('2d');
new Chart(ctx, {
    type: 'pie',
    data: {
        labels: <?php echo $labels; ?>,
        datasets: [{
            label: 'Event Types',
            data: <?php echo $counts; ?>,
            backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0']
        }]
    }
});
</script>
