<?php
/* Logout (POST only, with CSRF token - a link could be triggered by other sites) */
require __DIR__ . '/../../app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/');
}
verify_csrf();
logout();
redirect('/admin/login.php');
