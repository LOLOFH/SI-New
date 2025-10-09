<?php
require_once 'db.php';

function registerCustomer($name, $email, $address, $password) {
    global $conn;
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO customers (name, email, address, password) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $address, $hashedPassword);
    
    if ($stmt->execute()) {
        return true;
    } else {
        return false;
    }
}

function loginCustomer($email, $password) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT password FROM customers WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($hashedPassword);
        $stmt->fetch();
        
        if (password_verify($password, $hashedPassword)) {
            return true;
        }
    }
    return false;
}

function updateCustomer($customer_id, $name, $email, $address) {
    global $conn;
    
    $stmt = $conn->prepare("UPDATE customers SET name = ?, email = ?, address = ? WHERE customer_id = ?");
    $stmt->bind_param("sssi", $name, $email, $address, $customer_id);
    
    return $stmt->execute();
}

function getCustomerById($customer_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT * FROM customers WHERE customer_id = ?");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    
    return $stmt->get_result()->fetch_assoc();
}
?>