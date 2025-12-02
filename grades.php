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

// گرفتن شناسه از URL
if (!isset($_GET['id'])) {
    die("کاربر مشخص نشده است.");
}
$user_id = (int)$_GET['id'];

// گرفتن مشخصات دانش آموز
$stmt = $pdo->prepare("SELECT f_name, l_name, fa_name, username FROM stude WHERE id=?");
$stmt->execute([$user_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$student) die("دانش‌آموز پیدا نشد.");

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['score']) && is_numeric($_POST['score'])) {
        $score = (int)$_POST['score'];
        if ($score < 0 || $score > 20) {
            $message = "نمره باید بین 0 تا 20 باشد.";
        } else {
            // بررسی وجود رکورد قبلی
            $stmtCheck = $pdo->prepare("SELECT id FROM studen WHERE user_id=?");
            $stmtCheck->execute([$user_id]);
            $exists = $stmtCheck->fetch();

            if ($exists) {
                // بروزرسانی نمره و نام و نام خانوادگی
                $stmtUpdate = $pdo->prepare("UPDATE studen SET f_name=?, l_name=?, score=? WHERE user_id=?");
                $stmtUpdate->execute([$student['f_name'], $student['l_name'], $score, $user_id]);
            } else {
                // ثبت رکورد جدید با نام و نام خانوادگی و نمره
                $stmtInsert = $pdo->prepare("INSERT INTO studen (user_id, f_name, l_name, score) VALUES (?,?,?,?)");
                $stmtInsert->execute([$user_id, $student['f_name'], $student['l_name'], $score]);
            }
            $message = "نمره با موفقیت ثبت شد ✅";
        }
    } else {
        $message = "لطفاً نمره معتبر انتخاب کنید.";
    }
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ثبت نمره - <?php echo htmlspecialchars($student['f_name']); ?></title>
<style>
body { font-family: 'Vazirmatn', sans-serif; background:#121212; color:#eee; display:flex; justify-content:center; align-items:center; height:100vh; margin:0;}
.container { background:#222; padding:30px; border-radius:14px; width:350px; box-shadow:0 0 20px #4a7cff88; text-align:center;}
h2 { color:#4a7cff; margin-bottom:15px;}
select, button { width:100%; padding:10px; margin:10px 0; border-radius:10px; border:none; font-size:16px; }
select { background:#111; color:#eee; box-shadow: inset 2px 2px 6px #1a1a1a, inset -2px -2px 6px #2a2a2a;}
button { background:#4a7cff; color:#fff; cursor:pointer; transition:.3s; box-shadow:0 0 15px #4a7cff;}
button:hover { background:#2a50c7; }
.message { margin-top:10px; padding:10px; border-radius:8px; }
.success { background:#004400; color:#0f0; box-shadow:0 0 15px #00ff66;}
.error { background:#440000; color:#f00; box-shadow:0 0 15px #ff3333;}
</style>
</head>
<body>

<div class="container">
<h2>ثبت نمره برای <?php echo htmlspecialchars($student['f_name'].' '.$student['l_name']); ?></h2>
<p>نام پدر: <?php echo htmlspecialchars($student['fa_name']); ?><br>نام کاربری: <?php echo htmlspecialchars($student['username']); ?></p>

<form method="post">
<select name="score" required>
<option value="">انتخاب نمره</option>
<?php for($i=0;$i<=20;$i++): ?>
    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
<?php endfor; ?>
</select>
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
