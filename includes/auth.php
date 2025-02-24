<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function login($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
}

function logout() {
    session_destroy();
    header('Location: login.php');
    exit();
}

function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
} 