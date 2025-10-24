<?php
include '../student-grades/src/includes/conn.php';
include '../student-grades/src/includes/functions.php'; 

$departments = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$levels = $pdo->query("SELECT * FROM academic_levels ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$subjects = $pdo->query("SELECT * FROM subjects ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

$lectures = [];
$search_active = false;
$params = [];
$sql = "SELECT l.*, s.name as subject_name, d.name as department_name, al.name as level_name, u.first_name, u.last_name
        FROM lectures l
        JOIN subjects s ON l.subject_id = s.id
        JOIN departments d ON l.department_id = d.id
        JOIN academic_levels al ON l.level_id = al.id
        JOIN users u ON l.teacher_id = u.id
        WHERE 1=1";

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    if (!empty($_GET['level_id'])) {
        $sql .= " AND l.level_id = :level_id";
        $params[':level_id'] = $_GET['level_id'];
        $search_active = true;
    }
    if (!empty($_GET['department_id'])) {
        $sql .= " AND l.department_id = :department_id";
        $params[':department_id'] = $_GET['department_id'];
        $search_active = true;
    }
    if (!empty($_GET['subject_id'])) {
        $sql .= " AND l.subject_id = :subject_id";
        $params[':subject_id'] = $_GET['subject_id'];
        $search_active = true;
    }
    if (!empty($_GET['keyword'])) {
        $sql .= " AND (l.title LIKE :keyword OR l.description LIKE :keyword)";
        $params[':keyword'] = '%' . trim($_GET['keyword']) . '%';
        $search_active = true;
    }
}

$sql .= " ORDER BY l.upload_date DESC";

