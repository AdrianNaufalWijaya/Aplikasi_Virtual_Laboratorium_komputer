<div class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-title">Admin Panel</div>
    </div>
    
    <?php 
        if (!isset($current_page)) {
            $current_page = basename($_SERVER['SCRIPT_NAME']);
        }
    ?>

    <a href="dashboard_admin.php" class="menu-item <?php echo ($current_page == 'dashboard_admin.php') ? 'active' : ''; ?>">
        <i class="fas fa-chart-pie"></i>
        Monitor Sesi
    </a>
    <a href="notifikasi.php" class="menu-item <?php echo ($current_page == 'notifikasi.php') ? 'active' : ''; ?>">
        <i class="fas fa-bell"></i>
        Kirim Notifikasi
    </a>
    <a href="kelola_pengguna.php" class="menu-item <?php echo ($current_page == 'kelola_pengguna.php') ? 'active' : ''; ?>">
        <i class="fas fa-users"></i>
        Kelola Pengguna
    </a>
    <a href="kelola_matakuliah.php" class="menu-item <?php echo ($current_page == 'kelola_matakuliah.php') ? 'active' : ''; ?>">
        <i class="fas fa-book"></i>
        Kelola Mata Kuliah
    </a>
    <a href="kelola_lab.php" class="menu-item <?php echo ($current_page == 'kelola_lab.php') ? 'active' : ''; ?>">
        <i class="fas fa-desktop"></i>
        Kelola Laboratorium
    </a>
    <a href="kelola_software.php" class="menu-item <?php echo ($current_page == 'kelola_software.php') ? 'active' : ''; ?>">
        <i class="fas fa-cog"></i>
        Kelola Software
    </a>

    </div>