<?php
// --- Default Data (as in your code) ---
if (!isset($folder_types)) {
    $folder_types = [
        ['name' => 'Documents', 'items' => 12],
        ['name' => 'Pictures', 'items' => 34],
        ['name' => 'Videos', 'items' => 7],
        ['name' => 'Music', 'items' => 15],
        ['name' => 'Others', 'items' => 5],
    ];
}
if (!isset($storage_details)) {
    $storage_details = [
        'used' => 12,
        'total' => 50,
        'categories' => [
            ['icon' => 'fas fa-file-alt', 'name' => 'Documents', 'size' => 4, 'percentage' => 33, 'color' => '#007bff'],
            ['icon' => 'fas fa-image', 'name' => 'Pictures', 'size' => 3, 'percentage' => 25, 'color' => '#28a745'],
            ['icon' => 'fas fa-video', 'name' => 'Videos', 'size' => 2, 'percentage' => 17, 'color' => '#ffc107'],
            ['icon' => 'fas fa-music', 'name' => 'Music', 'size' => 2, 'percentage' => 17, 'color' => '#17a2b8'],
            ['icon' => 'fas fa-archive', 'name' => 'Others', 'size' => 1, 'percentage' => 8, 'color' => '#6c757d'],
        ]
    ];
}
if (!isset($uploading_on_drive_data)) {
    $uploading_on_drive_data = [
        ['filename' => 'Project.zip', 'time' => 2, 'progress' => 80],
        ['filename' => 'Photo.jpg', 'time' => 1, 'progress' => 60],
    ];
}
?>

<div class="container-fluid px-3 px-md-4 py-4">
  <div class="row g-4">
    <!-- Left/Main Column -->
    <div class="col-12 col-lg-8">
      <!-- Folders Section -->
      <div class="mb-4">
        <h5 class="mb-3 fw-semibold">Your Folders</h5>
        <div class="row g-3">
          <?php foreach ($folder_types as $folder): ?>
            <div class="col-6 col-sm-4 col-md-3">
              <div class="card border-0 shadow-sm rounded-4 h-100 p-3 d-flex flex-column justify-content-between">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <i class="fas fa-folder fa-2x text-primary"></i>
                  <div class="dropdown">
                    <button class="btn btn-sm btn-link text-muted" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                      <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                      <li><a class="dropdown-item" href="#">Open</a></li>
                      <li><a class="dropdown-item" href="#">Rename</a></li>
                      <li><a class="dropdown-item" href="#">Delete</a></li>
                    </ul>
                  </div>
                </div>
                <div>
                  <h6 class="card-title mb-1 fw-semibold"><?= htmlspecialchars($folder['name']) ?></h6>
                  <small class="text-muted"><?= htmlspecialchars($folder['items']) ?> Items</small>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <!-- Activity Chart Section -->
      <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="card-title mb-0 fw-semibold">Activity Chart</h5>
          <select class="form-select w-auto">
            <option>This year</option>
            <option>Last year</option>
          </select>
        </div>
        <canvas id="activityChart" height="120"></canvas>
      </div>
    </div>
    <!-- Right Column -->
    <div class="col-12 col-lg-4">
      <!-- Storage Details -->
      <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
        <h5 class="mb-3 fw-semibold">Storage Details</h5>
        <div class="text-center mb-4">
          <canvas id="storageChart" width="130" height="130"></canvas>
          <h4 class="mt-3 mb-1"><?= htmlspecialchars($storage_details['used']) ?>GB</h4>
          <small class="text-muted">used of <?= htmlspecialchars($storage_details['total']) ?>GB</small>
        </div>
        <ul class="list-unstyled mb-0">
          <?php foreach ($storage_details['categories'] as $category): ?>
            <li class="mb-3">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <span><i class="<?= htmlspecialchars($category['icon']) ?> me-2"></i><?= htmlspecialchars($category['name']) ?></span>
                <span class="small text-muted"><?= htmlspecialchars($category['size']) ?>GB</span>
              </div>
              <div class="progress" style="height: 5px;">
                <div class="progress-bar" role="progressbar" style="width: <?= htmlspecialchars($category['percentage']) ?>%; background-color: <?= htmlspecialchars($category['color']) ?>;" aria-valuenow="<?= htmlspecialchars($category['percentage']) ?>" aria-valuemin="0" aria-valuemax="100"></div>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
      <!-- Uploading on Drive -->
      <div class="card border-0 shadow-sm rounded-4 p-4">
        <h5 class="mb-3 fw-semibold">Uploading on Drive</h5>
        <ul class="list-unstyled mb-0">
          <?php foreach ($uploading_on_drive_data as $upload): ?>
            <li class="mb-3">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <span><?= htmlspecialchars($upload['filename']) ?></span>
                <span class="small text-muted"><?= htmlspecialchars($upload['time']) ?> min</span>
              </div>
              <div class="progress" style="height: 3px;">
                <div class="progress-bar bg-info" role="progressbar" style="width: <?= htmlspecialchars($upload['progress']) ?>%;" aria-valuenow="<?= htmlspecialchars($upload['progress']) ?>" aria-valuemin="0" aria-valuemax="100"></div>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
        <div class="text-center mt-3">
          <button class="btn btn-success btn-sm px-3"><i class="fas fa-plus me-1"></i> Buy Now</button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Responsive tweaks -->
<style>
  @media (max-width: 575.98px) {
    .card.p-4 { padding: 1.2rem !important; }
    .rounded-4 { border-radius: 1rem !important; }
  }
  .card {
    background: #f8f9fa;
  }
</style>
