<?php
session_start();
session_destroy(); // เคลียร์ session ทั้งหมด
header("location: login.php");
exit();
?>