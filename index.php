<?php

// Mengarahkan browser ke halaman login.php
header("Location: login.php");

// Penting: Hentikan eksekusi skrip setelah melakukan redirect untuk memastikan tidak ada kode lain yang berjalan.
exit();
?>