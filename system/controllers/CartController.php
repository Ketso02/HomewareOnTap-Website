<?php
// system/controllers/CartController.php
if (session_status() === PHP_SESSION_NONE) {
    session_name('HOT_SESSION');
    session_start();
}
// 1. Buffer ALL output so stray whitespace/errors never corrupt JSON
ob_start();

// 2. Start session FIRST before any other code
if (session_status() === PHP_SESSION_NONE) {
    session_name('HOT_SESSION');
    session_start();
}

// 3. Now set headers and error reporting
header('Content-Type: application/json');
ini_set('display_errors', 0); // Never show PHP errors as HTML in a JSON endpoint
error_reporting(E_ALL);

// 4. Load dependencies using hardcoded MAMP path
$base_dir = '/Applications/MAMP/htdocs/homewareontap';
require_once $base_dir . '/includes/config.php';
require_once $base_dir . '/includes/database.php';
require_once $base_dir . '/includes/functions.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();

    if (!$pdo) {
        throw new Exception('Database connection failed');
    }

    $action = $_POST['action'] ?? '';

    // Discard any buffered output before sending JSON
    ob_clean();

    switch ($action) {
        case 'add_to_cart':
            handleAddToCart($pdo);
            break;
        case 'get_cart_count':
            handleGetCartCount($pdo);
            break;
        case 'update_cart_quantity':
            handleUpdateCartQuantity($pdo);
            break;
        case 'remove_from_cart':
            handleRemoveFromCart($pdo);
            break;
        case 'get_cart_items':
            handleGetCartItems($pdo);
            break;
        case 'apply_coupon':
            handleApplyCoupon($pdo);
            break;
        case 'sync_cart':
            // Called by cart.js syncWithServer() — just acknowledge
            echo json_encode(['success' => true, 'message' => 'Cart synced']);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action: ' . $action]);
    }

} catch (Exception $e) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

// =========================================================
// Add to Cart
// =========================================================
function handleAddToCart($pdo) {
    $product_id = intval($_POST['product_id'] ?? 0);
    $quantity   = intval($_POST['quantity'] ?? 1);

    if ($product_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
        return;
    }

    // Check product exists and is active
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND status = 1");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not found or unavailable']);
        return;
    }

    // Get or create cart
    $cart_id = getCurrentCartId($pdo);
    if (!$cart_id) {
        $cart_id = createCart($pdo);
    }

    if (!$cart_id) {
        echo json_encode(['success' => false, 'message' => 'Could not create cart']);
        return;
    }

    // Add or update item
    $existing = getCartItem($pdo, $cart_id, $product_id);

    if ($existing) {
        $new_qty = $existing['quantity'] + $quantity;
        $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?")
            ->execute([$new_qty, $existing['id']]);
        $result_action = 'updated';
    } else {
        $pdo->prepare("INSERT INTO cart_items (cart_id, product_id, quantity, price) VALUES (?, ?, ?, ?)")
            ->execute([$cart_id, $product_id, $quantity, $product['price']]);
        $result_action = 'added';
    }

    $cart_count = getCartItemCount($pdo, $cart_id);

    echo json_encode([
        'success'    => true,
        'message'    => 'Product added to cart!',
        'cart_count' => (int) $cart_count,
        'action'     => $result_action
    ]);
}

// =========================================================
// Get Cart Count
// =========================================================
function handleGetCartCount($pdo) {
    $cart_id = getCurrentCartId($pdo);
    $count   = $cart_id ? (int) getCartItemCount($pdo, $cart_id) : 0;

    echo json_encode([
        'success' => true,
        'count'   => $count
    ]);
}

// =========================================================
// Update Cart Quantity
// =========================================================
function handleUpdateCartQuantity($pdo) {
    $cart_item_id = intval($_POST['cart_item_id'] ?? 0);
    $quantity     = intval($_POST['quantity'] ?? 1);

    if ($cart_item_id <= 0 || $quantity < 1) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
        return;
    }

    $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?")
        ->execute([$quantity, $cart_item_id]);

    $cart_id      = getCurrentCartId($pdo);
    $cart_items   = getCartItems($pdo, $cart_id);
    $cart_total   = calculateCartTotal($cart_items);
    $shipping     = calculateShippingCost($cart_total);
    $tax          = calculateTaxAmount($cart_total);
    $grand_total  = $cart_total + $shipping + $tax;
    $cart_count   = (int) getCartItemCount($pdo, $cart_id);

    echo json_encode([
        'success'       => true,
        'message'       => 'Cart updated',
        'cart_total'    => $cart_total,
        'shipping_cost' => $shipping,
        'tax_amount'    => $tax,
        'grand_total'   => $grand_total,
        'cart_count'    => $cart_count
    ]);
}

// =========================================================
// Remove From Cart
// =========================================================
function handleRemoveFromCart($pdo) {
    $cart_item_id = intval($_POST['cart_item_id'] ?? 0);

    if ($cart_item_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid cart item ID']);
        return;
    }

    $pdo->prepare("DELETE FROM cart_items WHERE id = ?")
        ->execute([$cart_item_id]);

    $cart_id     = getCurrentCartId($pdo);
    $cart_items  = getCartItems($pdo, $cart_id);
    $cart_total  = calculateCartTotal($cart_items);
    $shipping    = calculateShippingCost($cart_total);
    $tax         = calculateTaxAmount($cart_total);
    $grand_total = $cart_total + $shipping + $tax;
    $cart_count  = (int) getCartItemCount($pdo, $cart_id);

    echo json_encode([
        'success'       => true,
        'message'       => 'Item removed',
        'cart_count'    => $cart_count,
        'cart_total'    => $cart_total,
        'shipping_cost' => $shipping,
        'tax_amount'    => $tax,
        'grand_total'   => $grand_total
    ]);
}

// =========================================================
// Get Cart Items (used by the offcanvas drawer on shop.php)
// =========================================================
function handleGetCartItems($pdo) {
    $cart_id = getCurrentCartId($pdo);

    if (!$cart_id) {
        echo json_encode([
            'success'  => true,
            'items'    => [],
            'subtotal' => 0
        ]);
        return;
    }

    $cart_items = getCartItems($pdo, $cart_id);
    $subtotal   = calculateCartTotal($cart_items);

    echo json_encode([
        'success'  => true,
        'items'    => $cart_items,
        'subtotal' => $subtotal
    ]);
}

// =========================================================
// Apply Coupon
// =========================================================
function handleApplyCoupon($pdo) {
    $coupon_code = trim($_POST['coupon_code'] ?? '');

    if (empty($coupon_code)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a coupon code']);
        return;
    }

    $cart_id    = getCurrentCartId($pdo);
    $cart_items = getCartItems($pdo, $cart_id);
    $cart_total = calculateCartTotal($cart_items);

    // 10% discount — replace with real coupons table lookup when ready
    $discount    = $cart_total * 0.10;
    $new_total   = $cart_total - $discount;
    $shipping    = calculateShippingCost($new_total);
    $tax         = calculateTaxAmount($new_total);
    $grand_total = $new_total + $shipping + $tax;

    echo json_encode([
        'success'         => true,
        'message'         => 'Coupon applied! 10% discount.',
        'discount_amount' => $discount,
        'cart_total'      => $new_total,
        'shipping_cost'   => $shipping,
        'tax_amount'      => $tax,
        'grand_total'     => $grand_total
    ]);
}
?>