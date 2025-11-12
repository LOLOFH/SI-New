<?php 
require_once __DIR__.'/session.php';



// Initialize shopping cart if empty
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PHP Webshop</title>
    <link rel="stylesheet" href="/styles.css">
</head>
<body>
<header>
    <h1><a href="/index.php">PHP Webshop</a></h1>
</header>
<main>
