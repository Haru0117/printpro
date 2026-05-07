<?php
session_start();

function check_auth($required_role = null)
{
    if (!isset($_SESSION['user_id'])) {
        header("Location: /login.html");
        exit();
    }

    if ($required_role && $_SESSION['role'] !== $required_role) {
        // Redirect to their respective dashboards if they have the wrong role
        if ($_SESSION['role'] === 'admin') {
            header("Location: /Admin Dashboard.html");
        } else {
            header("Location: /Client Dashboard.html");
        }
        exit();
    }
}
?>