<?php
/**
 * Test script for Cart flow
 */
require_once 'includes/config/database.php';
require_once 'includes/functions/auth.php';
require_once 'includes/functions/helpers.php';

// Mock a login
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'customer';
$_SESSION['pharmacy_id'] = null;

echo "--- Testing Reservation ---\n";
$_POST['action'] = 'reserve';
$_POST['medicine_id'] = 1;
$_POST['pharmacy_id'] = 1;
$_POST['quantity'] = 1;

include 'ajax_inventory.php';
echo "\n\n";

echo "--- Testing Cart Add ---\n";
unset($_POST['action'], $_GET['action']);
$_POST['action'] = 'add';
$_POST['id'] = 1;
$_POST['name'] = 'Test Drug';
$_POST['price'] = 100;
$_POST['pharmacy_id'] = 1;
$_POST['pharmacy_name'] = 'Test Pharma';
$_POST['quantity'] = 1;

include 'cart.php';
echo "\n\n";

echo "--- Session Cart Content ---\n";
print_r($_SESSION['cart']);
