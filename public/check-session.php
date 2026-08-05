<?php
session_start();
header('Content-Type: application/json');

echo json_encode([
    'session_id' => session_id(),
    'user_id' => $_SESSION['user_id'] ?? null,
    'user_name' => $_SESSION['user_name'] ?? null,
    'user_role' => $_SESSION['user_role'] ?? null,
    'authenticated' => $_SESSION['authenticated'] ?? false,
    'cookies' => $_COOKIE
]);