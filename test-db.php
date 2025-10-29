<?php
declare(strict_types=1);
require_once __DIR__ . '/utils/Db.php';

header('Content-Type: text/html; charset=utf-8');

try {
    $pdo = Db::conn();

    // Ensure DB selected (schema.sql includes USE ... already, but just in case)
    $dbName = getenv('DB_NAME') ?: 'waste_not_kitchen';
    $pdo->exec("USE `{$dbName}`");

    $total = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $stmt = $pdo->query('SELECT id, username, role, created_at FROM users ORDER BY id DESC LIMIT 50');
    $rows = $stmt->fetchAll();
} catch (\Throwable $e) {
    http_response_code(500);
    ?><!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <title>DB check</title>
    <style> body{font-family:-apple-system,system-ui,Segoe UI,Roboto,Helvetica,Arial,sans-serif;margin:40px;}</style>
  </head>
  <body>
    <h1>Database check failed</h1>
    <p><?php echo htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'); ?></p>
    <p><a href="/Waste-Not-Kitchen/">Back</a></p>
  </body>
</html><?php
    exit;
}
?>


<!-- Can remove the "test-db.php" file that was used for testing the database connection. -->


<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Users · Waste‑Not‑Kitchen</title>
    <style>
      body{font-family:-apple-system,system-ui,Segoe UI,Roboto,Helvetica,Arial,sans-serif;margin:40px}
      table{border-collapse:collapse;width:100%;max-width:900px}
      th,td{border:1px solid #ddd;padding:8px;text-align:left}
      th{background:#f6f6f6}
      .muted{color:#666}
      .actions a{margin-right:10px}
    </style>
  </head>
  <body>
    <h1>Users (<?php echo (int)$total; ?>)</h1>
    <p class="muted">Showing up to the 50 most recent rows.</p>

    <?php if (empty($rows)): ?>
      <p>No users found. You can add one in phpMyAdmin or by running a quick INSERT:</p>
      <pre>INSERT INTO users (username, role, password) VALUES ('demo','customer', '$2y$10$exampleexampleexampleexampleexaP4mEitjJq3bXJUo0vYjRkq');</pre>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Role</th>
            <th>Created</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?php echo (int)$r['id']; ?></td>
            <td><?php echo htmlspecialchars($r['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($r['role'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($r['created_at'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>

    <p class="actions">
      <a href="/phpMyAdmin/">Open phpMyAdmin</a>
      <a href="/Waste-Not-Kitchen/">Back to home</a>
    </p>
  </body>
</html>
