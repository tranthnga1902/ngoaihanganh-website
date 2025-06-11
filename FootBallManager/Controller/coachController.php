<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';


// Hàm lấy danh sách HLV với bộ lọc
function getManagers($conn, $team_id = null, $search = null) {
    $sql = "SELECT m.*, t.name AS team_name
            FROM managers m
            LEFT JOIN teams t ON m.team_id = t.team_id
            WHERE 1=1";
   
    if ($team_id) {
        $sql .= " AND m.team_id = " . (int)$team_id;
    }
    if ($search) {
        $search = mysqli_real_escape_string($conn, $search);
        $sql .= " AND m.name LIKE '%$search%'";
    }
   
    $result = mysqli_query($conn, $sql);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';


    switch ($action) {
        case 'add_manager':
            $name = mysqli_real_escape_string($conn, $_POST['name']);
            $nationality = mysqli_real_escape_string($conn, $_POST['nationality']);
            $birth_date = mysqli_real_escape_string($conn, $_POST['birth_date']);
            $start_date = mysqli_real_escape_string($conn, $_POST['start_date']);
            $team_id = (int)$_POST['team_id'];
            $information = mysqli_real_escape_string($conn, $_POST['information']);
            $photo_url = null;


            // Kiểm tra xem CLB đã có HLV chưa
            if ($team_id > 0) {
                $check_sql = "SELECT COUNT(*) as count FROM managers WHERE team_id = ?";
                $check_stmt = mysqli_prepare($conn, $check_sql);
                mysqli_stmt_bind_param($check_stmt, "i", $team_id);
                mysqli_stmt_execute($check_stmt);
                $check_result = mysqli_stmt_get_result($check_stmt);
                $check_row = mysqli_fetch_assoc($check_result);
                if ($check_row['count'] > 0) {
                    $_SESSION['error'] = "CLB này đã có huấn luyện viên. Chỉ cho phép một HLV cho mỗi CLB.";
                    header("Location: ../admin/manageCoaches.php");
                    exit;
                }
                mysqli_stmt_close($check_stmt);
            }


            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../uploads/managers/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                $photo_url = uploadFile($_FILES['image'], $upload_dir);
                if (!$photo_url) {
                    $_SESSION['error'] = "Lỗi khi tải ảnh lên.";
                    header("Location: ../admin/manageCoaches.php");
                    exit;
                }
            }


            $sql = "INSERT INTO managers (name, nationality, birth_date, start_date, team_id, photo_url, information)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ssssiss", $name, $nationality, $birth_date, $start_date, $team_id, $photo_url, $information);


            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['message'] = "Thêm HLV thành công.";
            } else {
                $_SESSION['error'] = "Thêm HLV thất bại: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
            header("Location: ../admin/manageCoaches.php");
            exit;


        case 'update_manager':
            $manager_id = (int)$_POST['manager_id'];
            $name = mysqli_real_escape_string($conn, $_POST['name']);
            $nationality = mysqli_real_escape_string($conn, $_POST['nationality']);
            $birth_date = mysqli_real_escape_string($conn, $_POST['birth_date']);
            $start_date = mysqli_real_escape_string($conn, $_POST['start_date']);
            $team_id = (int)$_POST['team_id'];
            $information = mysqli_real_escape_string($conn, $_POST['information']);
            $current_photo = $_POST['current_image'];
            $photo_url = $current_photo;


            // Lấy team_id hiện tại của HLV
            $current_team_sql = "SELECT team_id FROM managers WHERE manager_id = ?";
            $current_team_stmt = mysqli_prepare($conn, $current_team_sql);
            mysqli_stmt_bind_param($current_team_stmt, "i", $manager_id);
            mysqli_stmt_execute($current_team_stmt);
            $current_team_result = mysqli_stmt_get_result($current_team_stmt);
            $current_team = mysqli_fetch_assoc($current_team_result)['team_id'] ?? 0;
            mysqli_stmt_close($current_team_stmt);


            // Kiểm tra nếu team_id thay đổi và CLB mới đã có HLV
            if ($team_id > 0 && $team_id != $current_team) {
                $check_sql = "SELECT COUNT(*) as count FROM managers WHERE team_id = ? AND manager_id != ?";
                $check_stmt = mysqli_prepare($conn, $check_sql);
                mysqli_stmt_bind_param($check_stmt, "ii", $team_id, $manager_id);
                mysqli_stmt_execute($check_stmt);
                $check_result = mysqli_stmt_get_result($check_stmt);
                $check_row = mysqli_fetch_assoc($check_result);
                if ($check_row['count'] > 0) {
                    $_SESSION['error'] = "CLB này đã có huấn luyện viên. Chỉ cho phép một HLV cho mỗi CLB.";
                    header("Location: ../admin/manageCoaches.php");
                    exit;
                }
                mysqli_stmt_close($check_stmt);
            }


            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../uploads/managers/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                $photo_url = uploadFile($_FILES['image'], $upload_dir);
                if (!$photo_url && $current_photo) {
                    $photo_url = $current_photo;
                }
            }


            $sql = "UPDATE managers SET name = ?, nationality = ?, birth_date = ?, start_date = ?, team_id = ?, photo_url = ?, information = ?
                    WHERE manager_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ssssissi", $name, $nationality, $birth_date, $start_date, $team_id, $photo_url, $information, $manager_id);


            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['message'] = "Cập nhật HLV thành công.";
            } else {
                $_SESSION['error'] = "Cập nhật HLV thất bại: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
            header("Location: ../admin/manageCoaches.php");
            exit;


        case 'delete_manager':
            $manager_id = (int)$_POST['manager_id'];
            $sql = "DELETE FROM managers WHERE manager_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $manager_id);


            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['message'] = "Xóa HLV thành công.";
            } else {
                $_SESSION['error'] = "Xóa HLV thất bại.";
            }
            mysqli_stmt_close($stmt);
            header("Location: ../admin/manageCoaches.php");
            exit;


        default:
            header("Location: ../admin/manageCoaches.php");
            exit;
    }
}


