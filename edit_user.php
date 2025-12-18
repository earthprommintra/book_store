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

$message = ""; 
$user_data = null; // ตัวแปรสำหรับเก็บข้อมูลผู้ใช้ที่จะแก้ไข

// รับ UID และดึงข้อมูลผู้ใช้ปัจจุบัน
if (isset($_GET['uid'])) {
    $uid_to_edit = mysqli_real_escape_string($surachet, $_GET['uid']);
    
    $sql_fetch = "SELECT UID, Username, Fullname, Role FROM users WHERE UID = '$uid_to_edit'";
    $result_fetch = mysqli_query($surachet, $sql_fetch);
    
    if (mysqli_num_rows($result_fetch) == 1) {
        $user_data = mysqli_fetch_assoc($result_fetch);
    } else {
        // หากไม่พบ UID หรือมีข้อผิดพลาด
        header("location: manage_users.php");
        exit();
    }
} else {
    // หากไม่มี UID ส่งมา
    header("location: manage_users.php");
    exit();
}

// Logic การแก้ไข/อัปเดตข้อมูล
if (isset($_POST['update_user'])) {
    // ใช้ UID เดิมที่ถูกดึงมา
    $uid = $user_data['UID']; 
    $new_fullname = mysqli_real_escape_string($surachet, $_POST['new_fullname']);
    $new_role = mysqli_real_escape_string($surachet, $_POST['new_role']);
    $new_password = mysqli_real_escape_string($surachet, $_POST['new_password']);

    $update_fields = "Fullname = '$new_fullname', Role = '$new_role'";

    // ตรวจสอบว่ามีการป้อนรหัสผ่านใหม่หรือไม่
    if (!empty($new_password)) {
        // ⚠️ ในโลกจริง: ใช้ $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $hashed_password = $new_password; 
        $update_fields .= ", Password = '$hashed_password'";
        $message .= " (มีการตั้งรหัสผ่านใหม่)";
    }

    $sql_update = "UPDATE users SET $update_fields WHERE UID = '$uid'";
    
    if (mysqli_query($surachet, $sql_update)) {
        // อัปเดตข้อมูลใน $user_data ทันที เพื่อให้แสดงผลลัพธ์ใหม่ในฟอร์ม
        $user_data['Fullname'] = $new_fullname;
        $user_data['Role'] = $new_role;

        $message = "✅ อัปเดตข้อมูลผู้ใช้ **{$user_data['Username']}** สำเร็จแล้ว! {$message}";
    } else {
        $message = "❌ ผิดพลาดในการอัปเดตข้อมูล: " . mysqli_error($surachet);
    }
}

// ปิดการเชื่อมต่อเมื่อจบการใช้งาน
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขผู้ใช้: <?php echo htmlspecialchars($user_data['Username']); ?> | Admin Panel</title>
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
        <h1 class="mb-4"><i class="bi bi-pencil-square me-2"></i> แก้ไขผู้ใช้งาน: **<?php echo htmlspecialchars($user_data['Username']); ?>**</h1>

        <?php if ($message): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-warning text-white">
                <h5><i class="bi bi-info-circle-fill me-2"></i> ข้อมูลผู้ใช้ UID: <?php echo $user_data['UID']; ?></h5>
            </div>
            <div class="card-body">
                <form action="" method="POST">
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">ชื่อผู้ใช้ (Username)</label>
                        <input type="text" id="username" class="form-control" value="<?php echo htmlspecialchars($user_data['Username']); ?>" disabled>
                    </div>

                    <div class="mb-3">
                        <label for="fullname" class="form-label">ชื่อ-นามสกุล</label>
                        <input type="text" name="new_fullname" id="fullname" class="form-control" value="<?php echo htmlspecialchars($user_data['Fullname']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="role" class="form-label">สิทธิ์การเข้าถึง (Role)</label>
                        <select name="new_role" id="role" class="form-select" required>
                            <option value="member" <?php echo ($user_data['Role'] == 'member') ? 'selected' : ''; ?>>Member</option>
                            <option value="admin" <?php echo ($user_data['Role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label">รหัสผ่านใหม่ (ว่างไว้หากไม่ต้องการเปลี่ยน)</label>
                        <input type="password" name="new_password" id="password" class="form-control" placeholder="ป้อนรหัสผ่านใหม่">
                        <small class="form-text text-muted">หากไม่ต้องการเปลี่ยนรหัสผ่าน ให้เว้นช่องนี้ว่างไว้</small>
                    </div>

                    <button type="submit" name="update_user" class="btn btn-warning"><i class="bi bi-save-fill me-2"></i> บันทึกการแก้ไข</button>
                    <a href="manage_users.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> กลับไปยังจัดการผู้ใช้</a>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>