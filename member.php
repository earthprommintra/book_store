<?php
session_start();

// ตรวจสอบว่าผู้ใช้ได้ Login แล้วเท่านั้น ไม่จำเป็นต้องตรวจสอบ Role
if (!isset($_SESSION['UID'])) {
    // ถ้ายังไม่ได้ Login ให้ส่งไปหน้า Login
    header("location: login.php");
    exit();
}

// ตรวจสอบ Role เพื่อป้องกัน Admin เข้ามาหน้า Member หากผู้ใช้เป็น Admin ให้ส่งไปหน้า Admin Dashboard แทน
if ($_SESSION['Role'] === 'admin') {
    header("location: admin.php");
    exit();
}

// ข้อมูลการเชื่อมต่อฐานข้อมูล
$hostname = "localhost";
$database = "u299560388.2568gp23";
$username = "root";
$password = "";

$surachet = mysqli_connect($hostname, $username, $password, $database);
mysqli_set_charset($surachet, "utf8");

if (!$surachet) {
    die("❌ การเชื่อมต่อฐานข้อมูลล้มเหลว: " . mysqli_connect_error());
}

// ดึงข้อมูลผู้ใช้ปัจจุบัน
$current_uid = mysqli_real_escape_string($surachet, $_SESSION['UID']);
$sql_user_info = "SELECT Fullname, Username, Role FROM users WHERE UID = '$current_uid'";
$result_user = mysqli_query($surachet, $sql_user_info);
$user_info = mysqli_fetch_assoc($result_user);

// ปิดการเชื่อมต่อ
mysqli_close($surachet); 
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>หน้าสมาชิก | E-commerce</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Prompt', sans-serif;
            background-color: #e9ecef; /* Light gray background */
        }
        .navbar-custom {
            background-color: #212529; /* Dark Navbar */
        }
        .content-container {
            padding-top: 50px;
        }
        .welcome-card {
            border-left: 5px solid #0d6efd; /* Primary blue color */
        }
        .action-card {
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
            border-radius: 10px;
        }
        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="member.php"><i class="bi bi-shop me-2"></i> หน้าสมาชิก</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="products.php"><i class="bi bi-grid-fill me-1"></i> ดูสินค้า</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="cart.php"><i class="bi bi-cart-fill me-1"></i> ตะกร้าสินค้า</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="orders_history.php"><i class="bi bi-clock-history me-1"></i> ประวัติคำสั่งซื้อ</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle me-1"></i> <?php echo htmlspecialchars($user_info['Fullname']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="profile.php"><i class="bi bi-gear-fill me-2"></i> จัดการบัญชี</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i> ออกจากระบบ</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <div class="container content-container">
        
        <div class="card p-4 mb-5 shadow-sm welcome-card">
            <h2 class="card-title text-primary"><i class="bi bi-emoji-smile me-2"></i> ยินดีต้อนรับเข้าสู่ระบบสมาชิก</h2>
            <p class="lead">คุณ **<?php echo htmlspecialchars($user_info['Fullname']); ?>** (Username: <?php echo htmlspecialchars($user_info['Username']); ?>) มีสิทธิ์เป็น: <span class="badge bg-primary"><?php echo ucfirst($user_info['Role']); ?></span></p>
            <p>นี่คือหน้าแดชบอร์ดส่วนตัวของคุณ คุณสามารถจัดการข้อมูลและดูประวัติการสั่งซื้อได้จากที่นี่</p>
        </div>

        <h3 class="mb-4"><i class="bi bi-lightning-fill me-2"></i> เมนูการดำเนินการด่วน</h3>
        
        <div class="row g-4">
            
            <div class="col-md-4">
                <a href="cart.php" class="text-decoration-none">
                    <div class="card action-card shadow-sm p-3 text-center">
                        <i class="bi bi-cart4 text-success" style="font-size: 3rem;"></i>
                        <div class="card-body">
                            <h5 class="card-title">ตะกร้าสินค้า</h5>
                            <p class="card-text text-muted">จัดการและชำระเงินสินค้าที่เลือกไว้</p>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-md-4">
                <a href="orders_history.php" class="text-decoration-none">
                    <div class="card action-card shadow-sm p-3 text-center">
                        <i class="bi bi-archive-fill text-warning" style="font-size: 3rem;"></i>
                        <div class="card-body">
                            <h5 class="card-title">ประวัติคำสั่งซื้อ</h5>
                            <p class="card-text text-muted">ตรวจสอบสถานะและประวัติการสั่งซื้อทั้งหมด</p>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-md-4">
                <a href="profile.php" class="text-decoration-none">
                    <div class="card action-card shadow-sm p-3 text-center">
                        <i class="bi bi-people-fill text-info" style="font-size: 3rem;"></i>
                        <div class="card-body">
                            <h5 class="card-title">จัดการบัญชี</h5>
                            <p class="card-text text-muted">แก้ไขข้อมูลส่วนตัว ที่อยู่ และรหัสผ่าน</p>
                        </div>
                    </div>
                </a>
            </div>

        </div> </div> <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>