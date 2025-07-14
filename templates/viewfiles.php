<?php
include 'conn.php';
$stmt = $pdo->query("SELECT * FROM files ORDER BY uploaded_at DESC");
$files = $stmt->fetchAll();
?>
<table>
  <tr>
    <th>Name</th>
    <th>Uploaded At</th>
    <th>Actions</th>
  </tr>
  <?php foreach ($files as $file): ?>
  <tr>
    <td><?= htmlspecialchars($file['filename']) ?></td>
    <td><?= $file['uploaded_at'] ?></td>
    <td>
      <a href="<?= $file['filepath'] ?>" download>Download</a>
      <a href="delete.php?id=<?= $file['id'] ?>">Delete</a>
    </td>
  </tr>
  <?php endforeach; ?>
</table>
