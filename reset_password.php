<!-- 
Reset Password (reset_password)
CSC 450 Capstone Final Project Byethost
Dylan Theis: theisd@csp.edu
Keagan Harr: 
Ty Steinbach: steinbat1@csp.edu
2/04/25
Revisions: 
2/04/25: Ty Steinbach created the file, added PHP functionality to change the password, added JavaScript to handle invalid passwords, added HTML
02/16/25: Ty Steinbach added debounce function to handle input events and changed hash() to password_hash() for security, changed table to users
-->


<?php 
session_start();

//Confirm login of user from index
if (!isset($_SESSION['username']) || $_SESSION['reset'] == 0) {
    header("Location: index.php");
    exit();
}

//Database connection details
$host = 'localhost';
$user = 'root';
$pass = 'mysql'; 
$dbname = 'csc450temp';

//Connect
$conn = new mysqli($host, $user, $pass, $dbname);

//If host, db, user, or pass is incorrect create error
if ($conn->connect_error) {
    die("Failed to connect: " . $conn->connect_error);
}

//If the two password input fields exist
if(array_key_exists('txtReset',$_POST) && array_key_exists('txtConfirm',$_POST)) {
    //Var to ensure they aren't empty
    $tempPass = $_POST['txtReset'];
    $tempPass .= $_POST['txtConfirm'];
    //Hashed password
    $password = password_hash($_POST['txtConfirm'], PASSWORD_DEFAULT);

    //If passwords aren't empty
    if (!empty($tempPass)) {
        //Update password sql
        $sql = "UPDATE users SET password = ?, resetting = 0 WHERE username = ? LIMIT 1";

        //Set up a prepared statement
        if($stmt = $conn->prepare($sql)) {
            //Pass the parameters
            $stmt->bind_param("ss",$password, $_SESSION['username']);
            if($stmt->errno) {
                print_r("stmt prepare( ) had error."); 
            }
            //Execute the query
            $result = $stmt->execute();
            if($stmt->errno) {
                print_r("Could not execute prepared statement");
            }
            //If the execution occurred display message and change reset session value
            if($result) {
                print_r("Password changed successfully");
                $_SESSION['reset'] = 0;
            }
            //Close the statement
            $stmt->close( );
        }
    } else {
        print_r("Please fill in all fields");
    }
}
?>
<script>
    //Function to call later
    function events() {
        //DOM constants
        const form = document.getElementById("formReset");
        const txtReset = document.getElementById('txtReset');
        const txtConfirm = document.getElementById('txtConfirm');
        const message = document.getElementById('message');

        function checkPass() {
            const resetValue = txtReset.value;
            const confirmVal = txtConfirm.value;

            if(resetValue != confirmVal){
                message.innerText = "Passwords do not match";
            }
            else if(confirmVal.length < 8) {
                message.innerText = "Password is too short";
            }
            else {
                message.innerText = "";
            }
        }


        const debouncedCheck = debounce(checkPass, 400);

        //Ensures correct input when entering in first password field
        txtReset.addEventListener("input", debouncedCheck);

        //Ensures correct input when entering in second password field
        txtConfirm.addEventListener("input", debouncedCheck);

        //Ensures correct input when submitting form
        form.addEventListener("submit", (event) => {
            event.preventDefault(); //Prevents button default behavior
            const resetValue = txtReset.value;
            const confirmVal = txtConfirm.value;
            
            if((resetValue == "") || (confirmVal == "")) {
                message.innerText = "Please fill all fields";
            }
            else if(resetValue != confirmVal){
                message.innerText = "Passwords do not match";
            }
            else if(confirmVal.length < 8) {
                message.innerText = "Password is too short";
            }
            else {
                form.submit();
            }
        });
    }
</script>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
</head>

<body>
    <main>
        <h1>Reset Password</h1>
        <form id="formReset" method="POST" action="">
            
            <label for="txtReset">New Password</label>
            <input type="password" id="txtReset" name="txtReset" value="">

            <label for="txtConfirm">Confirm Password</label>
            <input type="password" id="txtConfirm" name="txtConfirm" value="">

            <button type="submit" id="btnReset" name="btnReset" value="reset">Submit</button>
        </form>
        <p id="message"></p>
        <p><a href="index.php">Login</a></p>
    </main>

    <!--Afformentioned function-->
    <script>events();</script>
</body>

</html>
