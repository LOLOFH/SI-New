<?php
/**
 * cart_handler.php - Warenkorb-Verwaltung
 * Zentrale Stelle für alle Warenkorb-Operationen
 */

require_once __DIR__ . '/session.php';

// Warenkorb initialisieren
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

/**
 * Artikel zum Warenkorb hinzufügen
 */
function add_to_cart($productID, $erpProductUUID, $name, $price, $currency, $quantity = 1) {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    $quantity = (int)$quantity;
    if ($quantity <= 0) $quantity = 1;
    
    // Prüfe, ob Produkt bereits im Warenkorb
    $found = false;
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['productID'] === $productID) {
            $item['quantity'] += $quantity;
            $found = true;
            break;
        }
    }
    
    // Wenn nicht gefunden, neuen Artikel hinzufügen
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
 * Artikel aus dem Warenkorb entfernen
 */
function remove_from_cart($productID) {
    if (!isset($_SESSION['cart'])) return false;
    
    foreach ($_SESSION['cart'] as $key => $item) {
        if ($item['productID'] === $productID) {
            unset($_SESSION['cart'][$key]);
            $_SESSION['cart'] = array_values($_SESSION['cart']); // Reindexieren
            return true;
        }
    }
    return false;
}

/**
 * Menge eines Artikels aktualisieren
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
 * Warenkorb leeren
 */
function clear_cart() {
    $_SESSION['cart'] = [];
    return true;
}

/**
 * Warenkorb abrufen
 */
function get_cart() {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    return $_SESSION['cart'];
}

/**
 * Anzahl der Artikel im Warenkorb
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
 * Gesamtsumme des Warenkorbs berechnen
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
 * Anzahl unterschiedlicher Produkte
 */
function get_cart_items_count() {
    return count(get_cart());
}
