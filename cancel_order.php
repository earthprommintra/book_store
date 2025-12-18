<?php
session_start();

// ตรวจสอบสิทธิ์ member
if (!isset($_SESSION['UID']) || $_SESSION['Role'] != 'member') {
    header("location: login.php");
    exit();
}

// เชื่อมต่อฐานข้อมูล
$hostname = "localhost";
$database = "u299560388.2568gp23";
$username = "root";
$password = "";

$conn = mysqli_connect($hostname, $username, $password, $database);
mysqli_set_charset($conn, "utf8");

if (!$conn) {
    die("เชื่อมต่อฐานข้อมูลไม่สำเร็จ: " . mysqli_connect_error());
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['OID'])) {
    $oid = intval($_POST['OID']);
    $uid = $_SESSION['UID'];

    // ตรวจสอบว่า order นี้เป็นของ user และสถานะ pending
    $sql_check = "SELECT * FROM orders WHERE OID=$oid AND UID=$uid AND status='pending'";
    $result = mysqli_query($conn, $sql_check);

    if (mysqli_num_rows($result) > 0) {
        // อัปเดตสถานะเป็น cancelled
        $sql_update = "UPDATE orders SET status='cancelled' WHERE OID=$oid";
        if (mysqli_query($conn, $sql_update)) {
            $_SESSION['msg'] = "✅ ยกเลิกคำสั่งซื้อเรียบร้อยแล้ว (Order ID: $oid)";
        } else {
            $_SESSION['msg'] = "❌ เกิดข้อผิดพลาด: " . mysqli_error($conn);
        }
    } else {
        $_SESSION['msg'] = "⚠️ ไม่สามารถยกเลิกคำสั่งซื้อนี้ได้ (อาจไม่ใช่ของคุณหรือสถานะไม่ใช่ pending)";
    }
}

// กลับไปหน้า my_orders.php
header("location: my_orders.php");
exit();
?>