// Xuất Excel bằng XML Spreadsheet
if (isset($_GET['action']) && $_GET['action'] === 'export_excel') {
    ob_end_clean(); // Xóa output trước khi gửi header


    // Thiết lập header cho XML Spreadsheet
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="managers_' . date('Ymd_His') . '.xls"');
    header('Cache-Control: max-age=0');


    // Thêm BOM để Excel nhận diện UTF-8 (hỗ trợ tiếng Việt)
    echo "\xEF\xBB\xBF";


    // Bắt đầu tạo nội dung XML
    $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
    <?mso-application progid="Excel.Sheet"?>
    <Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
            xmlns:o="urn:schemas-microsoft-com:office:office"
            xmlns:x="urn:schemas-microsoft-com:office:excel"
            xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
            xmlns:html="http://www.w3.org/TR/REC-html40">
        <Styles>
            <Style ss:ID="Header">
                <Font ss:Bold="1" ss:Color="#FFFFFF"/>
                <Interior ss:Color="#4472C4" ss:Pattern="Solid"/>
                <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
                <Borders>
                    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
                </Borders>
            </Style>
            <Style ss:ID="Data">
                <Alignment ss:Horizontal="Left" ss:Vertical="Center"/>
                <Borders>
                    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
                </Borders>
            </Style>
        </Styles>
        <Worksheet ss:Name="Danh sách HLV">
            <Table>';


    // Định nghĩa độ rộng cột
    $xml .= '<Column ss:Index="1" ss:AutoFitWidth="0" ss:Width="50"/>'; // ID
    $xml .= '<Column ss:Index="2" ss:AutoFitWidth="0" ss:Width="150"/>'; // Tên
    $xml .= '<Column ss:Index="3" ss:AutoFitWidth="0" ss:Width="100"/>'; // Quốc tịch
    $xml .= '<Column ss:Index="4" ss:AutoFitWidth="0" ss:Width="100"/>'; // Ngày sinh
    $xml .= '<Column ss:Index="5" ss:AutoFitWidth="0" ss:Width="100"/>'; // Ngày bắt đầu
    $xml .= '<Column ss:Index="6" ss:AutoFitWidth="0" ss:Width="120"/>'; // Đội bóng
    $xml .= '<Column ss:Index="7" ss:AutoFitWidth="0" ss:Width="200"/>'; // Thông tin


    // Thêm header
    $xml .= '<Row ss:StyleID="Header">
                <Cell><Data ss:Type="String">ID</Data></Cell>
                <Cell><Data ss:Type="String">Tên</Data></Cell>
                <Cell><Data ss:Type="String">Quốc tịch</Data></Cell>
                <Cell><Data ss:Type="String">Ngày sinh</Data></Cell>
                <Cell><Data ss:Type="String">Ngày bắt đầu</Data></Cell>
                <Cell><Data ss:Type="String">Đội bóng</Data></Cell>
                <Cell><Data ss:Type="String">Thông tin</Data></Cell>
            </Row>';


    // Lấy dữ liệu từ database
    $sql = "SELECT m.*, t.name AS team_name
            FROM managers m
            LEFT JOIN teams t ON m.team_id = t.team_id";
    $result = mysqli_query($conn, $sql);
   
    if (!$result) {
        die("Query failed: " . mysqli_error($conn));
    }


    // Thêm dữ liệu
    while ($manager = mysqli_fetch_assoc($result)) {
        $xml .= '<Row ss:StyleID="Data">
                    <Cell><Data ss:Type="Number">' . htmlspecialchars($manager['manager_id']) . '</Data></Cell>
                    <Cell><Data ss:Type="String">' . htmlspecialchars($manager['name']) . '</Data></Cell>
                    <Cell><Data ss:Type="String">' . htmlspecialchars($manager['nationality']) . '</Data></Cell>
                    <Cell><Data ss:Type="String">' . htmlspecialchars($manager['birth_date']) . '</Data></Cell>
                    <Cell><Data ss:Type="String">' . htmlspecialchars($manager['start_date']) . '</Data></Cell>
                    <Cell><Data ss:Type="String">' . htmlspecialchars($manager['team_name'] ?: 'Chưa có đội') . '</Data></Cell>
                    <Cell><Data ss:Type="String">' . htmlspecialchars($manager['information'] ?: '') . '</Data></Cell>
                </Row>';
    }


    // Kết thúc file XML
    $xml .= '</Table>
        </Worksheet>
    </Workbook>';


    // Gửi nội dung XML
    echo $xml;
    exit;
}


function uploadFile($file, $dir) {
    $target_dir = $dir;
    $target_file = $target_dir . basename($file["name"]);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    $unique_name = time() . '_' . rand(1000, 9999) . '.' . $imageFileType;
    $target_file = $target_dir . $unique_name;


    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return str_replace('../', '', $target_file);
    }
    return false;
}
?>

