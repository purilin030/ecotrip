<?php
require('database.php');

if (isset($_REQUEST['firstname'])) {
    $firstname = stripslashes($_REQUEST['firstname']);
    $firstname = mysqli_real_escape_string($con, $firstname);

    $lastname = stripslashes($_REQUEST['lastname']);
    $lastname = mysqli_real_escape_string($con, $lastname);

    $email = stripslashes($_REQUEST['email']);
    $email = mysqli_real_escape_string($con, $email);

    $password = stripslashes($_REQUEST['password']);
    $password = mysqli_real_escape_string($con, $password);

    $reg_date = date("Y-m-d H:i:s");

    $query = "INSERT into `user` (First_Name, Last_Name, Password, Email, Register_Date) 
    VALUES ('$firstname', '$lastname', '" . md5($password) . "', '$email', '$reg_date')";
    
    $result = mysqli_query($con, $query);
    
    if ($result) {
        // 注册成功，提示去 index.php 登录
        echo "<div class='form'> 
        <h3>You are registered successfully.</h3> 
        <br/>Click here to <a href='index.php'>Login</a></div>";
    }
} else {
?>
    <div class="form">
        <h1>User Registration</h1>
        <form name="registration" action="" method="post">
            <input type="text" name="firstname" placeholder="Firstname" required /><br>
            <input type="text" name="lastname" placeholder="Lastname" required /><br>
            <input type="email" name="email" placeholder="Email" required /><br>
            <input type="password" name="password" placeholder="Password" required /><br>
            <input type="submit" name="submit" value="Register" />
        </form>
        <p>Already have an account? <a href='index.php'>Login Here</a></p>
    </div>
<?php } ?>