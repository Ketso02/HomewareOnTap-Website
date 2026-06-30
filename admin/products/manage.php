<?php
// admin/products/manage.php
require_once __DIR__ . '/../../includes/admin_bootstrap.php';

// Handle product actions (delete, toggle status)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $productId = intval($_GET['id']);
    
    if ($_GET['action'] == 'delete') {
        // Soft delete product (set status to 'deleted')
        $stmt = $pdo->prepare("UPDATE products SET status = 'deleted' WHERE id = ?");
        $stmt->execute([$productId]);
        $_SESSION['success_message'] = "Product deleted successfully.";
    } 
    elseif ($_GET['action'] == 'toggle') {
        // Toggle product status
        $stmt = $pdo->prepare("SELECT status FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();
        
        $newStatus = ($product['status'] == 'active') ? 'inactive' : 'active';
        $stmt = $pdo->prepare("UPDATE products SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $productId]);
        $_SESSION['success_message'] = "Product status updated successfully.";
    }
    
    // Redirect to avoid form resubmission
    header('Location: manage.php');
    exit();
}

// Get search and filter parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Build query for products with optional filters
$query = "SELECT p.*, c.name as category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE p.status != 'deleted'";

$params = [];

if (!empty($search)) {
    $query .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($category) && $category != 'all') {
    $query .= " AND p.category_id = ?";
    $params[] = $category;
}

if (!empty($status) && $status != 'all') {
    $query .= " AND p.status = ?";
    $params[] = $status;
}

$query .= " ORDER BY p.created_at DESC";

// Get categories for filter dropdown
$categories = $pdo->query("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name")->fetchAll();

// Execute products query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get stats for the header
$totalProducts = $pdo->query("SELECT COUNT(*) FROM products WHERE status != 'deleted'")->fetchColumn();
$activeProducts = $pdo->query("SELECT COUNT(*) FROM products WHERE status = 'active'")->fetchColumn();
$lowStockProducts = $pdo->query("SELECT COUNT(*) FROM products WHERE stock_quantity <= stock_alert AND status = 'active'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Manage Products - HomewareOnTap Admin</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    
    <style>
        :root {
            --primary: #A67B5B;
            --primary-dark: #8B6145;
            --secondary: #F2E8D5;
            --light: #F9F5F0;
            --dark: #3A3229;
            --border: #eadfd4;
            --muted: #6f6258;
            --page-bg: #f8f5f1;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--page-bg);
            color: var(--dark);
            overflow-x: hidden;
        }

        .main-content {
            margin-left: 350px;
            padding: 30px;
            transition: all 0.3s ease;
            background: #f8f5f1;
            min-height: 100vh;
        }

        .top-navbar {
            background: #fff;
            box-shadow: 0 4px 18px rgba(58, 50, 41, 0.08);
            padding: 14px 18px;
            margin-bottom: 22px;
            border-radius: 14px;
            border: 1px solid var(--border);
            position: sticky;
            top: 10px;
            z-index: 20;
        }

        .top-navbar h4 {
            font-weight: 700;
            color: var(--dark);
            font-size: 1.25rem;
        }

        .navbar-toggle {
            background: var(--light);
            border: 1px solid var(--border);
            border-radius: 10px;
            width: 42px;
            height: 42px;
            font-size: 1.15rem;
            color: var(--dark);
            display: none;
            align-items: center;
            justify-content: center;
        }

        .content-section > .d-flex:first-child {
            gap: 12px;
            flex-wrap: wrap;
        }

        .content-section h3 {
            font-weight: 700;
            color: var(--dark);
        }

        .card-dashboard {
            border-radius: 16px;
            border: 1px solid var(--border);
            box-shadow: 0 8px 24px rgba(58, 50, 41, 0.06);
            background: #fff;
            overflow: hidden;
        }

        .card-dashboard .card-body {
            padding: 20px;
        }

        .card-title {
            color: var(--muted);
            font-size: 0.95rem;
            font-weight: 600;
            margin-top: 10px;
        }

        .card-dashboard h2 {
            color: var(--dark);
            margin-bottom: 0;
        }

        .card-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        .bg-primary-light {
            background-color: rgba(166, 123, 91, 0.15);
            color: var(--primary);
        }

        .bg-success-light {
            background-color: rgba(40, 167, 69, 0.13);
            color: #28a745;
        }

        .bg-warning-light {
            background-color: rgba(255, 193, 7, 0.18);
            color: #b58100;
        }

        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
            border-radius: 10px;
            font-weight: 600;
            padding: 0.6rem 1rem;
        }

        .btn-primary:hover,
        .btn-primary:focus {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }

        .btn-outline-secondary,
        .btn-outline-primary,
        .btn-outline-danger,
        .btn-outline-warning,
        .btn-outline-success {
            border-radius: 10px;
            font-weight: 600;
        }

        .form-control,
        .form-select {
            border-radius: 10px;
            border: 1px solid var(--border);
            min-height: 42px;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(166, 123, 91, 0.15);
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            display: inline-block;
            white-space: nowrap;
        }

        .status-active {
            background-color: #d4edda;
            color: #155724;
        }

        .status-inactive {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-lowstock {
            background-color: #f8d7da;
            color: #721c24;
        }

        .product-image {
            width: 58px;
            height: 58px;
            min-width: 58px;
            object-fit: cover;
            border-radius: 12px;
            border: 1px solid var(--border);
        }

        .action-buttons {
            white-space: nowrap;
        }

        .action-buttons .btn {
            padding: 0.4rem 0.55rem;
            font-size: 0.85rem;
            margin: 2px;
        }

        .table-responsive {
            border-radius: 14px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        #productsTable {
            width: 100% !important;
            vertical-align: middle;
        }

        #productsTable thead th {
            background: var(--light);
            color: var(--dark);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }

        #productsTable td {
            vertical-align: middle;
            color: var(--dark);
        }

        .dataTables_wrapper .dataTables_filter input,
        .dataTables_wrapper .dataTables_length select {
            border-radius: 10px;
            border: 1px solid var(--border);
            padding: 6px 10px;
            margin-left: 6px;
        }

        .dataTables_wrapper .row {
            row-gap: 12px;
        }

        .pagination .page-link {
            color: var(--primary);
            border-radius: 8px;
            margin: 0 2px;
        }

        .pagination .active > .page-link {
            background: var(--primary);
            border-color: var(--primary);
            color: #fff;
        }

        /* Tablet view */
        @media (max-width: 1199.98px) {
            .main-content {
                margin-left: 230px;
                padding: 18px;
            }

            .card-dashboard .card-body {
                padding: 18px;
            }
        }

        /* Mobile and small tablet */
        @media (max-width: 991.98px) {
            .main-content {
                margin-left: 0;
                padding: 16px;
            }

            .navbar-toggle {
                display: inline-flex;
            }

            .top-navbar {
                top: 0;
                border-radius: 0 0 14px 14px;
                margin: -16px -16px 18px;
                padding: 14px 16px;
            }

            .top-navbar .dropdown span {
                display: none;
            }
        }

        /* Phone view */
        @media (max-width: 767.98px) {
            .main-content {
                padding: 12px;
            }

            .top-navbar {
                margin: -12px -12px 16px;
            }

            .top-navbar h4 {
                font-size: 1rem;
            }

            .content-section > .d-flex:first-child {
                align-items: stretch !important;
                flex-direction: column;
            }

            .content-section > .d-flex:first-child .btn {
                width: 100%;
            }

            .card-dashboard .card-body {
                padding: 16px;
            }

            .card-title {
                font-size: 0.9rem;
            }

            .card-dashboard h2 {
                font-size: 1.55rem;
            }

            .card-icon {
                width: 44px;
                height: 44px;
                font-size: 1.1rem;
            }

            .table-responsive {
                margin: 0 -4px;
            }

            #productsTable {
                min-width: 760px;
            }

            .dataTables_wrapper .dataTables_length,
            .dataTables_wrapper .dataTables_filter,
            .dataTables_wrapper .dataTables_info,
            .dataTables_wrapper .dataTables_paginate {
                text-align: left !important;
                width: 100%;
                margin-bottom: 10px;
            }

            .dataTables_wrapper .dataTables_filter input {
                width: 100%;
                margin: 8px 0 0 0;
            }
        }

        /* Very small phones */
        @media (max-width: 480px) {
            .main-content {
                padding: 10px;
            }

            .top-navbar {
                margin: -10px -10px 14px;
                padding: 12px;
            }

            .navbar-toggle {
                width: 38px;
                height: 38px;
            }

            .product-image {
                width: 52px;
                height: 52px;
                min-width: 52px;
            }
        }
    </style>
