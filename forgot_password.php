<!-- 
Forgot Password page(forgot_password)
CSC 450 Capstone Final Project Byethost
Dylan Theis: theisd@csp.edu
Keagan Haar: haark@csp.edu
Ty Steinbach: steinbat1@csp.edu
1/25/25
Revisions: 
1/25/25: Dylan Theis created html doc outline
02/04/25: Ty Steinbach added PHP functionality to change password to temp password and email that password to correct user.
02/16/25: Ty Steinbach changed hash() to password_hash() for security, changed table to users
-->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="forgotPassStyles.css">
    <?php 

        //PHPMailer classes
        require 'C:/Program Files/Ampps/www/DigitalDashboard/src/Exception.php';
        require 'C:/Program Files/Ampps/www/DigitalDashboard/src/PHPMailer.php';
        require 'C:/Program Files/Ampps/www/DigitalDashboard/src/SMTP.php';

        //Using PHPMailer stuff
        use PHPMailer\PHPMailer\PHPMailer;
        use PHPMailer\PHPMailer\Exception;


        //Database connection details
        $host = '';
        $user = '';
        $pass = ''; 
        $dbname = '';

        //Establish connection
        $conn = new mysqli($host, $user, $pass, $dbname);

        //If host, db, user, or pass is incorrect create error
        if ($conn->connect_error) {
            die("Failed to connect: " . $conn->connect_error);
        }

        $errorMessage = "";
        $messageType = "error";

        //If the email field exists
        if(array_key_exists('txtEmailForgot',$_POST)) {
            $email = $_POST['txtEmailForgot']; //Setting var

            //If the email field is not empty
            if (!empty($email)) {
                //Select user from email
                $sql = "SELECT * FROM users WHERE email = ? LIMIT 1";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s", $email);
                //Retrieve credentials
                $stmt->execute();
                $result = $stmt->get_result();
                
                //Verify user exists
                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc(); //Get results in manageable form
                    $stmt->close(); //Close connection
                    $pass = bin2hex(random_bytes(3)); //Create random secure 6 digit code
                    $passTemp = password_hash( $pass, PASSWORD_DEFAULT); //Hash code

                    //SQL to update
                    $sql = "UPDATE users SET password = ?, resetting = 1 WHERE email = ? LIMIT 1";

                    // Set up a prepared statement
                    if($stmt = $conn->prepare($sql)) {
                        // Pass the parameters
                        $stmt->bind_param("ss",$passTemp, $email);
                        if($stmt->errno) {
                            print_r("stmt prepare( ) had error."); 
                        }
                        // Execute the query
                        $result = $stmt->execute();
                        if($stmt->errno) {
                            print_r("Could not execute prepared statement");
                        }
                        //If the execution occurred, email the new passoword
                        if($result) {
                            // Create a new PHPMailer instance
                            $mail = new PHPMailer(true);
                            try {
                                //Server settings
                                $mail->isSMTP(); //Use SMTP
                                $mail->Host = 'smtp.gmail.com'; //SMTP server (e.g., Gmail)
                                $mail->SMTPAuth = true; //Enable SMTP authentication
                                $mail->Username = 'userdashboardcsp@gmail.com'; //Email address
                                $mail->Password = 'kjnd biwe zuvl weyv'; //Email temp password
                                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; //Enable TLS encryption
                                $mail->Port = 587; //TCP port to connect to
                            
                                $mail->setFrom('userdashboardcsp@gmail.com', 'User Dashboard'); //Sender
                                $mail->addAddress($email, $user['username']); //Recipient
                            
                                // Content
                                $mail->isHTML(true); // Set email format to HTML
                                $mail->Subject = "Reset Password"; //Subject
                                $mail->Body = "
                                    <h2>Reset Password</h2>
                                    <p>
                                        Hello " . $user['username'] . ", <br/>
                                        Below is a temporary password you can use to log in. After logging in you will be prompted to reset your password. <br/><br/><br/>
                                        " . $pass . "
                                    </p>
                                "; //Body
                            
                                // Send the email
                                $mail->send();
                                $redirect = true;
                                $errorMessage = "Email has been sent! Redirecting to login...";
                                $messageType = "success";
                                $redirectPage = "login.php";
                            } catch (Exception $e) {
                                $errorMessage = "Email could not be sent.";
                                $messageType = "error";
                            }
                        }
                        //Close the connection
                        $stmt->close( );
                    }
                } else {
                    $errorMessage = "Invalid email";
                    $messageType = "error";
                }
            //If no input for email
            } else {
                $errorMessage = "Please fill in all fields";
                $messageType = "error";
            }
        }
    ?>
</head>

<body>
    <div class="background"></div>
    <div class="main-container">
        <div class="message-container <?php echo isset($messageType) ? $messageType : ''; ?>" style="<?php echo !empty($errorMessage) ? 'display:block;' : 'display:none;'; ?>">
                <?php echo !empty($errorMessage) ? $errorMessage : ''; ?>
        </div>
        <h1>Forgot Password?</h1>
        <p>Enter your email for a temporary password (if account exists)</p>
        <form method="POST" action="">
            <label for="`txtEmailForgot">Email</label>
            <input type="text" id="txtEmailForgot" name="txtEmailForgot" placeholder="example@email.com">

            <button type="submit" id="btnSubmitForgot">Submit</button>
            <p><a href="login.php" class="link-button">Back to Login</a></p>
        </form>
    </main>

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
