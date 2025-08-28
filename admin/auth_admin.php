<?php
// Memulai session di baris paling atas
session_start();

// Cek apakah session user_id ada, session role ada, dan role-nya adalah 'admin'
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    
    // Jika salah satu syarat tidak terpenuhi, alihkan ke halaman login
    header('Location: login_admin.php');
    exit(); // Pastikan skrip berhenti setelah redirect
}

// Jika semua syarat terpenuhi, siapkan variabel $admin_user dari session
// agar bisa digunakan di semua halaman admin (dashboard_admin.php, kelola_pengguna.php, dll.)
$admin_user = $_SESSION;
?>