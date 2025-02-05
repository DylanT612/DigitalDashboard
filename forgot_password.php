<!-- 
Forgot Password page(forgot_password)
CSC 450 Capstone Final Project Byethost
Dylan Theis: theisd@csp.edu
Keagan Harr: 
Ty Steinbach: steinbat1@csp.edu
1/25/25
Revisions: 
1/25/25: Dylan Theis created html doc outline
02/04/25: Ty Steinbach added PHP functionality to change password to temp password and email that password to correct user.
-->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <?php 

        //PHPMailer classes
        require 'C:/Program Files/Ampps/www/CSC450/src/Exception.php';
        require 'C:/Program Files/Ampps/www/CSC450/src/PHPMailer.php';
        require 'C:/Program Files/Ampps/www/CSC450/src/SMTP.php';

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

        //If the email field exists
        if(array_key_exists('txtEmailForgot',$_POST)) {
            $email = $_POST['txtEmailForgot']; //Setting var

            //If the email field is not empty
            if (!empty($email)) {
                //Select user from email
                $sql = "SELECT * FROM dashboard_login WHERE email = ? LIMIT 1";
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
                    $passTemp = hash('sha256', $pass); //Hash code

                    //SQL to update
                    $sql = "UPDATE dashboard_login SET password = ?, resetting = 1 WHERE email = ? LIMIT 1";

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
                                echo 'Email has been sent!';
                            } catch (Exception $e) {
                                echo "Email could not be sent. Error: {$mail->ErrorInfo}";
                            }
                        }
                        //Close the connection
                        $stmt->close( );
                    }
                } else {
                    print_r("Invalid email");
                }
            //If no input for email
            } else {
                print_r("Please fill in all fields");
            }
        }
    ?>
</head>

<body>
    <main>
        <h1>Forgot Password?</h1>
        <p>Enter your email for a temporary password if an account exists</p>
        <form method="POST" action="">
            <label for="`txtEmailForgot">Email</label>
            <input type="text" id="txtEmailForgot" name="txtEmailForgot" placeholder="example@email.com">

            <button type="submit" id="btnSubmitForgot">Submit</button>
        </form>
        <p><a href="index.php">Login</a></p>
    </main>
</body>

</html>
