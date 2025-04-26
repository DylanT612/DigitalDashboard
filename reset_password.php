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
    header("Location: login.php");
    exit();
}

//Database connection details
$host = '';
$user = '';
$pass = ''; 
$dbname = '';

//Connect
$conn = new mysqli($host, $user, $pass, $dbname);

//If host, db, user, or pass is incorrect create error
if ($conn->connect_error) {
    die("Failed to connect: " . $conn->connect_error);
}

$errorMessage = "";
$messageType = "error";
$redirect = false;
$redirectPage = "";

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
            //Execute the query
            $result = $stmt->execute();
            if($stmt->errno) {
                $errorMessage = "Could not execute prepared statement";
                $messageType = "error";
            }
            //If the execution occurred display message and change reset session value
            if($result) {
                $redirect = true;
                $errorMessage = "Password changed successfully. Redirecting to login...";
                $messageType = "success";
                $redirectPage = "login.php";
                
            }
            //Close the statement
            $stmt->close( );
        }
    } else {
        $errorMessage = "Please fill in all fields";
        $messageType = "error";
    }
}
?>
<script>
    function debounce(func, delay) {
        let timeoutId;
        return function(...args) {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => func.apply(this, args), delay);
        };
    }
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
                header("Location: login.php");
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
    <link rel="stylesheet" href="Styles/resetPassStyles.css">
    <link rel="preload" as="image" href="Images/digital.jpg">
    <style>
        body {
            background: url('Images/digital.jpg') no-repeat center center fixed;
            background-size: cover;
            position: relative;
        }
    </style>
</head>

<body>
    <div class="background"></div>
    <div class="main-container">
        <div class="message-container <?php echo isset($messageType) ? $messageType : ''; ?>" style="<?php echo !empty($errorMessage) ? 'display:block;' : 'display:none;'; ?>">
            <?php echo !empty($errorMessage) ? $errorMessage : ''; ?>
        </div>
        <h1>Reset Password</h1>
        <form id="formReset" method="POST" action="">
            
            <label for="txtReset">New Password</label>
            <input type="password" id="txtReset" name="txtReset" value="">

            <label for="txtConfirm">Confirm Password</label>
            <input type="password" id="txtConfirm" name="txtConfirm" value="">

            <button type="submit" id="btnReset" name="btnReset" value="reset">Submit</button>
            <div id="message" class="error"></div>
        </form>
        <p><a href="login.php" class="link-button">Back To Home Page</a></p>
    </main>

    <!--Afformentioned function-->
    <script>events();</script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handling Redirects
            <?php if ($redirect && $redirectPage): ?>
                setTimeout(function() {
                    window.location.href = "<?php echo $redirectPage; ?>"; // Use the PHP variable to control the redirect
                }, 5000);
            <?php endif; ?>

            // Handling Message Disappearance
            const messageContainer = document.querySelector('.message-container');
            if (messageContainer && messageContainer.style.display !== 'none') {
                setTimeout(function() {
                    messageContainer.style.display = 'none'; // Hide the message
                }, 5000); // Adjust as necessary
            }
        });
    </script>
</body>

</html>
