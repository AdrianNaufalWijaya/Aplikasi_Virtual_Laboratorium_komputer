<div class="header">
    <div class="time-display">
        <i class="far fa-clock" style="margin-right: 8px;"></i>
        <span id="currentTime"></span>
    </div>

    <div class="user-menu">
        <div class="user-info" id="userMenuButton"> <div class="admin-badge">ADMIN</div>
            <span>@<?php echo htmlspecialchars($admin_user['username']); ?></span>
            <div class="user-avatar">
                <i class="fas fa-user-shield"></i>
            </div>
        </div>

        <div class="dropdown-menu" id="userDropdown">
            <div class="dropdown-header">
                <h4><?php echo htmlspecialchars($admin_user['full_name']); ?></h4>
                <p>Administrator</p>
            </div>
            <a href="logout_admin.php" class="dropdown-item logout-item">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>
</div>

<script>
    // Fungsi untuk mengupdate jam
    function updateAdminTime() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', hour12: false });
        document.getElementById('currentTime').textContent = timeString;
    }
    updateAdminTime();
    setInterval(updateAdminTime, 1000);

    // Fungsi untuk dropdown menu
    const userMenuButton = document.getElementById('userMenuButton');
    const userDropdown = document.getElementById('userDropdown');

    userMenuButton.addEventListener('click', function(event) {
        event.stopPropagation(); // Mencegah window click event berjalan saat tombol diklik
        userDropdown.classList.toggle('show');
    });

    // Menutup dropdown jika klik di luar area
    window.addEventListener('click', function(event) {
        if (!userMenuButton.contains(event.target) && userDropdown.classList.contains('show')) {
            userDropdown.classList.remove('show');
        }
    });
</script>