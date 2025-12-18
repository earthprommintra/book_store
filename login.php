<?php
session_start();

// ถ้าผู้ใช้ Login อยู่แล้ว ให้ส่งไปยังหน้า Dashboard ที่ถูกต้อง
if (isset($_SESSION['UID'])) {
    if ($_SESSION['Role'] === 'admin') {
        header("location: admin.php");
    } else {
        header("location: member.php");
    }
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

// Logic การเข้าสู่ระบบ (Authentication)**
if (isset($_POST['login'])) {
    $input_username = mysqli_real_escape_string($surachet, $_POST['username']);
    $input_password = mysqli_real_escape_string($surachet, $_POST['password']);
    
    // ดึงข้อมูลผู้ใช้จากฐานข้อมูล
    $sql_login = "SELECT UID, Fullname, Password, Role FROM users WHERE Username = '$input_username'";
    $result_login = mysqli_query($surachet, $sql_login);

    if (mysqli_num_rows($result_login) == 1) {
        $user = mysqli_fetch_assoc($result_login);
        
        // ตรวจสอบรหัสผ่าน
        // 💡 ในระบบจริง ควรใช้ password_verify($input_password, $user['Password'])
        if ($input_password === $user['Password']) { // ⚠️ ใช้การเปรียบเทียบตรงๆ สำหรับโค้ดตัวอย่างที่ไม่ได้ใช้ Hash
            
            // ตั้งค่า Session เมื่อ Login สำเร็จ
            $_SESSION['UID'] = $user['UID'];
            $_SESSION['Username'] = $input_username;
            $_SESSION['Fullname'] = $user['Fullname'];
            $_SESSION['Role'] = $user['Role'];
            
            // นำทางผู้ใช้ไปยังหน้า Dashboard ที่เหมาะสม
            if ($user['Role'] === 'admin') {
                header("location: admin.php");
            } else {
                header("location: member.php");
            }
            exit();

        } else {
            // รหัสผ่านไม่ถูกต้อง
            $message = "❌ รหัสผ่านไม่ถูกต้อง";
        }
    } else {
        // ไม่พบ Username ในระบบ
        $message = "❌ ไม่พบชื่อผู้ใช้ (Username) นี้ในระบบ";
    }
}

// 4. ปิดการเชื่อมต่อ
mysqli_close($surachet); 
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ</title>
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
        .login-card {
            width: 100%;
            max-width: 400px;
            padding: 30px;
            border-radius: 10px;
        }
    </style>
</head>
<body>
    
    <div class="card login-card shadow-lg">
        <h2 class="card-title text-center mb-4 text-primary"><i class="bi bi-box-arrow-in-right me-2"></i> เข้าสู่ระบบ</h2>

        <?php if ($message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="mb-3">
                <label for="username" class="form-label">ชื่อผู้ใช้ (Username)</label>
                <input type="text" name="username" id="username" class="form-control" required value="<?php echo isset($input_username) ? htmlspecialchars($input_username) : ''; ?>">
            </div>
            <div class="mb-4">
                <label for="password" class="form-label">รหัสผ่าน</label>
                <input type="password" name="password" id="password" class="form-control" required>
            </div>
            
            <button type="submit" name="login" class="btn btn-primary w-100 mb-3">
                <i class="bi bi-box-arrow-in-right me-2"></i> เข้าสู่ระบบ
            </button>
        </form>

        <p class="text-center mt-3">
            ยังไม่มีบัญชี? <a href="register.php">สมัครสมาชิกที่นี่</a>
        </p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>