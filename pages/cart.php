<?php
// pages/cart.php - Shopping Cart Page
if (session_status() === PHP_SESSION_NONE) {
    session_name('HOT_SESSION');
    session_start();
}
// Session MUST start first
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

// Initialize database connection
$database = new Database();
$pdo = $database->getConnection();

// Unified login check — covers both session structures
function cart_is_logged_in() {
    return (
        (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true)
        || isset($_SESSION['user_id'])
        || isset($_SESSION['user']['id'])
    ) && isset($_SESSION['last_activity']);
}

$logged_in = cart_is_logged_in();

// Get cart items
$cart_id      = getCurrentCartId($pdo);
$cart_items   = getCartItems($pdo, $cart_id);
$cart_total   = calculateCartTotal($cart_items);
$shipping_cost = calculateShippingCost($cart_total);
$tax_amount   = calculateTaxAmount($cart_total);
$grand_total  = $cart_total + $shipping_cost + $tax_amount;

// Get user addresses if logged in
$user_addresses = [];
if ($logged_in) {
    $user_id = get_current_user_id();
    $user_addresses = getUserAddresses($pdo, $user_id);
}

// Checkout redirect logic
$require_login = false;
if (isset($_GET['checkout']) && $_GET['checkout'] == '1' && !$logged_in) {
    $require_login = true;
    $_SESSION['redirect_after_login'] = SITE_URL . '/pages/cart.php?checkout=1';
}

