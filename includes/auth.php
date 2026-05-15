<?php
session_start();

function check_auth($required_role = null)
{
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../index.html#login");
        exit();
    }

    $current_role = strtolower($_SESSION['role']);
    
    if ($required_role && $current_role !== strtolower($required_role)) {
        // Redirect to their respective dashboards if they have the wrong role
        if ($current_role === 'admin' || $current_role === 'super_admin') {
            header("Location: ../admin_dashboard.html");
        } else {
            header("Location: ../client_dashboard.html");
        }
        exit();
    }
}

function is_admin() {
    if (!isset($_SESSION['role'])) return false;
    $role = strtolower($_SESSION['role']);
    return ($role === 'admin' || $role === 'super_admin');
}
?>