<?php
// payment_callback.php
// Handle payment callbacks from Midtrans

require_once 'config.php';
require_once 'payment_gateway.php';

// Initialize payment gateway
$paymentGateway = new PaymentGateway();

// Check if order_id and status are provided
if (!isset($_GET['order_id']) || !isset($_GET['status'])) {
    http_response_code(400);
    exit('Bad Request: Missing required parameters');
}

$orderId = $_GET['order_id'];
$status = $_GET['status'];

// Verify the payment status
$payment = $paymentGateway->checkPaymentStatus($orderId);

if ($payment['status'] === 'not_found') {
    http_response_code(404);
    exit('Payment not found');
}

// Handle different callback statuses
switch ($status) {
    case 'finish':
        // Payment is completed or pending confirmation
        // Redirect user to view profile if payment is completed
        if ($payment['status'] === 'completed') {
            redirect('view_profile.php?id=' . $payment['target_user_id'] . '&from_payment=1&new=1');
        } else {
            // Payment is still being processed
            redirect('dashboard.php?page=payments&pending=' . $orderId);
        }
        break;
        
    case 'pending':
        // Payment is pending
        redirect('dashboard.php?page=payments&pending=' . $orderId);
        break;
        
    case 'error':
        // Payment failed
        redirect('dashboard.php?page=payments&failed=' . $orderId);
        break;
        
    default:
        // Unknown status
        redirect('dashboard.php?page=payments');
        break;
}

// Function to redirect
function redirect($url) {
    header('Location: ' . $url);
    exit();
}