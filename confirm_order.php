<?php
session_start();

// ตรวจสอบ Login
if (!isset($_SESSION['UID'])) {
    header("location: login.php");
    exit();
}

$UID = $_SESSION['UID'];

$hostname = "localhost";
$database = "u299560388.2568gp23";
$username = "root";
$password = "";

$conn = mysqli_connect($hostname, $username, $password, $database);
mysqli_set_charset($conn, "utf8");

if (!$conn) die("เชื่อมต่อ DB ไม่สำเร็จ: " . mysqli_connect_error());

// ตรวจสอบว่ามีสินค้าในตะกร้า
$sql_cart = "SELECT c.PID, c.quantity, p.Price 
             FROM cart c 
             JOIN products p ON c.PID = p.PID 
             WHERE c.UID = $UID";
$result_cart = mysqli_query($conn, $sql_cart);

if (mysqli_num_rows($result_cart) == 0) {
    $_SESSION['msg'] = "ตะกร้าว่าง ไม่มีสินค้าให้สั่งซื้อ";
    header("location: cart.php");
    exit();
}

// เริ่ม Transaction
mysqli_begin_transaction($conn);

try {
    $total_amount = 0;
    $items = [];

    // คำนวณยอดรวม
    while ($row = mysqli_fetch_assoc($result_cart)) {
        $items[] = $row;
        $total_amount += $row['Price'] * $row['quantity'];
    }

    // สร้าง orders ใหม่
    $sql_order = "INSERT INTO orders (UID, status, total_amount) VALUES ($UID, 'pending', $total_amount)";
    if (!mysqli_query($conn, $sql_order)) throw new Exception(mysqli_error($conn));
    $OID = mysqli_insert_id($conn);

    // เพิ่ม order_items
    foreach ($items as $item) {
        $pid = $item['PID'];
        $qty = $item['quantity'];
        $price = $item['Price'];

        $sql_item = "INSERT INTO order_items (OID, PID, quantity, price) VALUES ($OID, $pid, $qty, $price)";
        if (!mysqli_query($conn, $sql_item)) throw new Exception(mysqli_error($conn));
    }

    // สร้าง payment แบบ pending
    $sql_payment = "INSERT INTO payment (OID, payment_method, amount, payment_status) 
                    VALUES ($OID, 'cash_on_delivery', $total_amount, 'pending')";
    if (!mysqli_query($conn, $sql_payment)) throw new Exception(mysqli_error($conn));

    // ล้างตะกร้า
    $sql_clear_cart = "DELETE FROM cart WHERE UID=$UID";
    mysqli_query($conn, $sql_clear_cart);

    mysqli_commit($conn);

    $_SESSION['msg'] = "✅ สั่งซื้อสำเร็จ! สถานะคำสั่งซื้อ: Pending";
    header("location: orders_history.php");
} catch (Exception $e) {
    mysqli_rollback($conn);
    $_SESSION['msg'] = "❌ เกิดข้อผิดพลาด: " . $e->getMessage();
    header("location: cart.php");
}

mysqli_close($conn);
?>
