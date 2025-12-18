<?php
session_start();
// ตรวจสอบ login และ role ให้แน่ใจว่าผู้ใช้เป็น Admin เท่านั้นที่เข้าถึงหน้านี้ได้
if (!isset($_SESSION['UID']) || $_SESSION['Role'] !== 'admin') {
    header("location: login.php");
    exit();
}

// เชื่อมต่อฐานข้อมูล
$hostname = "localhost";
$database = "u299560388.2568gp23";
$username = "root";
$password = "";

$surachet = mysqli_connect($hostname, $username, $password, $database);
mysqli_set_charset($surachet, "utf8");

if (!$surachet) {
    die("เชื่อมต่อฐานข้อมูลไม่สำเร็จ: " . mysqli_connect_error());
}

// ดึงข้อมูลสถิติจากตารางจริง
// นับจำนวนผู้ใช้งานทั้งหมด (Total Users)
$sql_users = "SELECT COUNT(UID) AS total_users FROM users";
$result_users = mysqli_query($surachet, $sql_users);
$data_users = mysqli_fetch_assoc($result_users);
$total_users = $data_users['total_users']; // ใช้ค่าจริงจาก DB
// นับจำนวนสินค้าทั้งหมด (Total Products)
$sql_products = "SELECT COUNT(PID) AS total_products FROM products";
$result_products = mysqli_query($surachet, $sql_products);
$data_products = mysqli_fetch_assoc($result_products);
$total_products = $data_products['total_products']; // ใช้ค่าจริงจาก DB
// นับจำนวนคำสั่งซื้อที่รอดำเนินการ (Pending Orders)
$sql_pending_orders = "SELECT COUNT(OID) AS pending_orders FROM orders WHERE status = 'pending'";
$result_pending_orders = mysqli_query($surachet, $sql_pending_orders);
$data_pending_orders = mysqli_fetch_assoc($result_pending_orders);
$pending_orders = $data_pending_orders['pending_orders']; // ใช้ค่าจริงจาก DB