// CartController URL relative to this page
$cart_controller = SITE_URL . '/system/controllers/CartController.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - HomewareOnTap</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@400;500;600;700&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <style>
        :root {
            --primary: #A67B5B;
            --secondary: #F2E8D5;
            --light: #F9F5F0;
            --dark: #3A3229;
        }
        .cart-hero { background-color: var(--light); padding: 40px 0; margin-bottom: 40px; }
        .cart-table { width: 100%; border-collapse: collapse; }
        .cart-table th { background-color: var(--light); padding: 15px; text-align: left; font-weight: 600; border-bottom: 2px solid #ddd; }
        .cart-table td { padding: 20px 15px; border-bottom: 1px solid #eee; vertical-align: middle; }
        .cart-product { display: flex; align-items: center; }
        .cart-product-img { width: 100px; height: 100px; border-radius: 8px; overflow: hidden; margin-right: 15px; flex-shrink: 0; }
        .cart-product-img img { width: 100%; height: 100%; object-fit: cover; }
        .cart-product-info h4 { margin-bottom: 5px; font-size: 18px; }
        .cart-product-info p { color: #777; margin-bottom: 0; }
        .quantity-selector { display: flex; align-items: center; }
        .qty-btn { width: 35px; height: 35px; background-color: var(--light); border: 1px solid #ddd; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 16px; font-weight: 500; user-select: none; }
        .qty-input { width: 50px; height: 35px; text-align: center; border: 1px solid #ddd; border-left: none; border-right: none; }
        .cart-price { font-weight: 600; color: var(--primary); font-size: 18px; }
        .cart-remove { color: #dc3545; background: none; border: none; font-size: 18px; cursor: pointer; transition: color 0.3s; }
        .cart-remove:hover { color: #c82333; }
        .cart-summary { background-color: var(--light); border-radius: 8px; padding: 25px; margin-bottom: 30px; }
        .summary-item { display: flex; justify-content: space-between; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #eee; }
        .summary-total { font-weight: 700; font-size: 20px; color: var(--primary); }
        .coupon-form { display: flex; margin-bottom: 20px; }
        .coupon-input { flex-grow: 1; margin-right: 10px; }
        .empty-cart { text-align: center; padding: 60px 0; }
        .empty-cart-icon { font-size: 80px; color: #ddd; margin-bottom: 20px; }
        .login-prompt { background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; padding: 20px; margin-bottom: 20px; }
        .toast-container { z-index: 1090; }
        .address-management { margin-top: 40px; padding: 20px; background-color: var(--light); border-radius: 8px; }
        .address-card { border: 1px solid #ddd; border-radius: 8px; padding: 15px; margin-bottom: 15px; cursor: pointer; transition: all 0.3s ease; }
        .address-card:hover { border-color: var(--primary); }
        .address-card.selected { border-color: var(--primary); background-color: rgba(166,123,91,0.1); }
        .add-address-btn { border: 2px dashed #ddd; border-radius: 8px; padding: 30px 15px; text-align: center; cursor: pointer; transition: all 0.3s ease; }
        .add-address-btn:hover { border-color: var(--primary); color: var(--primary); }
        .security-note { margin-top: 20px; padding: 15px; background-color: #f8f9fa; border-radius: 8px; text-align: center; }
        .payment-methods img { margin: 0 5px; }
        .cart-product { transition: all 0.3s ease; }
        .cart-product.removing { opacity: 0; transform: translateX(-100%); }
        @media (max-width: 768px) {
            .cart-table thead { display: none; }
            .cart-table tr { display: block; margin-bottom: 20px; border: 1px solid #eee; border-radius: 8px; padding: 15px; }
            .cart-table td { display: block; text-align: center; padding: 10px; border-bottom: none; }
            .cart-product { flex-direction: column; text-align: center; }
            .cart-product-img { margin-right: 0; margin-bottom: 15px; }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <section class="cart-hero">
        <div class="container">
            <h1>Shopping Cart</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb justify-content-center">
                    <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                    <li class="breadcrumb-item active">Shopping Cart</li>
                </ol>
            </nav>
        </div>
    </section>

    <div class="container">
        <div class="toast-container position-fixed bottom-0 end-0 p-3"></div>

        <?php if ($require_login): ?>
        <div class="login-prompt">
            <div class="d-flex align-items-center">
                <div class="flex-grow-1">
                    <h4 class="mb-1"><i class="fas fa-exclamation-triangle text-warning me-2"></i> Login Required</h4>
                    <p class="mb-0">Please log in to proceed with checkout.</p>
                </div>
                <div>
                    <a href="<?php echo SITE_URL; ?>/pages/auth/login.php" class="btn btn-primary">Login Now</a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (count($cart_items) > 0): ?>
        <div class="row">
            <!-- Cart Items -->
            <div class="col-lg-8">
                <div class="table-responsive">
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Total</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cart_items as $item): ?>
                            <tr id="cart-row-<?php echo $item['id']; ?>">
                                <td>
                                    <div class="cart-product">
                                        <div class="cart-product-img">
                                            <img src="<?php echo SITE_URL; ?>/assets/img/products/primary/<?php echo !empty($item['image']) ? htmlspecialchars($item['image']) : 'default-product.jpg'; ?>"
                                                 alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                 onerror="this.src='<?php echo SITE_URL; ?>/assets/img/products/primary/default-product.jpg'">
                                        </div>
                                        <div class="cart-product-info">
                                            <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                            <p>SKU: <?php echo htmlspecialchars($item['sku'] ?? 'N/A'); ?></p>
                                            <?php if (isset($item['stock_quantity']) && $item['stock_quantity'] < $item['quantity']): ?>
                                            <p class="text-danger small">Only <?php echo $item['stock_quantity']; ?> available</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="cart-price" id="unit-price-<?php echo $item['id']; ?>">R<?php echo number_format($item['price'], 2); ?></td>
                                <td>
                                    <div class="quantity-selector">
                                        <div class="qty-btn" onclick="updateQuantity(<?php echo $item['id']; ?>, <?php echo $item['quantity'] - 1; ?>)">-</div>
                                        <input type="number" class="qty-input"
                                               id="qty-<?php echo $item['id']; ?>"
                                               value="<?php echo $item['quantity']; ?>"
                                               min="1"
                                               data-item-id="<?php echo $item['id']; ?>"
                                               data-price="<?php echo $item['price']; ?>"
                                               onchange="updateQuantityInput(this)"
                                               onfocus="this.dataset.oldValue = this.value">
                                        <div class="qty-btn" onclick="updateQuantity(<?php echo $item['id']; ?>, <?php echo $item['quantity'] + 1; ?>)">+</div>
                                    </div>
                                </td>
                                <td class="cart-price" id="line-total-<?php echo $item['id']; ?>">R<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                <td>
                                    <button class="cart-remove" onclick="removeFromCart(<?php echo $item['id']; ?>)">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="coupon-form">
                            <input type="text" class="form-control coupon-input" placeholder="Coupon code" id="couponCode">
                            <button class="btn btn-outline-secondary" onclick="applyCoupon()">Apply</button>
                        </div>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <a href="shop.php" class="btn btn-outline-secondary">Continue Shopping</a>
                    </div>
                </div>

                <?php if ($logged_in): ?>
                <div class="address-management mt-5">
                    <h3 class="section-title">Shipping Address</h3>
                    <div class="row">
                        <?php if (count($user_addresses) > 0): ?>
                            <?php foreach ($user_addresses as $address): ?>
                            <div class="col-md-6 mb-3">
                                <div class="address-card" onclick="selectAddress(this)">
                                    <input type="radio" name="shipping_address" value="<?php echo $address['id']; ?>" style="display:none;">
                                    <h5><?php echo htmlspecialchars($address['first_name'] . ' ' . $address['last_name']); ?></h5>
                                    <p>
                                        <?php echo htmlspecialchars($address['street']); ?><br>
                                        <?php echo htmlspecialchars($address['city']); ?>, <?php echo htmlspecialchars($address['province']); ?><br>
                                        <?php echo htmlspecialchars($address['postal_code']); ?><br>
                                        <?php echo htmlspecialchars($address['country']); ?>
                                    </p>
                                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($address['phone']); ?></p>
                                    <?php if ($address['is_default']): ?>
                                        <span class="badge bg-primary">Default</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12">
                                <div class="alert alert-info">No addresses saved yet. Add one to speed up checkout.</div>
                            </div>
                        <?php endif; ?>
                        <div class="col-md-6 mb-3">
                            <div class="add-address-btn" data-bs-toggle="modal" data-bs-target="#addAddressModal">
                                <i class="fas fa-plus-circle fa-2x mb-2"></i>
                                <p>Add New Address</p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Cart Summary -->
            <div class="col-lg-4">
                <div class="cart-summary">
                    <h3 class="mb-4">Cart Summary</h3>
                    <div class="summary-item">
                        <span>Subtotal</span>
                        <span id="summary-subtotal">R<?php echo number_format($cart_total, 2); ?></span>
                    </div>
                    <div class="summary-item">
                        <span>Shipping</span>
                        <span id="summary-shipping">R<?php echo number_format($shipping_cost, 2); ?></span>
                    </div>
                    <div class="summary-item">
                        <span>Tax (VAT)</span>
                        <span id="summary-tax">R<?php echo number_format($tax_amount, 2); ?></span>
                    </div>
                    <div class="summary-item" id="discount-row" style="display:none;">
                        <span>Discount</span>
                        <span id="discount-amount">R0.00</span>
                    </div>
                    <div class="summary-item summary-total">
                        <span>Total</span>
                        <span id="summary-total">R<?php echo number_format($grand_total, 2); ?></span>
                    </div>

                    <?php if ($logged_in): ?>
                        <a href="<?php echo SITE_URL; ?>/pages/account/checkout.php" 
                        class="btn btn-primary w-100 mt-3">
                            <i class="fas fa-lock me-2"></i>Proceed to Checkout
                        </a>
                    <?php else: ?>
                        <?php $_SESSION['redirect_after_login'] = SITE_URL . '/pages/account/checkout.php'; ?>
                        <a href="<?php echo SITE_URL; ?>/pages/auth/login.php" 
                        class="btn btn-primary w-100 mt-3">
                            <i class="fas fa-sign-in-alt me-2"></i>Login to Checkout
                        </a>
                        <p class="text-center mt-2 small text-muted">
                            You need to be logged in to complete your purchase.
                        </p>
                    <?php endif; ?>
                </div>

                <div class="security-note">
                    <p><i class="fas fa-lock me-2"></i> Secure checkout. All transactions are encrypted.</p>
                    <div class="payment-methods">
                        <img src="<?php echo SITE_URL; ?>/assets/img/icons/visa.png" alt="Visa" height="30" class="me-2">
                        <img src="<?php echo SITE_URL; ?>/assets/img/icons/mastercard.png" alt="Mastercard" height="30" class="me-2">
                        <img src="<?php echo SITE_URL; ?>/assets/img/icons/payfast.png" alt="PayFast" height="30">
                    </div>
                </div>
            </div>
        </div>

        <?php else: ?>
        <div class="empty-cart">
            <div class="empty-cart-icon"><i class="fas fa-shopping-cart"></i></div>
            <h2>Your cart is empty</h2>
            <p class="mb-4">Looks like you haven't added any items yet.</p>
            <a href="shop.php" class="btn btn-primary">Continue Shopping</a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Add Address Modal -->
    <div class="modal fade" id="addAddressModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Address</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control" id="first_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="last_name" required>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Street Address</label>
                            <input type="text" class="form-control" id="street" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">City</label>
                            <input type="text" class="form-control" id="city" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Province</label>
                            <input type="text" class="form-control" id="province" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Postal Code</label>
                            <input type="text" class="form-control" id="postal_code" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Country</label>
                            <select class="form-select" id="country" required>
                                <option value="">Select Country</option>
                                <option value="South Africa" selected>South Africa</option>
                                <option value="United States">United States</option>
                                <option value="United Kingdom">United Kingdom</option>
                            </select>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" required>
                        </div>
                        <div class="col-12 mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="set_default">
                                <label class="form-check-label" for="set_default">Set as default address</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveAddressFromCart()">Save Address</button>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>

    <script>
        const CART_URL = '<?php echo $cart_controller; ?>';

        $(document).ready(function() {
            $('[data-bs-toggle="tooltip"]').tooltip();
            updateCartCount();
        });

        // ── Update quantity via +/- buttons ──────────────────────────────
        function updateQuantity(itemId, newQty) {
            if (newQty < 1) newQty = 1;
            const $input = $('#qty-' + itemId);
            $input.val(newQty).prop('disabled', true);

            $.ajax({
                url: CART_URL,
                method: 'POST',
                dataType: 'json',
                data: { action: 'update_cart_quantity', cart_item_id: itemId, quantity: newQty },
                success: function(data) {
                    $input.prop('disabled', false);
                    if (data.success) {
                        const price = parseFloat($input.data('price'));
                        $('#line-total-' + itemId).text('R' + (price * newQty).toFixed(2));
                        updateSummary(data);
                        updateCartCount();
                        showToast('Cart updated!', 'success');
                    } else {
                        showToast(data.message || 'Error updating cart', 'error');
                        $input.val($input.data('oldValue') || 1);
                    }
                },
                error: function() {
                    $input.prop('disabled', false);
                    showToast('Network error updating cart', 'error');
                }
            });
        }

        // ── Update quantity via typing in the input ───────────────────────
        function updateQuantityInput(input) {
            const $input = $(input);
            const itemId = $input.data('item-id');
            const newQty = parseInt($input.val());
            if (isNaN(newQty) || newQty < 1) {
                $input.val(1);
                updateQuantity(itemId, 1);
            } else {
                updateQuantity(itemId, newQty);
            }
        }

        // ── Remove item ───────────────────────────────────────────────────
        function removeFromCart(itemId) {
            if (!confirm('Remove this item from your cart?')) return;

            $.ajax({
                url: CART_URL,
                method: 'POST',
                dataType: 'json',
                data: { action: 'remove_from_cart', cart_item_id: itemId },
                success: function(data) {
                    if (data.success) {
                        $('#cart-row-' + itemId).fadeOut(300, function() {
                            $(this).remove();
                            updateSummary(data);
                            updateCartCount();
                            if (data.cart_count === 0) {
                                setTimeout(() => location.reload(), 400);
                            }
                        });
                        showToast('Item removed', 'info');
                    } else {
                        showToast(data.message || 'Error removing item', 'error');
                    }
                },
                error: function() {
                    showToast('Network error removing item', 'error');
                }
            });
        }

        // ── Update the summary panel numbers ─────────────────────────────
        function updateSummary(data) {
            if (data.cart_total   !== undefined) $('#summary-subtotal').text('R' + parseFloat(data.cart_total).toFixed(2));
            if (data.shipping_cost !== undefined) $('#summary-shipping').text('R' + parseFloat(data.shipping_cost).toFixed(2));
            if (data.tax_amount   !== undefined) $('#summary-tax').text('R' + parseFloat(data.tax_amount).toFixed(2));
            if (data.grand_total  !== undefined) $('#summary-total').text('R' + parseFloat(data.grand_total).toFixed(2));
        }

        // ── Update header cart badge ──────────────────────────────────────
        function updateCartCount() {
            $.ajax({
                url: CART_URL,
                method: 'POST',
                dataType: 'json',
                data: { action: 'get_cart_count' },
                success: function(data) {
                    if (data && data.success) {
                        $('.cart-badge, .cart-count-badge, .cart-count').text(data.count);
                    }
                }
            });
        }

        // ── Apply coupon ──────────────────────────────────────────────────
        function applyCoupon() {
            const code = $('#couponCode').val().trim();
            if (!code) { showToast('Please enter a coupon code', 'error'); return; }

            $.ajax({
                url: CART_URL,
                method: 'POST',
                dataType: 'json',
                data: { action: 'apply_coupon', coupon_code: code },
                success: function(data) {
                    if (data.success) {
                        $('#discount-row').show();
                        $('#discount-amount').text('R' + parseFloat(data.discount_amount).toFixed(2));
                        updateSummary(data);
                        showToast('Coupon applied!', 'success');
                    } else {
                        showToast(data.message || 'Invalid coupon', 'error');
                    }
                },
                error: function() { showToast('Error applying coupon', 'error'); }
            });
        }

        // ── Save new address from modal ───────────────────────────────────
        function saveAddressFromCart() {
            <?php if (!$logged_in): ?>
                alert('Please login to save addresses'); return;
            <?php endif; ?>

            const fields = ['first_name','last_name','street','city','province','postal_code','country','phone'];
            const formData = { action: 'add_address', type: 'shipping' };
            for (const f of fields) {
                const val = document.getElementById(f).value.trim();
                if (!val) { alert('Please fill in all required fields'); return; }
                formData[f] = val;
            }
            formData.set_default = document.getElementById('set_default').checked ? 1 : 0;

            $.ajax({
                url: '<?php echo SITE_URL; ?>/system/controllers/AddressController.php',
                type: 'POST',
                dataType: 'json',
                data: formData,
                success: function(data) {
                    if (data.success) {
                        bootstrap.Modal.getInstance(document.getElementById('addAddressModal')).hide();
                        showToast('Address saved!', 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showToast('Error: ' + (data.message || 'Could not save address'), 'error');
                    }
                },
                error: function() { showToast('Network error saving address', 'error'); }
            });
        }

        // ── Select address card ───────────────────────────────────────────
        function selectAddress(card) {
            document.querySelectorAll('.address-card').forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');
            const radio = card.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;
        }

        // ── Toast notification ────────────────────────────────────────────
        function showToast(message, type = 'success') {
            const id = 'toast-' + Date.now();
            const bg = { success: 'text-bg-success', error: 'text-bg-danger', info: 'text-bg-info', warning: 'text-bg-warning' }[type] || 'text-bg-secondary';
            const icon = { success: 'fa-check-circle', error: 'fa-exclamation-circle', info: 'fa-info-circle', warning: 'fa-exclamation-triangle' }[type] || 'fa-bell';
            const html = `
                <div id="${id}" class="toast align-items-center ${bg} border-0" role="alert">
                    <div class="d-flex">
                        <div class="toast-body"><i class="fas ${icon} me-2"></i>${message}</div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>`;
            $('.toast-container').append(html);
            const el = document.getElementById(id);
            const toast = new bootstrap.Toast(el, { autohide: true, delay: 4000 });
            toast.show();
            el.addEventListener('hidden.bs.toast', () => el.remove());
        }
    </script>
</body>
</html>