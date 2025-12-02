<?php
// 1. 开启 Session (必须在文件最第一行)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>User Login</title>
    <link rel="stylesheet" href="../css/login.css">
</head>

<body>
    <?php
    require('database.php');

    if (isset($_POST['Firstname'])) {
        // 防止 SQL 注入的处理
        $firstname = stripslashes($_REQUEST['Firstname']);
        $firstname = mysqli_real_escape_string($con, $firstname);

        $lastname = stripslashes($_REQUEST['Lastname']);
        $lastname = mysqli_real_escape_string($con, $lastname);

        $password = stripslashes($_REQUEST['password']);
        $password = mysqli_real_escape_string($con, $password);

        // 使用 MD5 加密密码进行比对
        $query = "SELECT * FROM `user` WHERE `First_Name`='$firstname' AND `Last_Name`='$lastname' AND `Password`='" . md5($password) . "'";
        $result = mysqli_query($con, $query) or die(mysqli_error($con));
        
        $rows = mysqli_num_rows($result);

        if ($rows == 1) {
            // =======================================================
            // 核心逻辑：从数据库抓取这一行数据
            // =======================================================
            $row = mysqli_fetch_assoc($result);

            // 存入 Session
            $_SESSION['Firstname'] = $row['First_Name'];
            $_SESSION['Lastname']  = $row['Last_Name'];
            $_SESSION['Email']     = $row['Email']; 
            
            // 【关键】存入刚刚调试成功的 User_ID (例如 22)
            $_SESSION['user_id']   = $row['User_ID']; 

            // 跳转到首页
            echo "<script>window.location.href='home.php';</script>";
            exit();
        } else {
            // 登录失败提示
            echo "<div class='form'> 
                  <h3>Username/password is incorrect.</h3> 
                  <br/>Click here to <a href='index.php'>Login</a></div>";
        }
    } else {
    ?>
        <div class="form">
            <h1>User Log In</h1>
            <form action="" method="post" name="login">
                <input type="text" name="Firstname" placeholder="Firstname" required /><br>
                <input type="text" name="Lastname" placeholder="Lastname" required /><br>
                <input type="password" name="password" placeholder="Password" required /><br>
                <input name="submit" type="submit" value="Login" />
            </form>
            <p>Not registered yet? <a href='signup.php'>Register Here</a></p>
        </div>
    <?php } ?>
</body>
</html>