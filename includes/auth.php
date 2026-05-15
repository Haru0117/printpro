<?php
session_start();

function check_auth($required_role = null)
{
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../index.html#login");
        exit();
    }

    if ($required_role && $_SESSION['role'] !== $required_role) {
        // Redirect to their respective dashboards if they have the wrong role
        if ($_SESSION['role'] === 'admin') {
            header("Location: ../admin_dashboard.html");
        } else {
            header("Location: ../client_dashboard.html");
        }
        exit();
    }
}
?>