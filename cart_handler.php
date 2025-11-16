<?php
/**
 * cart_handler.php - Cart management
 * Central place for all cart operations
 */

require_once __DIR__ . '/session.php';

// Initialize cart
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

/**
 * Add an item to the cart
 */
function add_to_cart($productID, $erpProductUUID, $name, $price, $currency, $quantity = 1) {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    $quantity = (int)$quantity;
    if ($quantity <= 0) $quantity = 1;
    
    // Check if product is already in the cart
    $found = false;
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['productID'] === $productID) {
            $item['quantity'] += $quantity;
            $found = true;
            break;
        }
    }
    
    // If not found, add a new item
    if (!$found) {
        $_SESSION['cart'][] = [
            'productID' => $productID,
            'erpProductUUID' => $erpProductUUID,
            'name' => $name,
            'price' => (float)$price,
            'currency' => $currency,
            'quantity' => $quantity,
            'added_at' => time()
        ];
    }
    
    return true;
}

/**
 * Remove an item from the cart
 */
function remove_from_cart($productID) {
    if (!isset($_SESSION['cart'])) return false;
    
    foreach ($_SESSION['cart'] as $key => $item) {
        if ($item['productID'] === $productID) {
            unset($_SESSION['cart'][$key]);
            $_SESSION['cart'] = array_values($_SESSION['cart']); // Reindex
            return true;
        }
    }
    return false;
}

/**
 * Update the quantity of an item
 */
function update_cart_quantity($productID, $quantity) {
    if (!isset($_SESSION['cart'])) return false;
    
    $quantity = (int)$quantity;
    if ($quantity <= 0) {
        return remove_from_cart($productID);
    }
    
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['productID'] === $productID) {
            $item['quantity'] = $quantity;
            return true;
        }
    }
    return false;
}

/**
 * Clear the cart
 */
function clear_cart() {
    $_SESSION['cart'] = [];
    return true;
}

/**
 * Get the cart
 */
function get_cart() {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    return $_SESSION['cart'];
}

/**
 * Number of items in the cart
 */
function get_cart_count() {
    $cart = get_cart();
    $count = 0;
    foreach ($cart as $item) {
        $count += $item['quantity'];
    }
    return $count;
}

/**
 * Calculate the cart total
 */
function get_cart_total() {
    $cart = get_cart();
    $total = 0;
    foreach ($cart as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    return $total;
}

/**
 * Number of distinct products
 */
function get_cart_items_count() {
    return count(get_cart());
}