if ($search_active) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $lectures = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $search_error = "حدث خطأ أثناء البحث.";
        error_log("Lecture search error: " . $e->getMessage());
    }
}

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="ابحث عن المحاضرات الدراسية لكلية الهندسة جامعة بني سويف مفلترة حسب القسم، الفرقة، أو المادة الدراسية.">
    <meta name="keywords" content="كلية الهندسة, جامعة بني سويف, محاضرات دراسية, تحميل محاضرات, محاضرات هندسة">

    <title>المحاضرات الدراسية | كلية الهندسة</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        bsu_dark_blue: '#1e40af',
                        bsu_light_gray: '#f3f4f6',
                        bsu_medium_blue: '#3b82f6',
                        bsu_gold: '#FFC107',
                    },
                    fontFamily: {
                        'cairo': ['Cairo', 'sans-serif'],
                    },
                    keyframes: {
                         fadeInUp: {
                            '0%': { opacity: '0', transform: 'translateY(20px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' },
                        }
                    },
                    animation: {
                        'fade-in-up': 'fadeInUp 0.6s ease-out forwards',
                    }
                },
            },
        }
    </script>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        body {
            font-family: 'cairo', sans-serif;
            scroll-behavior: smooth;
        }
        nav ul li .dropdown-content {
            display: none; position: absolute; background: white;
            min-width: 200px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border-radius: 8px; padding: 0.5rem 0; z-index: 50;
            opacity: 0; transform: translateY(10px);
            transition: opacity 0.3s ease, transform 0.3s ease;
        }
        nav ul li:hover > .dropdown-content { display: block; opacity: 1; transform: translateY(0); }
        .dropdown-content li a {
            display: block; padding: 10px 15px; color: #374151;
            transition: background-color 0.2s ease, padding-right 0.2s ease, color 0.2s ease; white-space: nowrap;
        }
        .dropdown-content li a:hover {
            background-color: #f3f4f6; padding-right: 20px; color: #1e40af;
        }
        header { border-bottom: 3px solid #FFC107; }
        #mobile-menu a:hover { background-color: #e5e7eb; }
        
        .form-group { margin-bottom: 1.25rem; }
        .form-group label {
            display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }
        .form-group input, .form-group select {
            width: 100%; padding: 0.65rem 1rem; border: 1px solid #D1D5DB;
            border-radius: 0.375rem; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            background-color: #fff;
        }
         .form-group select {
            background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%2020%2020%22%20fill%3D%22currentColor%22%3E%3Cpath%20fill-rule%3D%22evenodd%22%20d%3D%22M5.293%207.293a1%201%200%20011.414%200L10%2010.586l3.293-3.293a1%201%200%20111.414%201.414l-4%204a1%201%200%2001-1.414%200l-4-4a1%201%200%20010-1.414z%22%20clip-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E');
            background-repeat: no-repeat;
            background-position: left 0.5rem center;
            background-size: 1.5em 1.5em;
            padding-left: 2.5rem;
        }
        .form-group input:focus, .form-group select:focus {
            outline: none; border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }
        
        .submit-btn {
            display: inline-flex; justify-content: center; align-items: center;
            width: 100%; padding: 0.75rem 1.5rem; background-color: #1e40af;
            color: white; font-weight: 700; border-radius: 0.375rem; border: none;
            cursor: pointer; transition: background-color 0.3s ease;
            height: 50px; 
        }
        .submit-btn:hover { background-color: #3b82f6; }
        
        .data-table {
            width: 100%; border-collapse: collapse; margin-top: 25px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05); border-radius: 8px; overflow: hidden;
        }
        .data-table th, .data-table td {
            border-bottom: 1px solid #ddd; padding: 12px 15px;
            text-align: right; white-space: nowrap;
        }
        .data-table th {
            background-color: #f9fafb; font-weight: 700; color: #374151;
            font-size: 0.875rem; text-transform: uppercase;
        }
        .data-table tr:last-child td { border-bottom: 0; }
        .data-table tr:hover { background-color: #f3f4f6; }
        .data-table .actions a {
            display: inline-flex; align-items: center;
            margin: 0; padding: 6px 12px; font-size: 13px; text-decoration: none;
            border: 1px solid #3b82f6; color: #3b82f6; border-radius: 6px;
            transition: all 0.2s ease;
        }
        .data-table .actions a:hover { background-color: #3b82f6; color: white; }
    </style>
</head>
<body class="bg-bsu_light_gray text-gray-800">

    <header id="main-header" class="bg-white shadow sticky top-0 z-50">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <div class="logo">
                <a href="../index.html" class="flex items-center space-x-3 rtl:space-x-reverse group">
                    <img src="https://www2.0zz0.com/2025/10/08/14/393349642.png" alt="شعار كلية الهندسة جامعة بني سويف" class="w-12 h-12 rounded-full shadow transition-transform duration-300 group-hover:scale-110">
                    <h1 class="text-xl md:text-2xl font-bold text-bsu_dark_blue group-hover:text-bsu_medium_blue transition-colors duration-200">كلية الهندسة</h1>
                </a>
            </div>

            <nav class="hidden md:flex">
                <ul class="flex space-x-5 rtl:space-x-reverse items-center">
                    <li class="relative group">
                        <a href="../index.html" class="text-gray-700 hover:text-bsu_dark_blue font-semibold transition-colors duration-200">الرئيسية</a>
                        <ul class="dropdown-content">
                            <li><a href="../dean-word.html">كلمة عميد الكلية</a></li>
                            <li><a href="../vision-mission.html">الرؤية والرسالة والأهداف</a></li>
                            <li><a href="../regulations.html">اللائحة الداخلية</a></li>
                            <li><a href="../laws-regulations.html">القوانين واللوائح</a></li>
                        </ul>
                    </li>
                    <li><a href="../about.html" class="text-gray-700 hover:text-bsu_dark_blue font-semibold transition-colors duration-200">عن الكلية</a></li>
                    <li class="relative group">
                        <a href="#" class="text-gray-700 hover:text-bsu_dark_blue font-semibold transition-colors duration-200">الأقسام العلمية</a>
                        <ul class="dropdown-content">
                            <li><a href="../civil-engineering.html">قسم الهندسة المدنية</a></li>
                            <li><a href="../architectural-engineering.html">قسم الهندسة المعمارية</a></li>
                            <li><a href="../electrical-engineering.html">قسم الهندسة الكهربية</a></li>
                        </ul>
                    </li>
                    <li class="relative group">
                        <a href="#" class="text-gray-700 hover:text-bsu_dark_blue font-semibold transition-colors duration-200">البرامج الدراسية</a>
                        <ul class="dropdown-content">
                            <li><a href="../structural-engineering.html">برنامج الهندسة الإنشائية</a></li>
                        </ul>
                    </li>
                    <li class="relative group">
                        <a href="#" class="text-gray-700 hover:text-bsu_dark_blue font-semibold transition-colors duration-200">الطلاب</a>
                        <ul class="dropdown-content w-56">
                            <li><a href="http://www.email.bsu.edu.eg/_BSU_Std.aspx" target="_blank" rel="noopener noreferrer">الحصول على البريد الإلكتروني</a></li>
                            <li><a href="https://www.bsu.edu.eg/Sector_Home.aspx?cat_id=284" target="_blank" rel="noopener noreferrer">منتدى الطلاب</a></li>
                            <li><a href="https://www.bsu.edu.eg/Sector_Home.aspx?cat_id=286" target="_blank" rel="noopener noreferrer">المدن الجامعية</a></li>
                            <li><a href="https://www.bsu.edu.eg/Sector_Home.aspx?cat_id=277" target="_blank" rel="noopener noreferrer">رعاية الشباب</a></li>
                            <li><a href="https://www.bsu.edu.eg/Sector_Home.aspx?cat_id=53" target="_blank" rel="noopener noreferrer">إدارة الوافدين</a></li>
                            <li><a href="http://www.results.bsu.edu.eg/" target="_blank" rel="noopener noreferrer">نتائج الكليات</a></li>
                            <li><a href="http://www.bsu.edu.eg/Sector_Home.aspx?cat_id=283" target="_blank" rel="noopener noreferrer">التربية العسكرية</a></li>
                            <li><a href="http://www.payment.bsu.edu.eg/services/" target="_blank" rel="noopener noreferrer">خدمات الدفع الإلكتروني</a></li>
                        </ul>
                    </li>
                    <li class="relative group">
                        <a href="#" class="text-bsu_dark_blue font-semibold transition-colors duration-200">الخدمات</a>
                        <ul class="dropdown-content">
                            <li><a href="certificates.php">البحث عن الشهادات</a></li>
                            <li><a href="lectures.php" class="bg-bsu_light_gray text-bsu_dark_blue font-semibold">المحاضرات الدراسية</a></li>
                        </ul>
                    </li>
                    <li><a href="../student-grades/src/index.php" class="text-gray-700 hover:text-bsu_dark_blue font-semibold transition-colors duration-200">درجات الطلاب</a></li>
                    <li><a href="../contact.html" class="text-gray-700 hover:text-bsu_dark_blue font-semibold transition-colors duration-200">اتصل بنا</a></li>
                </ul>
            </nav>
            <button class="md:hidden text-bsu_dark_blue text-2xl" id="menu-toggle" aria-controls="mobile-menu" aria-expanded="false">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        
        <div class="md:hidden bg-white shadow-lg absolute w-full top-full right-0 z-40 hidden" id="mobile-menu">
            <ul class="px-4 py-2 space-y-1">
                <li><a href="../index.html" class="block py-2 px-3 rounded hover:bg-bsu_light_gray">الرئيسية</a></li>
                <li><a href="../about.html" class="block py-2 px-3 rounded hover:bg-bsu_light_gray">عن الكلية</a></li>
                <li><a href="#" class="block py-2 px-3 rounded bg-bsu_light_gray font-semibold text-bsu_dark_blue">الخدمات</a></li>
                <li><a href="certificates.php" class="block py-2 pl-6 pr-3 rounded hover:bg-bsu_light_gray">البحث عن الشهادات</a></li>
                <li><a href="lectures.php" class="block py-2 pl-6 pr-3 rounded bg-bsu_light_gray">المحاضرات الدراسية</a></li>
                <li><a href="../contact.html" class="block py-2 px-3 rounded hover:bg-bsu_light_gray">اتصل بنا</a></li>
            </ul>
        </div>
    </header>

    <main class="py-12 md:py-16">
        <div class="container mx-auto px-4">
            <section class="page-content max-w-5xl mx-auto bg-white p-6 md:p-8 rounded-lg shadow-md animate-fade-in-up">
                <h2 class="text-2xl md:text-3xl font-bold text-bsu_dark_blue mb-4 flex items-center">
                    <i class="fas fa-book-open mr-3 text-bsu_medium_blue"></i> البحث عن المحاضرات الدراسية
                </h2>
                
                <div class="mb-4 text-left">
                    <a href="teacher_lectures.php" class="inline-flex items-center text-sm font-medium px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors duration-200">
                        <i class="fas fa-cog ml-2"></i> الذهاب لإدارة المحاضرات
                    </a>
                </div>

                <form action="lectures.php" method="get" class="filter-form contact-form bg-bsu_light_gray border border-gray-200 p-5 rounded-lg mb-8" style="box-shadow: none;">
                    <h4 class="text-lg font-semibold text-bsu_dark_blue mb-4 flex items-center"><i class="fas fa-filter mr-2"></i> تصفية النتائج</h4>
                    <div class="filter-grid grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4 items-end">
                        <div class="form-group mb-0">
                            <label for="level_id">الفرقة الدراسية</label>
                            <select id="level_id" name="level_id">
                                <option value="">-- الكل --</option>
                                <?php foreach($levels as $level): ?>
                                <option value="<?php echo $level['id']; ?>" <?php echo (isset($_GET['level_id']) && $_GET['level_id'] == $level['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($level['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group mb-0">
                            <label for="department_id">القسم</label>
                            <select id="department_id" name="department_id">
                                <option value="">-- الكل --</option>
                                <?php foreach($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>" <?php echo (isset($_GET['department_id']) && $_GET['department_id'] == $dept['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group mb-0">
                            <label for="subject_id">المادة الدراسية</label>
                            <select id="subject_id" name="subject_id">
                                <option value="">-- الكل --</option>
                                <?php foreach($subjects as $subject): ?>
                                <option value="<?php echo $subject['id']; ?>" <?php echo (isset($_GET['subject_id']) && $_GET['subject_id'] == $subject['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($subject['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group mb-0">
                            <label for="keyword">كلمة مفتاحية</label>
                            <input type="text" id="keyword" name="keyword" value="<?php echo isset($_GET['keyword']) ? htmlspecialchars($_GET['keyword']) : ''; ?>" placeholder="عنوان المحاضرة...">
                        </div>
                        <button type="submit" class="submit-btn" style="height: 46px;">
                            <i class="fas fa-search mr-2"></i> بحث
                        </button>
                    </div>
                </form>

                <?php if ($search_active): ?>
                    <h3 class="text-xl font-semibold text-bsu_dark_blue mb-4"><i class="fas fa-list-ul mr-2"></i> نتائج البحث (<?php echo count($lectures); ?>)</h3>
                    <?php if (isset($search_error)): ?>
                        <div class="p-4 text-center text-red-700 bg-red-100 border border-red-300 rounded-md"><?php echo $search_error; ?></div>
                    <?php elseif (empty($lectures)): ?>
                        <div class="p-4 text-center text-gray-600 bg-bsu_light_gray border border-gray-200 rounded-md">
                             <i class="fas fa-exclamation-circle mr-2"></i> لا توجد محاضرات تطابق معايير البحث الحالية.
                        </div>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table class="data-table min-w-full">
                                <thead>
                                    <tr>
                                        <th>العنوان</th>
                                        <th>المادة</th>
                                        <th>القسم</th>
                                        <th>الفرقة</th>
                                        <th>المدرس</th>
                                        <th>تاريخ الرفع</th>
                                        <th>تحميل</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white">
                                    <?php foreach($lectures as $lecture): ?>
                                    <tr>
                                        <td class="font-medium text-gray-900"><?php echo htmlspecialchars($lecture['title']); ?></td>
                                        <td><?php echo htmlspecialchars($lecture['subject_name']); ?></td>
                                        <td><?php echo htmlspecialchars($lecture['department_name']); ?></td>
                                        <td><?php echo htmlspecialchars($lecture['level_name']); ?></td>
                                        <td>د. <?php echo htmlspecialchars($lecture['first_name'] . ' ' . $lecture['last_name']); ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($lecture['upload_date'])); ?></td>
                                        <td class="actions">
                                            <a href="<?php echo htmlspecialchars($lecture['file_path']); ?>" target="_blank" rel="noopener noreferrer">
                                                <i class="fas fa-download mr-1"></i> تحميل
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                 <?php else: ?>
                      <div class="p-4 text-center text-gray-600 bg-bsu_light_gray border border-gray-200 rounded-md">
                           <i class="fas fa-filter mr-2"></i> الرجاء استخدام الفلاتر أعلاه للبحث عن المحاضرات.
                      </div>
                 <?php endif; ?>

            </section>
        </div>
    </main>

    <footer class="bg-gray-800 text-white py-8 mt-16">
        <div class="container mx-auto px-4 text-center">
            <p>&copy; <span id="currentYear"></span> كلية الهندسة، جامعة بني سويف. جميع الحقوق محفوظة.</p>
        </div>
    </footer>

    <script src="../script.js"></script>
    <script>
        document.getElementById('currentYear').textContent = new Date().getFullYear();
    </script>
</body>
</html>