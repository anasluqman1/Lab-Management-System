<?php
require_once __DIR__ . '/config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

function requireRole($roles)
{
    if (!is_array($roles)) {
        $roles = [$roles];
    }
    if (!in_array(getUserRole(), $roles)) {
        redirect('dashboard.php');
    }
}