// ไม่ต้องปิดการเชื่อมต่อที่นี่ เพราะอาจมีการใช้ในส่วนอื่นของเพจได้
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Admin Page</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 250px;
            --primary-color: #007bff; /* สีน้ำเงินหลัก */
            --light-bg: #e9ecef; /* พื้นหลังสีเทาอ่อน */
            --card-hover-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }
        body {
            font-family: 'Prompt', sans-serif;
            background-color: var(--light-bg);
            min-height: 100vh;
        }
        .sidebar {
            width: var(--sidebar-width);
            background-color: #343a40; /* Darker tone */
            color: white;
            position: fixed;
            height: 100%;
            padding-top: 20px;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        }
        .sidebar-header {
            padding: 10px 20px;
            margin-bottom: 20px;
            font-weight: 700;
            font-size: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.7);
            padding: 12px 20px;
            transition: background-color 0.3s, color 0.3s;
        }
        .sidebar .nav-link.active, .sidebar .nav-link:hover {
            color: white;
            background-color: var(--primary-color);
            border-radius: 5px;
            margin: 0 10px;
        }
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 30px;
        }
        .welcome-card {
            background: linear-gradient(45deg, #007bff 0%, #0056b3 100%); /* Primary Blue gradient */
            color: white;
            border-radius: 12px;
            padding: 25px 30px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
            margin-bottom: 30px;
        }
        .welcome-card h2 {
            font-weight: 700;
        }
        .welcome-card p {
            font-size: 1rem;
            opacity: 0.9;
        }
        .stat-card {
            border-radius: 12px;
            border-left: 5px solid; /* For colored border */
            transition: transform 0.3s, box-shadow 0.3s;
            background-color: white;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--card-hover-shadow);
        }
        .stat-icon {
            font-size: 3rem;
            opacity: 0.6;
        }
        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
        }
        .stat-label {
            font-weight: 400;
            color: #6c757d;
        }
        .stat-card.users { border-left-color: #28a745; } /* Green */
        .stat-card.products { border-left-color: #ffc107; } /* Yellow */
        .stat-card.orders { border-left-color: #dc3545; } /* Red */
        .text-green { color: #28a745; }
        .text-yellow { color: #ffc107; }
        .text-red { color: #dc3545; }
        .logout-btn-sidebar {
            position: absolute;
            bottom: 20px;
            width: 85%;
            margin-left: 7.5%;
        }
        .card-custom {
            transition: transform 0.3s, box-shadow 0.3s;
            border: none;
            cursor: pointer;
        }
        .card-custom:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    
    <div class="sidebar">
        <div class="sidebar-header">
            <i class="bi bi-gear-fill me-2"></i> Admin Panel
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link active" href="#"><i class="bi bi-speedometer2 me-2"></i> หน้าหลัก (Dashboard)</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="manage_users.php"><i class="bi bi-people-fill me-2"></i> จัดการผู้ใช้</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="manage_products.php"><i class="bi bi-box-seam-fill me-2"></i> จัดการสินค้า</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="manage_orders.php"><i class="bi bi-cart-check-fill me-2"></i> จัดการคำสั่งซื้อ</a>
            </li>
            </ul>
        
        <a href="logout.php" class="btn btn-danger logout-btn-sidebar">
            <i class="bi bi-box-arrow-right"></i> ออกจากระบบ
        </a>
    </div>

    <div class="main-content">
        <div class="welcome-card shadow">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="bi bi-hand-thumbs-up-fill me-2"></i> ยินดีต้อนรับผู้ดูแลระบบ</h2>
                    <p class="mb-0">สวัสดีคุณ <b><?php echo htmlspecialchars($_SESSION['Fullname']); ?></b> (<?php echo htmlspecialchars($_SESSION['Username']); ?>)</p>
                </div>
                <i class="bi bi-clipboard-data-fill" style="font-size: 4rem; opacity: 0.5;"></i>
            </div>
        </div>
        
        <h3 class="mb-4 text-muted"><i class="bi bi-bar-chart-fill me-2"></i> ภาพรวมระบบ</h3>
        <div class="row g-4 mb-5">
            
            <div class="col-lg-4 col-md-6">
                <div class="card stat-card users p-3">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-value text-green"><?php echo number_format($total_users); ?></div>
                            <div class="stat-label">ผู้ใช้งานทั้งหมด</div>
                        </div>
                        <i class="bi bi-people-fill stat-icon text-green"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <div class="card stat-card products p-3">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-value text-yellow"><?php echo number_format($total_products); ?></div>
                            <div class="stat-label">สินค้าทั้งหมด</div>
                        </div>
                        <i class="bi bi-box-seam-fill stat-icon text-yellow"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <div class="card stat-card orders p-3">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-value text-red"><?php echo number_format($pending_orders); ?></div>
                            <div class="stat-label">คำสั่งซื้อที่รอดำเนินการ</div>
                        </div>
                        <i class="bi bi-cart-x-fill stat-icon text-red"></i>
                    </div>
                </div>
            </div>
        </div>

        <h3 class="mb-4 text-muted"><i class="bi bi-lightning-charge-fill me-2"></i> ดำเนินการด่วน</h3>
        <div class="row g-4">
            <div class="col-md-4">
                <a href="manage_users.php" class="card card-custom p-4 text-center bg-white text-decoration-none h-100">
                    <i class="bi bi-person-lines-fill display-4 text-primary"></i>
                    <h4 class="mt-3">จัดการผู้ใช้</h4>
                    <p class="text-muted mb-0">ดู, เพิ่ม, แก้ไข, ลบสมาชิก</p>
                </a>
            </div>
            <div class="col-md-4">
                <a href="manage_products.php" class="card card-custom p-4 text-center bg-white text-decoration-none h-100">
                    <i class="bi bi-tags-fill display-4 text-success"></i>
                    <h4 class="mt-3">จัดการสินค้า</h4>
                    <p class="text-muted mb-0">เพิ่ม, แก้ไข หรือลบสินค้า</p>
                </a>
            </div>
            <div class="col-md-4">
                <a href="manage_orders.php" class="card card-custom p-4 text-center bg-white text-decoration-none h-100">
                    <i class="bi bi-receipt display-4 text-warning"></i>
                    <h4 class="mt-3">จัดการคำสั่งซื้อ</h4>
                    <p class="text-muted mb-0">ตรวจสอบและจัดการคำสั่งซื้อ</p>
                </a>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>