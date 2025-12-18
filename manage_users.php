<?php
session_start();

// ตรวจสอบ Login และ Role
if (!isset($_SESSION['UID']) || $_SESSION['Role'] !== 'admin') {
    header("location: login.php");
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

$message = ""; // ตัวแปรสำหรับเก็บข้อความแจ้งเตือน (สำเร็จ/ผิดพลาด)

// Logic การจัดการข้อมูล (CRUD Operations)
// การเพิ่มผู้ใช้ใหม่
if (isset($_POST['add_user'])) {
    $new_username = mysqli_real_escape_string($surachet, $_POST['new_username']);
    $new_password = mysqli_real_escape_string($surachet, $_POST['new_password']); // ⚠️ ควรใช้ password_hash() ใน production
    $new_fullname = mysqli_real_escape_string($surachet, $_POST['new_fullname']);
    $new_role = mysqli_real_escape_string($surachet, $_POST['new_role']);

    // ควรใช้ password_hash() ในโลกจริง
    $hashed_password = $new_password; // แทนที่ด้วย password_hash($new_password, PASSWORD_DEFAULT); ในโค้ดจริง

    $sql_insert = "INSERT INTO users (Username, Password, Fullname, Role) 
                   VALUES ('$new_username', '$hashed_password', '$new_fullname', '$new_role')";
    
    if (mysqli_query($surachet, $sql_insert)) {
        $message = "✅ เพิ่มผู้ใช้ **{$new_fullname}** สำเร็จแล้ว!";
    } else {
        $message = "❌ ผิดพลาดในการเพิ่มผู้ใช้: " . mysqli_error($surachet);
    }
}

// การลบผู้ใช้
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['uid'])) {
    $uid_to_delete = mysqli_real_escape_string($surachet, $_GET['uid']);

    $sql_delete = "DELETE FROM users WHERE UID = '$uid_to_delete'";
    
    if (mysqli_query($surachet, $sql_delete)) {
        $message = "🗑️ ลบผู้ใช้ UID: {$uid_to_delete} สำเร็จแล้ว!";
    } else {
        $message = "❌ ผิดพลาดในการลบผู้ใช้: " . mysqli_error($surachet);
    }
}

// การดึงข้อมูลผู้ใช้ทั้งหมด
$sql_select_all = "SELECT UID, Username, Fullname, Role FROM users ORDER BY UID DESC";
$result_users = mysqli_query($surachet, $sql_select_all);

// ปิดการเชื่อมต่อเมื่อจบการใช้งาน
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการผู้ใช้ | Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 250px;
            --primary-color: #007bff;
        }
        body {
            font-family: 'Prompt', sans-serif;
            background-color: #f8f9fa;
        }
        .sidebar {
            width: var(--sidebar-width);
            background-color: #343a40;
            color: white;
            position: fixed;
            height: 100%;
            padding-top: 20px;
        }
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 30px;
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.7);
            padding: 12px 20px;
        }
        .sidebar .nav-link.active, .sidebar .nav-link:hover {
            color: white;
            background-color: var(--primary-color);
            border-radius: 5px;
            margin: 0 10px;
        }
        .logout-btn-sidebar {
            position: absolute;
            bottom: 20px;
            width: 85%;
            margin-left: 7.5%;
        }
    </style>
</head>
<body>
    
    <div class="sidebar">
        <div class="sidebar-header p-3 mb-4" style="font-weight: 700; font-size: 1.5rem; border-bottom: 1px solid rgba(255, 255, 255, 0.1);">
            <i class="bi bi-gear-fill me-2"></i> Admin Panel
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="admin.php"><i class="bi bi-speedometer2 me-2"></i> หน้าหลัก (Dashboard)</a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="manage_users.php"><i class="bi bi-people-fill me-2"></i> จัดการผู้ใช้</a>
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
        <h1 class="mb-4"><i class="bi bi-people-fill me-2"></i> จัดการผู้ใช้งาน</h1>

        <?php if ($message): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm mb-5">
            <div class="card-header bg-primary text-white">
                <h5><i class="bi bi-person-plus-fill me-2"></i> เพิ่มผู้ใช้งานใหม่</h5>
            </div>
            <div class="card-body">
                <form action="" method="POST">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <input type="text" name="new_username" class="form-control" placeholder="ชื่อผู้ใช้ (Username)" required>
                        </div>
                        <div class="col-md-3">
                            <input type="password" name="new_password" class="form-control" placeholder="รหัสผ่าน (Password)" required>
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="new_fullname" class="form-control" placeholder="ชื่อ-นามสกุล" required>
                        </div>
                        <div class="col-md-2">
                            <select name="new_role" class="form-select" required>
                                <option value="member">Member</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <button type="submit" name="add_user" class="btn btn-success w-100"><i class="bi bi-plus-lg"></i></button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card shadow-sm">
            <div class="card-header">
                <h5>รายชื่อผู้ใช้งานทั้งหมด (<?php echo mysqli_num_rows($result_users); ?> คน)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>UID</th>
                                <th>ชื่อผู้ใช้</th>
                                <th>ชื่อ-นามสกุล</th>
                                <th>สิทธิ์</th>
                                <th>จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if (mysqli_num_rows($result_users) > 0) {
                                while($row = mysqli_fetch_assoc($result_users)) {
                                    $role_badge = ($row['Role'] == 'admin') ? 'badge bg-danger' : 'badge bg-primary';
                                    echo "<tr>";
                                    echo "<td>" . $row['UID'] . "</td>";
                                    echo "<td>" . htmlspecialchars($row['Username']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['Fullname']) . "</td>";
                                    echo "<td><span class='{$role_badge}'>" . ucfirst($row['Role']) . "</span></td>";
                                    echo "<td>
                                        <a href='edit_user.php?uid=" . $row['UID'] . "' class='btn btn-sm btn-warning me-2'><i class='bi bi-pencil'></i> แก้ไข</a>
                                        <a href='?action=delete&uid=" . $row['UID'] . "' class='btn btn-sm btn-danger' onclick=\"return confirm('คุณแน่ใจหรือไม่ที่จะลบผู้ใช้: " . htmlspecialchars($row['Username']) . "?');\"><i class='bi bi-trash'></i> ลบ</a>
                                    </td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='5' class='text-center'>ไม่พบข้อมูลผู้ใช้งานในระบบ</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>