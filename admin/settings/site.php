<?php
// admin/settings/site.php
require_once __DIR__ . '/../../includes/admin_bootstrap.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Site Settings - HomewareOnTap Admin</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="../../assets/css/admin.css" rel="stylesheet">
</head>
<body>
<div class="admin-container">
    <?php include_once __DIR__ . '/../../includes/sidebar.php'; ?>
    <div class="admin-main">
        <div class="content-section p-4">
            <h3>Site Settings</h3>
            <div class="card admin-card mt-3">
                <div class="card-body">
                    <p class="mb-0">Admin settings page placeholder. You can add store settings here later.</p>
                    <a href="<?php echo SITE_URL; ?>/admin/index.php" class="btn btn-primary mt-3">Back to Dashboard</a>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
