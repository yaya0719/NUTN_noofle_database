<?php
// 資料庫連接設定
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "noodledata";

$conn = new mysqli($servername, $username, $password, $dbname);

// 檢查連接
if ($conn->connect_error) {
    die("資料庫連接失敗: " . $conn->connect_error); #中止腳本執行函數
}

// 初始化變數，isset()：檢查 $_GET['page'] 是否存在、intval()：將 $_GET['orderid'] 轉換為整數。
$page = isset($_GET['page']) ? $_GET['page'] : 'orderr';
$orderid = isset($_GET['orderid']) ? intval($_GET['orderid']) : null;

// 顯示資料表的函式，conn:資料庫、sql:查詢的值、headers:標頭欄
function displayTable($conn, $sql, $headers) {
    echo "<table>";
    echo "<tr>";
    //輸出標頭欄
    foreach ($headers as $header) {
        echo "<th>$header</th>";
    }
    echo "</tr>";

    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        /*逐行取得查詢結果，並將每一行資料轉換為 關聯陣列。
        例如，$row = ['name' => 'John', 'price' => '100']*/ 
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            foreach ($headers as $field) {
                //將資料轉換成 HTML 安全格式，防止 XSS 攻擊
                echo "<td>" . htmlspecialchars($row[$field]) . "</td>";
            }
            echo "</tr>";
        }
    } else {
        //colspan：讓訊息橫跨整個表格的所有欄位
        echo "<tr><td colspan='" . count($headers) . "'>No records found</td></tr>";
    }
    echo "</table>";
}

// 檢查 ID 是否存在的函數，conn:資料庫、$table：資料表名稱，例如 EMPLOYEE 或 orderinform
//$column：欄位名稱，例如 id 或 customerid、$id：要檢查的值
function isIdExists($conn, $table, $column, $id) {
    $sql = "SELECT * FROM $table WHERE $column = '$id'";
    return $conn->query($sql)->num_rows > 0;
}

// 新增資料的函數，conn:資料庫、$table：資料表名稱，例如 EMPLOYEE 或 orderinform
//$data：一個關聯陣列，包含要插入的欄位名稱與值。範例：['name' => 'John', 'age' => 30]
//$redirectPage：新增成功後要重新導向的頁面
function addRecord($conn, $table, $data, $redirectPage) {
    //implode()：將鍵與值用逗號分隔，生成 SQL 語句
    $columns = implode(", ", array_keys($data));//array_keys($data)：取得資料陣列的鍵（欄位名稱）
    $values = "'" . implode("', '", array_values($data)) . "'";//array_values($data)：取得資料陣列的值
    $sql = "INSERT INTO $table ($columns) VALUES ($values)";
    
    if ($conn->query($sql)) {
        //header() 重新導向到指定頁面
        header("Location: ?page=$redirectPage");
        exit();
    } else {
        echo "<script>alert('新增失敗: " . $conn->error . "');</script>";
    }
}

// 刪除資料的函數
function deleteRecord($conn, $table, $column, $id, $redirectPage) {
    $sql = "DELETE FROM $table WHERE $column = $id";
    if ($conn->query($sql)) {
        $conn->query("ALTER TABLE $table AUTO_INCREMENT = 1");
        header("Location: ?page=$redirectPage");
        exit();
    } else {
        echo "<script>alert('刪除失敗: " . $conn->error . "');</script>";
    }
}

// 顯示資料表含刪除功能的函數
function displayTable_Deletion($conn, $table, $columns, $idColumn, $deletePage) {
    //查詢SQL指令
    $sql = "SELECT * FROM $table";
    $result = $conn->query($sql);
    //輸出標頭欄
    echo "<table><tr>";
    foreach ($columns as $col) {
        echo "<th>$col</th>";
    }
    echo "<th>操作</th></tr>";

    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        foreach ($columns as $col) {
            echo "<td>{$row[$col]}</td>";
        }
        //?page=$deletePage：指定刪除操作後要導向的頁面。delete_：標記刪除的操作。{$table}：資料表名稱。{$row[$idColumn]}：該行資料的主鍵值。
        echo "<td><a href='?page=$deletePage&delete_{$table}={$row[$idColumn]}'>刪除</a></td>";
        echo "</tr>";
    }
    echo "</table>";
}


// 獲取當前頁面
$page = isset($_GET['page']) ? $_GET['page'] : 'orderr';
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>餐飲點餐系統管理</title>
    <link rel="stylesheet" href="noodle_display.css">