</head>

<body>
    <!-- Include the sidebar -->
    <?php include '../../includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navbar -->
        <nav class="top-navbar">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <button class="navbar-toggle me-3" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h4 class="mb-0">Manage Products</h4>
                </div>
                <div class="dropdown">
                    <a class="dropdown-toggle d-flex align-items-center text-decoration-none" href="#" role="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="https://ui-avatars.com/api/?name=Admin&background=A67B5B&color=fff" alt="Admin" class="rounded-circle me-2" width="32" height="32">
                        <span>Admin</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i>Profile</a></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i>Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/includes/logout.php?admin=1"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Products Content -->
        <div class="content-section" id="productsSection">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="mb-0">Manage Products</h3>
                <a href="add.php" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Add New Product</a>
            </div>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="card card-dashboard h-100">
                        <div class="card-body">
                            <div class="card-icon bg-primary-light">
                                <i class="fas fa-box"></i>
                            </div>
                            <h5 class="card-title">Total Products</h5>
                            <h2 class="fw-bold"><?php echo $totalProducts; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card card-dashboard h-100">
                        <div class="card-body">
                            <div class="card-icon bg-success-light">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h5 class="card-title">Active Products</h5>
                            <h2 class="fw-bold"><?php echo $activeProducts; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card card-dashboard h-100">
                        <div class="card-body">
                            <div class="card-icon bg-warning-light">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <h5 class="card-title">Low Stock</h5>
                            <h2 class="fw-bold"><?php echo $lowStockProducts; ?></h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters and Search -->
            <div class="card card-dashboard mb-4">
                <div class="card-body">
                    <form method="GET" action="">
                        <div class="row">
                            <div class="col-md-3 mb-2">
                                <label for="search" class="form-label">Search</label>
                                <input type="text" class="form-control" id="search" name="search" 
                                       placeholder="Product name or description" value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-3 mb-2">
                                <label for="category" class="form-label">Category</label>
                                <select class="form-select" id="category" name="category">
                                    <option value="all">All Categories</option>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo ($category == $cat['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-2">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="all" <?php echo ($status == 'all') ? 'selected' : ''; ?>>All Status</option>
                                    <option value="active" <?php echo ($status == 'active') ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo ($status == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">Apply Filters</button>
                                <a href="manage.php" class="btn btn-outline-secondary">Reset</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Products Table -->
            <div class="card card-dashboard">
                <div class="card-body">
                    <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>
                    
                    <div class="table-responsive">
                        <table class="table table-hover" id="productsTable">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $product): ?>
                                <tr>
                                    <td>
                                        <?php
                                        $imageUrl = '';

                                        if (!empty($product['image'])) {
                                            // New admin-uploaded images are saved with the full relative path.
                                            if (strpos($product['image'], 'assets/uploads/products/') === 0) {
                                                $imageUrl = SITE_URL . '/' . $product['image'];
                                            }
                                            // Old/manual images are saved as only the filename.
                                            else {
                                                $imageUrl = SITE_URL . '/assets/img/products/primary/' . $product['image'];
                                            }
                                        }
                                        ?>

                                        <?php if (!empty($imageUrl)): ?>
                                            <img src="<?php echo htmlspecialchars($imageUrl); ?>"
                                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                                 class="product-image"
                                                 onerror="this.onerror=null; this.parentElement.innerHTML='<div class=&quot;product-image bg-light d-flex align-items-center justify-content-center&quot;><i class=&quot;fas fa-box-open text-muted&quot;></i></div>';">
                                        <?php else: ?>
                                            <div class="product-image bg-light d-flex align-items-center justify-content-center">
                                                <i class="fas fa-box-open text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-bold"><?php echo htmlspecialchars($product['name']); ?></div>
                                        <small class="text-muted">SKU: <?php echo htmlspecialchars($product['sku']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></td>
                                    <td>R<?php echo number_format($product['price'], 2); ?></td>
                                    <td>
                                        <?php echo $product['stock_quantity']; ?>
                                        <?php if ($product['stock_quantity'] <= $product['stock_alert']): ?>
                                        <span class="badge bg-danger ms-1">Low</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $product['status']; ?>">
                                            <?php echo ucfirst($product['status']); ?>
                                        </span>
                                    </td>
                                    <td class="action-buttons">
                                        <a href="edit.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?action=toggle&id=<?php echo $product['id']; ?>" 
                                           class="btn btn-sm btn-outline-<?php echo ($product['status'] == 'active') ? 'warning' : 'success'; ?>" 
                                           title="<?php echo ($product['status'] == 'active') ? 'Deactivate' : 'Activate'; ?>">
                                            <i class="fas fa-<?php echo ($product['status'] == 'active') ? 'eye-slash' : 'eye'; ?>"></i>
                                        </a>
                                        <a href="?action=delete&id=<?php echo $product['id']; ?>" 
                                           class="btn btn-sm btn-outline-danger" 
                                           onclick="return confirm('Are you sure you want to delete this product?')" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if (empty($products)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No products found.</p>
                        <a href="add.php" class="btn btn-primary">Add Your First Product</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap & jQuery JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#productsTable').DataTable({
                pageLength: 10,
                responsive: false,
                scrollX: true,
                autoWidth: false,
                order: [[1, 'asc']],
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search products..."
                },
                columnDefs: [
                    { orderable: false, targets: [0, 6] }
                ]
            });
            
            // Sidebar toggle functionality
            $('#sidebarToggle').click(function() {
                $('#adminSidebar').toggleClass('active');
                $('#sidebarOverlay').toggle();
                $('body').toggleClass('overflow-hidden');
            });
            
            // Close sidebar when clicking overlay
            $('#sidebarOverlay').click(function() {
                $('#adminSidebar').removeClass('active');
                $(this).hide();
                $('body').removeClass('overflow-hidden');
            });
            
            // Auto-close sidebar on mobile when clicking a link (except dropdown toggles)
            $('.admin-menu .nav-link:not(.has-dropdown)').click(function() {
                if (window.innerWidth < 992) {
                    $('#adminSidebar').removeClass('active');
                    $('#sidebarOverlay').hide();
                    $('body').removeClass('overflow-hidden');
                }
            });
        });
    </script>
</body>
</html>