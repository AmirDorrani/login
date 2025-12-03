<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// اتصال به دیتابیس
$host = 'localhost';
$dbname = 'student';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(Exception $e) {
    die("خطا در اتصال به دیتابیس: " . $e->getMessage());
}

// گرفتن id دانش‌آموز از URL
if (!isset($_GET['id'])) {
    die("کاربر مشخص نشده است.");
}
$user_id = (int)$_GET['id'];

// گرفتن مشخصات دانش‌آموز
$stmt = $pdo->prepare("SELECT id, f_name, l_name FROM stude WHERE id=?");
$stmt->execute([$user_id]);
$userData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$userData) {
    die("دانش‌آموز یافت نشد.");
}

// دروس پیش‌فرض
$lessons = ['فارسی','ریاضی','قرآن','دینی','تاریخ','هنر','ورزش'];

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['score'], $_POST['name_dars']) && is_numeric($_POST['score'])) {
        $score = (int)$_POST['score'];
        $name_dars = trim($_POST['name_dars']);

        if ($score < 0 || $score > 20) {
            $message = "نمره باید بین 0 تا 20 باشد.";
        } elseif (empty($name_dars)) {
            $message = "لطفاً نام درس را وارد کنید.";
        } else {
            // ایجاد تاریخ و زمان فعلی
            $current_datetime = date('Y-m-d H:i:s'); // فرمت: 2024-01-15 14:30:45
            
            // بررسی وجود رکورد قبلی
            $stmtCheck = $pdo->prepare("SELECT id FROM studen WHERE user_id=? AND name_dars=?");
            $stmtCheck->execute([$user_id, $name_dars]);
            $exists = $stmtCheck->fetch();

            if ($exists) {
                // آپدیت نمره و تاریخ/زمان
                $stmtUpdate = $pdo->prepare("UPDATE studen SET score=?, date_time=? WHERE id=?");
                $stmtUpdate->execute([$score, $current_datetime, $exists['id']]);
                $message = "نمره برای درس {$name_dars} با موفقیت به‌روزرسانی شد ✅";
            } else {
                // درج نمره جدید با تاریخ/زمان
                $stmtInsert = $pdo->prepare("INSERT INTO studen (user_id, name_dars, score, date_time) VALUES (?,?,?,?)");
                $stmtInsert->execute([$user_id, $name_dars, $score, $current_datetime]);
                $message = "نمره برای درس {$name_dars} با موفقیت ثبت شد ✅";
            }
        }
    } else {
        $message = "لطفاً درس و نمره معتبر وارد کنید.";
    }
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ثبت نمره - مشکی قرمز</title>

<style>
@import url('https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;600&display=swap');

body {
    margin: 0;
    padding: 0;
    font-family: 'Vazirmatn', sans-serif;
    background: #111;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
}

.container {
    background: #1a1a1a;
    padding: 40px 35px;
    border-radius: 20px;
    width: 400px;
    box-shadow: 0 0 25px #ff1a1a99, 0 0 15px #ff4d4d55 inset;
    text-align: center;
    position: relative;
    animation: float 3s ease-in-out infinite alternate;
}

@keyframes float {
    0% { transform: translateY(0px); }
    100% { transform: translateY(-8px); }
}

h2 {
    color: #ff4d4d;
    font-size: 28px;
    margin-bottom: 20px;
    text-shadow: 0 0 10px #ff1a1a88, 0 0 20px #ff4d4d44;
}

.user-info {
    color: #fff;
    background: #220000;
    padding: 12px;
    border-radius: 10px;
    margin-bottom: 20px;
    box-shadow: 0 0 15px #ff1a1aaa;
}

input, button {
    width: 100%;
    padding: 14px;
    margin: 12px 0;
    border-radius: 12px;
    border: none;
    font-size: 16px;
    box-sizing: border-box;
    transition: all 0.3s ease;
}

input[list] {
    background: #222;
    color: #ff4d4d;
    box-shadow: 0 0 8px #ff1a1a55, inset 0 0 5px #ff4d4d22;
    text-align: center;
}
input[list]:focus {
    outline: none;
    box-shadow: 0 0 15px #ff1a1a, inset 0 0 5px #ff4d4d44;
    transform: scale(1.02);
}

input[type=number] {
    background: #222;
    color: #ff4d4d;
    text-align: center;
    box-shadow: 0 0 8px #ff1a1a55, inset 0 0 5px #ff4d4d22;
}
input[type=number]:focus {
    outline: none;
    box-shadow: 0 0 20px #ff1a1a, inset 0 0 5px #ff4d4d44;
    transform: scale(1.02);
}

button {
    background: linear-gradient(145deg, #ff1a1a, #b30000);
    color: #fff;
    font-weight: 600;
    cursor: pointer;
    box-shadow: 0 0 20px #ff1a1aaa, inset 0 0 8px #ff4d4d33;
    transition: all 0.3s ease;
}
button:hover {
    background: linear-gradient(145deg, #b30000, #ff1a1a);
    box-shadow: 0 0 35px #ff4d4dbb, inset 0 0 12px #ff1a1a55;
    transform: translateY(-2px) scale(1.02);
}

.message {
    margin-top: 18px;
    padding: 12px;
    border-radius: 14px;
    font-weight: 600;
    animation: fadeIn 0.6s;
}
.success {
    background:#330000; 
    color:#ff4d4d; 
    box-shadow: 0 0 15px #ff4d4daa;
}
.error { 
    background:#4d0000; 
    color:#ff9999; 
    box-shadow: 0 0 15px #ff6666; 
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
</head>
<body>

<div class="container">
<h2>📝 ثبت نمره - مشکی قرمز</h2>

<div class="user-info">
نام: <?php echo htmlspecialchars($userData['f_name']); ?><br>
نام خانوادگی: <?php echo htmlspecialchars($userData['l_name']); ?>
</div>

<form method="post">
<input list="lessons" name="name_dars" placeholder="انتخاب یا تایپ درس" required>
<datalist id="lessons">
<?php foreach($lessons as $lesson): ?>
    <option value="<?php echo $lesson; ?>">
<?php endforeach; ?>
</datalist>

<input type="number" name="score" min="0" max="20" placeholder="نمره (0 تا 20)" required>

<button type="submit">ثبت نمره</button>
</form>

<?php if($message): ?>
<div class="message <?php echo strpos($message,'موفقیت')!==false ? 'success':'error'; ?>">
    <?php echo htmlspecialchars($message); ?>
</div>
<?php endif; ?>
</div>

</body>
</html>