</head>
<body>
    <h1>餐飲點餐系統管理</h1>

    <!-- 導覽列 -->
    <div class="navbar">
        <a href="?page=EMPLOYEE">員工資料</a>
        <a href="?page=菜單">菜單資料</a>
        <a href="?page=customer">顧客資料</a>
        <a href="?page=orderr">訂單資料</a>
    </div>

    <?php
    if ($page == 'EMPLOYEE') {
        echo "<h2>員工資料</h2>";
    
        // 新增資料處理
        // $_SERVER['REQUEST_METHOD']：確認 HTTP 請求的方式為 POST，表示用戶提交了表單資料。
        // isset($_POST['add_employee'])：確認提交的表單中包含 add_employee(在下面的表單) 這個按鈕，確保是「新增員工」的請求。
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_employee'])) {
            $sid = $_POST['sid'];
            if (isIdExists($conn, 'EMPLOYEE', 'sid', $sid)) {
                echo "<script>alert('員工ID 已存在，請使用不同的ID！');</script>";
            } else {
                addRecord($conn, 'EMPLOYEE', [
                    'name' => $_POST['name'],
                    'Bdate' => $_POST['Bdate'],
                    'Sex' => $_POST['Sex'],
                    'Salary' => $_POST['Salary'],
                    'sid' => $sid
                ], 'EMPLOYEE');
            }
        }
    
        // 刪除資料處理
        if (isset($_GET['delete_EMPLOYEE'])) {
            deleteRecord($conn, 'EMPLOYEE', 'sid', intval($_GET['delete_EMPLOYEE']), 'EMPLOYEE');
        }
    
        // 顯示新增表單
        echo "<form method='POST'>
                <input type='text' name='name' placeholder='姓名' required>
                <input type='date' name='Bdate' placeholder='生日' required>
                <input type='text' name='Sex' placeholder='性別' required>
                <input type='number' name='Salary' placeholder='薪水' required>
                <input type='number' name='sid' placeholder='員工ID' required>
                <button type='submit' name='add_employee'>新增員工</button>
              </form>";
    
        // 顯示資料表
        displayTable_Deletion($conn, 'EMPLOYEE', ['name', 'Bdate', 'Sex', 'Salary', 'sid'], 'sid', 'EMPLOYEE');
    } elseif ($page == '菜單') {
        echo "<h2>菜單資料</h2>";
    
        // 新增資料處理
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_menu'])) {
            $mid = $_POST['mid'];
            if (isIdExists($conn, '菜單', 'mid', $mid)) {
                echo "<script>alert('餐點ID 已存在，請使用不同的ID！');</script>";
            } else {
                addRecord($conn, '菜單', [
                    'name' => $_POST['name'],
                    'Size' => $_POST['Size'],
                    'price' => $_POST['price'],
                    'mid' => $mid
                ], '菜單');
            }
        }
    
        // 刪除資料處理
        if (isset($_GET['delete_菜單'])) {
            deleteRecord($conn, '菜單', 'mid', intval($_GET['delete_菜單']), '菜單');
        }
    
        // 顯示新增表單
        echo "<form method='POST'>
                <input type='text' name='name' placeholder='餐點名稱' required>
                <input type='text' name='Size' placeholder='大小'>
                <input type='number' name='price' placeholder='價格' required>
                <input type='number' name='mid' placeholder='餐點ID' required>
                <button type='submit' name='add_menu'>新增餐點</button>
              </form>";
    
        // 顯示資料表
        displayTable_Deletion($conn, '菜單', ['name', 'Size', 'price', 'mid'], 'mid', '菜單');
    }    
     elseif ($page == 'customer') {
        echo "<h2>顧客資料</h2>";
        $sql = "SELECT * FROM customer";
        displayTable($conn, $sql, ["name", "phonenumber", "customerid"]);
    }  elseif ($page == 'orderr') {
        echo "<h2>訂單資料</h2>";
        $sql = "SELECT * FROM orderr";
        echo "<table>";
        echo "<tr><th>顧客ID</th><th>時間</th><th>總金額</th><th>訂單ID</th></tr>";
        $result = $conn->query($sql);
        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                <td>{$row['customerid']}</td>
                <td>{$row['ordtime']}</td>
                <td>{$row['totalprice']}</td>
                <td><a href='?page=details&orderid={$row['orderid']}'>{$row['orderid']}</a></td>
            </tr>";
        }
        echo "</table>";
    } elseif ($page == 'details' && $orderid) {
        // 訂單細項
        echo "<h2>訂單細項 (Order ID: $orderid)</h2>";
    
        // 查詢 orderinform 表，根據 oid（訂單ID）顯示細項
        $sql = "
            SELECT orderinform.ordish AS item, 
                   orderinform.size AS size, 
                   orderinform.orderprice AS price, 
                   orderinform.phonenumber AS phonenumber,
                   orderinform.spicy AS spicy,
                   orderinform.coriander AS coriander,
                   orderinform.quantity AS quantity
            FROM orderinform
            WHERE orderinform.oid = $orderid
        ";
    
        // 顯示表格
        displayTable($conn, $sql, ["item", "size", "price", "phonenumber", "spicy", "coriander", "quantity"]);
    
        echo "<p style='text-align:center;'><a href='?page=orderr'>返回訂單列表</a></p>";
    }
    ?>

</body>
</html>

<?php $conn->close(); ?>