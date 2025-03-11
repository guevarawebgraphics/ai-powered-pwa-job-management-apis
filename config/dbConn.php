<?php
if ($_SERVER['HTTP_HOST'] == 'localhost') {
    $mysql_host = 'localhost';
    $mysql_username = 'root';
    $mysql_password = '';
    $mysql_db = 'lr_aar_dv';

    $base_url = (isset($_SERVER['HTTPS']) ? "https://" : "http://") . "127.0.0.1:8080/";
} else {
    $mysql_host = 'localhost';
    $mysql_username = 'root';
    $mysql_password = '';
    $mysql_db = 'lr_aar_dv';

    $base_url = (isset($_SERVER['HTTPS']) ? "https://" : "http://") . "127.0.0.1:8080/";
}
$dbConn = mysqli_connect($mysql_host, $mysql_username, $mysql_password, $mysql_db);

header("Access-Control-Allow-Origin: http://127.0.0.1"); // ✅ Must match the frontend exactly
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With"); // ✅ Add X-Requested-With
header("Access-Control-Allow-Credentials: true"); // ✅ Ensure it's true if credentials are used

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
// if(!$dbConn)
// die("<b>NOT ABLE TO CONNECT DATABASE</b>.");
// @mysqli_select_db($db_name) or die("<b>NOT ABLE TO CONNECT DATABASE</b>.");
// $levels = array(1=>"Admin", 2=>"Employee");
// $inpage = 20;
