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

// تاریخ پیش‌فرض
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-7 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$search_type = isset($_GET['search_type']) ? $_GET['search_type'] : 'all';

// گرفتن اطلاعات ویرایش‌ها در بازه زمانی
$results = [];
if (isset($_GET['search'])) {
    $start_datetime = $start_date . ' 00:00:00';
    $end_datetime = $end_date . ' 23:59:59';
    
    if ($search_type == 'update' || $search_type == 'all') {
        // ویرایش‌ها
        $sql_update = "SELECT 
            s.id, s.user_id, s.name_dars, s.score, s.date_update,
            u.f_name, u.l_name
            FROM studen s
            JOIN stude u ON s.user_id = u.id
            WHERE s.date_update BETWEEN ? AND ?
            ORDER BY s.date_update DESC";
        
        $stmt_update = $pdo->prepare($sql_update);
        $stmt_update->execute([$start_datetime, $end_datetime]);
        $updates = $stmt_update->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($updates as $update) {
            $update['type'] = 'ویرایش';
            $update['date'] = $update['date_update'];
            $results[] = $update;
        }
    }
    
    if ($search_type == 'create' || $search_type == 'all') {
        // ثبت‌های جدید
        $sql_create = "SELECT 
            s.id, s.user_id, s.name_dars, s.score, s.date_time as date,
            u.f_name, u.l_name
            FROM studen s
            JOIN stude u ON s.user_id = u.id
            WHERE s.date_time BETWEEN ? AND ?
            ORDER BY s.date_time DESC";
        
        $stmt_create = $pdo->prepare($sql_create);
        $stmt_create->execute([$start_datetime, $end_datetime]);
        $creates = $stmt_create->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($creates as $create) {
            $create['type'] = 'ثبت جدید';
            $results[] = $create;
        }
    }
    
    // مرتب‌سازی بر اساس تاریخ
    usort($results, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
}

// آمار کلی
$stats = [
    'total' => count($results),
    'updates' => 0,
    'creates' => 0
];

foreach ($results as $item) {
    if ($item['type'] == 'ویرایش') {
        $stats['updates']++;
    } else {
        $stats['creates']++;
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📊 گزارش زمانی ویرایش‌ها</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700&display=swap');
        
        :root {
            --primary: #4361ee;
            --secondary: #3a0ca3;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --dark: #1a1a2e;
            --light: #f8f9fa;
            --gray: #6c757d;
            --gradient: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            --radius: 15px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Vazirmatn', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 30px;
            color: var(--dark);
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            animation: fadeIn 0.8s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .header {
            background: white;
            padding: 30px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            background: linear-gradient(to right, #ffffff, #f8f9fa);
            border-left: 5px solid var(--primary);
        }
        
        .header h1 {
            color: var(--primary);
            font-size: 32px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .header h1 i {
            font-size: 36px;
        }
        
        .nav-buttons {
            display: flex;
            gap: 15px;
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-align: center;
        }
        
        .btn-primary {
            background: var(--gradient);
            color: white;
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(67, 97, 238, 0.4);
        }
        
        .btn-outline {
            background: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
        }
        
        .btn-outline:hover {
            background: var(--primary);
            color: white;
        }
        
        .filter-card {
            background: white;
            padding: 35px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            margin-bottom: 30px;
            animation: slideUp 0.6s ease;
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .filter-card h2 {
            color: var(--secondary);
            margin-bottom: 25px;
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .form-group label {
            font-weight: 600;
            color: var(--dark);
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-control {
            padding: 14px 18px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: var(--transition);
            background: #f8f9fa;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
            background: white;
        }
        
        .radio-group {
            display: flex;
            gap: 25px;
            flex-wrap: wrap;
        }
        
        .radio-label {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            padding: 12px 20px;
            background: #f8f9fa;
            border-radius: 10px;
            transition: var(--transition);
            border: 2px solid transparent;
        }
        
        .radio-label:hover {
            background: #e9ecef;
        }
        
        .radio-label.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .radio-label input {
            display: none;
        }
        
        .btn-search {
            grid-column: 1 / -1;
            background: var(--gradient);
            color: white;
            padding: 16px;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 12px;
            margin-top: 10px;
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.3);
        }
        
        .btn-search:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(67, 97, 238, 0.4);
        }
        
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 30px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: var(--transition);
            animation: slideUp 0.6s ease 0.2s backwards;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }
        
        .stat-icon {
            width: 70px;
            height: 70px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: white;
        }
        
        .stat-icon.total { background: linear-gradient(135deg, #4361ee, #3a0ca3); }
        .stat-icon.update { background: linear-gradient(135deg, #4cc9f0, #4895ef); }
        .stat-icon.create { background: linear-gradient(135deg, #f72585, #b5179e); }
        
        .stat-info h3 {
            font-size: 16px;
            color: var(--gray);
            margin-bottom: 8px;
        }
        
        .stat-number {
            font-size: 36px;
            font-weight: 700;
            color: var(--dark);
        }
        
        .results-section {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            animation: slideUp 0.6s ease 0.4s backwards;
        }
        
        .results-header {
            padding: 25px 30px;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .results-header h2 {
            font-size: 22px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .results-count {
            background: rgba(255, 255, 255, 0.2);
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 15px;
            font-weight: 600;
        }
        
        .table-container {
            overflow-x: auto;
            padding: 20px;
        }
        
        .results-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }
        
        .results-table th {
            background: #f8f9fa;
            padding: 18px 20px;
            text-align: right;
            font-weight: 600;
            color: var(--dark);
            border-bottom: 2px solid #e0e0e0;
            font-size: 15px;
        }
        
        .results-table td {
            padding: 18px 20px;
            border-bottom: 1px solid #eee;
            transition: var(--transition);
        }
        
        .results-table tr:hover td {
            background: #f8fafc;
        }
        
        .type-badge {
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            display: inline-block;
        }
        
        .type-update {
            background: #dbeafe;
            color: #1d4ed8;
        }
        
        .type-create {
            background: #dcfce7;
            color: #166534;
        }
        
        .score-badge {
            background: linear-gradient(135deg, #4cc9f0, #4895ef);
            color: white;
            padding: 6px 15px;
            border-radius: 20px;
            font-weight: 600;
            display: inline-block;
            min-width: 45px;
            text-align: center;
        }
        
        .student-name {
            font-weight: 600;
            color: var(--dark);
        }
        
        .datetime-cell {
            font-family: monospace;
            font-size: 14px;
            color: var(--gray);
        }
        
        .no-results {
            text-align: center;
            padding: 60px 30px;
            color: var(--gray);
        }
        
        .no-results i {
            font-size: 60px;
            margin-bottom: 20px;
            color: #e0e0e0;
        }
        
        .no-results h3 {
            font-size: 22px;
            margin-bottom: 10px;
            color: var(--dark);
        }
        
        @media (max-width: 768px) {
            body {
                padding: 15px;
            }
            
            .header {
                flex-direction: column;
                text-align: center;
                padding: 25px;
            }
            
            .header h1 {
                font-size: 26px;
            }
            
            .filter-card {
                padding: 25px;
            }
            
            .stat-card {
                flex-direction: column;
                text-align: center;
                padding: 25px;
            }
            
            .results-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .table-container {
                padding: 10px;
            }
            
            .results-table th,
            .results-table td {
                padding: 12px 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>
                <span>📊</span>
                گزارش زمانی ویرایش‌های نمرات
            </h1>
            <div class="nav-buttons">
                <a href="grades.php?id=1" class="btn btn-outline">
                    ← بازگشت به ثبت نمرات
                </a>
                <button onclick="window.print()" class="btn btn-primary">
                    🖨️ چاپ گزارش
                </button>
            </div>
        </div>
        
        <div class="filter-card">
            <h2><span>🔍</span> فیلتر جستجو</h2>
            <form method="GET" class="filter-form">
                <div class="form-group">
                    <label for="start_date"><span>📅</span> تاریخ شروع:</label>
                    <input type="date" id="start_date" name="start_date" 
                           value="<?php echo $start_date; ?>" class="form-control">
                </div>
                
                <div class="form-group">
                    <label for="end_date"><span>📅</span> تاریخ پایان:</label>
                    <input type="date" id="end_date" name="end_date" 
                           value="<?php echo $end_date; ?>" class="form-control">
                </div>
                
                <div class="form-group">
                    <label><span>📋</span> نوع رویداد:</label>
                    <div class="radio-group">
                        <label class="radio-label <?php echo $search_type == 'all' ? 'active' : ''; ?>">
                            <input type="radio" name="search_type" value="all" 
                                   <?php echo $search_type == 'all' ? 'checked' : ''; ?>>
                            <span>📊 همه رویدادها</span>
                        </label>
                        <label class="radio-label <?php echo $search_type == 'update' ? 'active' : ''; ?>">
                            <input type="radio" name="search_type" value="update" 
                                   <?php echo $search_type == 'update' ? 'checked' : ''; ?>>
                            <span>✏️ فقط ویرایش‌ها</span>
                        </label>
                        <label class="radio-label <?php echo $search_type == 'create' ? 'active' : ''; ?>">
                            <input type="radio" name="search_type" value="create" 
                                   <?php echo $search_type == 'create' ? 'checked' : ''; ?>>
                            <span>➕ فقط ثبت‌های جدید</span>
                        </label>
                    </div>
                </div>
                
                <button type="submit" name="search" class="btn-search">
                    <span>🔎</span>
                    جستجو در بازه زمانی
                </button>
            </form>
        </div>
        
        <?php if (isset($_GET['search'])): ?>
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-icon total">
                    📈
                </div>
                <div class="stat-info">
                    <h3>تعداد کل رویدادها</h3>
                    <div class="stat-number"><?php echo $stats['total']; ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon update">
                    ✏️
                </div>
                <div class="stat-info">
                    <h3>ویرایش‌ها</h3>
                    <div class="stat-number"><?php echo $stats['updates']; ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon create">
                    ➕
                </div>
                <div class="stat-info">
                    <h3>ثبت‌های جدید</h3>
                    <div class="stat-number"><?php echo $stats['creates']; ?></div>
                </div>
            </div>
        </div>
        
        <div class="results-section">
            <div class="results-header">
                <h2><span>📋</span> نتایج جستجو</h2>
                <div class="results-count">
                    <?php echo count($results); ?> مورد یافت شد
                </div>
            </div>
            
            <?php if (!empty($results)): ?>
                <div class="table-container">
                    <table class="results-table">
                        <thead>
                            <tr>
                                <th>ردیف</th>
                                <th>نوع رویداد</th>
                                <th>دانش‌آموز</th>
                                <th>کد دانش‌آموزی</th>
                                <th>نام درس</th>
                                <th>نمره</th>
                                <th>تاریخ و زمان</th>
                                <th>جزئیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $index => $row): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td>
                                    <span class="type-badge <?php echo $row['type'] == 'ویرایش' ? 'type-update' : 'type-create'; ?>">
                                        <?php echo $row['type']; ?>
                                    </span>
                                </td>
                                <td class="student-name">
                                    <?php echo htmlspecialchars($row['f_name'] . ' ' . $row['l_name']); ?>
                                </td>
                                <td><?php echo $row['user_id']; ?></td>
                                <td><?php echo htmlspecialchars($row['name_dars']); ?></td>
                                <td>
                                    <span class="score-badge">
                                        <?php echo $row['score']; ?>
                                    </span>
                                </td>
                                <td class="datetime-cell">
                                    <?php echo date('Y/m/d H:i', strtotime($row['date'])); ?>
                                </td>
                                <td>
                                    <a href="grades.php?id=<?php echo $row['user_id']; ?>" 
                                       class="btn btn-outline" style="padding: 8px 15px; font-size: 14px;">
                                        مشاهده جزئیات
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-results">
                    <div>🔍</div>
                    <h3>هیچ موردی یافت نشد</h3>
                    <p>در بازه زمانی انتخاب شده هیچ رویدادی ثبت نشده است.</p>
                </div>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="no-results" style="background: white; border-radius: var(--radius); padding: 60px; text-align: center;">
            <div style="font-size: 80px; margin-bottom: 20px; color: #4361ee;">📊</div>
            <h3 style="color: #3a0ca3; margin-bottom: 15px;">گزارش زمانی ویرایش‌ها</h3>
            <p style="color: #6c757d; max-width: 600px; margin: 0 auto 30px; line-height: 1.6;">
                برای مشاهده گزارش ویرایش‌ها و ثبت‌های نمرات، لطفاً بازه زمانی مورد نظر را انتخاب کرده و دکمه جستجو را بزنید.
            </p>
            <p style="color: #f8961e; font-weight: 600;">
                ⏱️ گزارش‌دهی بر اساس تاریخ و ساعت دقیق انجام می‌شود
            </p>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
        // فعال‌سازی radio buttons
        document.querySelectorAll('.radio-label').forEach(label => {
            label.addEventListener('click', function() {
                document.querySelectorAll('.radio-label').forEach(l => l.classList.remove('active'));
                this.classList.add('active');
                this.querySelector('input').checked = true;
            });
        });
        
        // تاریخ پیش‌فرض امروز برای تاریخ پایان
        document.getElementById('end_date').max = new Date().toISOString().split('T')[0];
        
        // اعتبارسنجی تاریخ
        document.querySelector('form').addEventListener('submit', function(e) {
            const startDate = new Date(document.getElementById('start_date').value);
            const endDate = new Date(document.getElementById('end_date').value);
            
            if (startDate > endDate) {
                e.preventDefault();
                alert('⚠️ تاریخ شروع نمی‌تواند بعد از تاریخ پایان باشد!');
                return false;
            }
        });
    </script>
</body>
</html>