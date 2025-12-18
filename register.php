<?php
session_start();

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

// Logic การลงทะเบียน (Registration)**
if (isset($_POST['register'])) {
    $fullname = mysqli_real_escape_string($surachet, $_POST['fullname']);
    $username = mysqli_real_escape_string($surachet, $_POST['username']);
    $password = mysqli_real_escape_string($surachet, $_POST['password']);
    $confirm_password = mysqli_real_escape_string($surachet, $_POST['confirm_password']);
    
    // ตั้งค่า Role เริ่มต้นเป็น 'member'
    $role = 'member';

    // ตรวจสอบรหัสผ่านตรงกัน
    if ($password !== $confirm_password) {
        $message = "❌ รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน";
    } 
    // ตรวจสอบความยาวรหัสผ่านขั้นต่ำ (ตัวอย่าง: 6 ตัวอักษร)
    else if (strlen($password) < 6) {
        $message = "❌ รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร";
    }
    // ตรวจสอบ Username ซ้ำ
    else {
        $sql_check = "SELECT Username FROM users WHERE Username = '$username'";
        $result_check = mysqli_query($surachet, $sql_check);

        if (mysqli_num_rows($result_check) > 0) {
            $message = "❌ Username นี้ถูกใช้ไปแล้ว กรุณาเลือกชื่อผู้ใช้ใหม่";
        } else {
            // เข้ารหัสรหัสผ่าน (Security)
            // 💡 ควรใช้ password_hash() ในระบบจริง
            $hashed_password = $password; // ⚠️ ในระบบจริง: $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // บันทึกข้อมูล
            $sql_insert = "INSERT INTO users (Fullname, Username, Password, Role) 
                           VALUES ('$fullname', '$username', '$hashed_password', '$role')";

            if (mysqli_query($surachet, $sql_insert)) {
                $message = "✅ ลงทะเบียนสำเร็จแล้ว! คุณสามารถเข้าสู่ระบบได้ทันที";
                // Redirect ไปหน้า Login หลังจากลงทะเบียนสำเร็จ
                header("refresh:3; url=login.php"); 
            } else {
                $message = "❌ ผิดพลาดในการลงทะเบียน: " . mysqli_error($surachet);
            }
        }
    }
}

// ปิดการเชื่อมต่อ
mysqli_close($surachet); 
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลงทะเบียนผู้ใช้ใหม่ | E-commerce</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Prompt', sans-serif;
            background-color: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .register-card {
            width: 100%;
            max-width: 450px;
            padding: 30px;
            border-radius: 10px;
        }
    </style>
</head>
<body>
    
    <div class="card register-card shadow-lg">
        <h2 class="card-title text-center mb-4 text-primary"><i class="bi bi-person-plus-fill me-2"></i> สมัครสมาชิกใหม่</h2>

        <?php if ($message): ?>
            <div class="alert <?php echo (strpos($message, '✅') !== false) ? 'alert-success' : 'alert-danger'; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="mb-3">
                <label for="username" class="form-label">ชื่อผู้ใช้ (Username)</label>
                <input type="text" name="username" id="username" class="form-control" required value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>">
            </div>
            <div class="mb-3">
                <label for="fullname" class="form-label">ชื่อ-นามสกุล</label>
                <input type="text" name="fullname" id="fullname" class="form-control" required value="<?php echo isset($fullname) ? htmlspecialchars($fullname) : ''; ?>">
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">รหัสผ่าน</label>
                <input type="password" name="password" id="password" class="form-control" required>
            </div>
            <div class="mb-4">
                <label for="confirm_password" class="form-label">ยืนยันรหัสผ่าน</label>
                <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
            </div>
            
            <button type="submit" name="register" class="btn btn-primary w-100 mb-3">
                <i class="bi bi-person-check-fill me-2"></i> ลงทะเบียน
            </button>
        </form>

        <p class="text-center mt-3">
            เป็นสมาชิกอยู่แล้ว? <a href="login.php">เข้าสู่ระบบที่นี่</a>
        </p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>