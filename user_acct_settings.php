<?php
    session_start();
    if (!isset($_SESSION['username'])) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'user not authenticated']);
            exit();
        }
    // Database connection details
    $host = '';
    $user = '';
    $pass = '';
    $dbname = '';

    $conn = new mysqli($host, $user, $pass, $dbname);

    // If host, db, user, or pass is incorrect create error
    if ($conn->connect_error) {
        die("Failed to connect: " . $conn->connect_error);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if(array_key_exists('formUserInfo',$_POST)) {
            // Sanitize form input
            $first_name = $conn->real_escape_string($_POST['txtFirstName']);
            $last_name = $conn->real_escape_string($_POST['txtLastName']);
            $email = $conn->real_escape_string($_POST['txtEmail']);
            $username = $conn->real_escape_string($_POST['txtUsername']);
            $birth_date = isset($_POST['datBday']) ? $conn->real_escape_string($_POST['datBday']) : NULL;
            $city = $conn->real_escape_string($_POST['city']);
            $state = $conn->real_escape_string($_POST['state']);
            $country = $conn->real_escape_string($_POST['country']);
            
            // Handle file upload (profile picture)
            $profile_picture = "uploads/blankProfile.png";
            if (isset($_FILES['picture']) && $_FILES['picture']['error'] === UPLOAD_ERR_OK) {
                $target_dir = "uploads/";
                $target_file = $target_dir . basename($_FILES["picture"]["name"]);
                $fileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
                
                // Check if file is an image
                if (in_array($fileType, ['jpg', 'png', 'jpeg'])) {
                    if (move_uploaded_file($_FILES["picture"]["tmp_name"], $target_file)) {
                        $profile_picture = $target_file;
                    } else {
                        print_r( "Sorry, there was an error uploading your file.");
                    }
                } else {
                    print_r("Only JPG, PNG, and JPEG files are allowed.");
                }
            }
        
            // $sql for prepared statement
            $sql = "UPDATE users SET 
                first_name = ?,
                last_name = ?,
                email = ?,
                username = ?,
                birth_date = ?,
                city = ?,
                id_state = ?,
                id_country = ?,
                profile_picture = ?
                WHERE username = ?";
            // Prepare
            if($stmt = $conn->prepare($sql)) {
                // Bind the parameters
                $stmt->bind_param("ssssssssss", $first_name, $last_name, $email, $username, $birth_date, $city, $state, $country, $profile_picture, $_SESSION['username']); 
                if($stmt->errno) {
                    print_r("stmt prepare( ) had error."); 
                }
                // Execute the query
                $stmt->execute();
                if($stmt->errno) {
                    print_r("Could not execute prepared statement");
                    $result = false;
                }
                else {
                    $result = true;
                }

                // Free results
                $stmt->free_result( );

                // Close the statement
                $stmt->close( );
            }

            //Display message if successful
            if($result) {
                print_r("User successfully updated");
            }
        }
        if(array_key_exists('formPassword',$_POST)) {
            $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
            $stmt->bind_param("s", $_SESSION['username']);
            // Retrieve credentials
            $stmt->execute();
            $result = $stmt->get_result();
            // Verify username exists
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                // Verify correct password

                if (password_verify($_POST['oldPassword'], $user['password'])) {
                    $stmt->close();
                    $newPassword = password_hash($_POST['confirmPassword'], PASSWORD_DEFAULT);
                    
                    // $sql for prepared statement
                    $sql = "UPDATE users SET 
                        password = ?
                        WHERE username = ?";
                    // Prepare
                    if($stmt = $conn->prepare($sql)) {
                        // Bind the parameters
                        $stmt->bind_param("ss",  $newPassword, $_SESSION['username']); 
                        if($stmt->errno) {
                            print_r("stmt prepare( ) had error."); 
                        }
                        // Execute the query
                        $stmt->execute();
                        if($stmt->errno) {
                            print_r("Could not execute prepared statement");
                            $result = false;
                        }
                        else {
                            $result = true;
                        }

                        // Free results
                        $stmt->free_result( );

                        // Close the statement
                        $stmt->close( );
                    }

                    //Display message if successful
                    if($result) {
                        print_r("User successfully updated");
                    }
                } else {
                    print_r("Invalid password");
                }

            } else {
                print_r("Invalid username");
            }
            
        }
    }
    //Selects appropriate transaction and makes sure the data stays in its input elements

    $sql = "SELECT * FROM users JOIN states ON users.id_state = states.stateCode JOIN countries ON users.id_country = countries.countryCode WHERE username = ? LIMIT 1";

    // Set up a prepared statement
    if($stmt = $conn->prepare($sql)) {

        // Pass the parameters
        $stmt->bind_param("s", $_SESSION['username']);

        if($stmt->errno) {
            print_r("stmt prepare( ) had error."); 
        }

        // Execute the query
        $stmt->execute();
        if($stmt->errno) {
            print_r("Could not execute prepared statement");
        }

        // Fetch the results
        $result = $stmt->get_result();

        // Free results
        $stmt->free_result( );
        
        // Close the statement
        $stmt->close( );
    } // end of if($conn->prepare($sql))

    $row = $result->fetch_assoc();

    $thisUser = [
        "first_name" => $row["first_name"],
        "last_name" => $row["last_name"],
        "email" => $row["email"],
        "username" => $row["username"],
        "password" => $row["password"],
        "birth_date" => $row["birth_date"],
        "city" => $row["city"],
        "state" => $row["stateCode"],
        "country" => $row["countryCode"],
        "profile_picture" => $row["profile_picture"]
    ];   

    

?>
<!DOCTYPE html>
<!-- 
User Account Settings (user_acct_settings)
CSC 450 Capstone Final Project Byethost
Dylan Theis: theisd@csp.edu
Keagan Harr: 
Ty Steinbach:
1/25/25
Revisions: 
1/25/25: Dylan Theis created php session verifier and html doc outline
2/08/25: Dylan Theis created news ticker (css, html, JS) began working on creating weather api as well
2/09/25: Dylan Theis linked API and wrote (css, html, JS) for weather api for items such as city, temperature, SVGs, forecast boxes
2/11/25: Dylan Theis made advancements on weather, features such as feels like, city time, added cityName api to find whatever city based on coords. Improved getClothingRec function
2/12/25: Dylan Theis added wind and made small edits(colors, etc)
02/14/25: Ty Steinbach started a wireframe and started implimenting forms
02/15/25: Ty Steinbach added full PHP functionality 
02/16/25: Ty Steinbach styled
02/27/25: Ty Steinbach ensured first select options are disabled
-->
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Account Settings</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            /* Background container */
            .background {
                position: absolute;
                width: 100vw;
                height: 100vh;
                background: url('./Images/Geo.jpg');
                filter: blur(5px);
                z-index: -1; /* Makes background lower stack order than content */
            }
            main {
                display: grid;
                grid-template-columns: 20% 50% 30%;
                grid-template-rows: 100px auto auto;

                grid-template-areas: 
                    "header header header"
                    "settings formUserInfo formUserInfo"
                    ". formPassword .";
                filter: none;
                color: white;
                background-color: rgb(93, 93, 104);
                width: 75%;
                margin-left: auto;
                margin-right: auto;
                height: 100vh;
            }
            header {
                display: grid;
                grid-template-columns: auto auto;
                height: fit-content;
                background-color: rgb(33, 33, 53);
                padding: 10px;
                grid-area: header;
            }
            #settings {
                height: 20%;
                
                display: grid;
                grid-template-columns: auto;
                grid-area: settings;
                
            }
            ul {
                list-style-type: none;
                background-color: rgb(133, 133, 146);
                padding: 8px;
                text-align: center;
                color: black;
                display: grid;
                grid-template-columns: auto;
                align-content: center;
                row-gap: 10px;
                margin: 10px;
            }
            li {
                text-align: center;
                background-color: rgb(33, 33, 53);
                padding: 10px;
                color: white;
            }
            li:hover {
                background-color: rgb(25, 25, 39);
                cursor: pointer;
            }
            form {
                margin-top: 40px;
                
            }
            #formUserInfo {
                grid-area: formUserInfo;
                display: grid;
                grid-template-columns: 67% 33%;
                grid-template-areas: 
                    "sectionUserInfo asidePic";
                justify-content: left;
            }
            #sectionUserInfo {
                grid-area: sectionUserInfo;
                display: grid;
                grid-template-columns: auto;
                justify-items: space-between;
                gap: 10px;
                padding: 10px;
                background-color: rgb(72, 72, 80);
            }
            #asidePic {
                grid-area: asidePic;
                margin-left: auto;
                margin-right: auto;
                display: grid;
                grid-template-columns: auto;
                grid-template-areas: 
                    "gridPic"
                    "gridPicUpload";
                padding: 10px;
            }
            .formUser {
                display: grid;
                grid-template-columns: 30% 70%;
                padding: 10px;
            }
            .formSelect {
                display: grid;
                grid-template-columns: auto;
                padding: 10px;
            }
            #gridPicUpload {
                width: fit-content;
                margin-left: auto;
                margin-right: auto;
                grid-area: gridPicUpload;
            }
            button {
                width: 70px;
                margin-left: auto;
                margin-right: auto;
            }
            #formPassword {
                grid-area: formPassword;
                display: grid;
                grid-template-columns: auto;
                padding: 10px;
                background-color: rgb(72, 72, 80);
            }
            .formPass {
                display: grid;
                grid-template-columns: 30% 70%;
                padding: 10px;
            }
            #profileContainer {
                background-color: lightgray;
                width: 300px;
                height: 300px;
                text-align: center;
                border-radius: 100%;
                z-index: 2;
                margin-right: auto;
                margin-left: auto;
                grid-area: gridPic;
            }
            #profilePic {
                margin-top: 30px;
            }
        </style>
        
        <script type="module">
            import {eventOptAmerica} from './src/stateDisplayHandler.js';
            
            //DOM constants
            const form = document.getElementById("formPassword");
            const txtReset = document.getElementById('txtPassword');
            const txtConfirm = document.getElementById('txtConfirmPassword');
            const message = document.getElementById('message');
            const optCountry = document.getElementById('optCountry');
            const settings = document.getElementsByClassName('settingsOption');

            const debouncedCheck = debounce(checkPass, 400);

            function debounce(func, delay) {
                let timeoutId;
                return function(...args) {
                    clearTimeout(timeoutId);
                    timeoutId = setTimeout(() => {
                        func.apply(this, args);
                    }, delay);
                };
            }

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


            if (optCountry.value != "") {
                eventOptAmerica(optCountry.value);
            }
            


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

            //Add event listener to the country select element to see if USA was selected
            optCountry.addEventListener('change', (event) => eventOptAmerica(event.target.value));

            for(var i = 0; i < settings.length; i++) {
                settings[i].addEventListener('click', (event) => {
                    if (event.target.innerText === "User Settings") {
                        window.location.href = "user_acct_settings.php";
                    } else if (event.target.innerText === "Dashboard Settings") {
                        console.log("Dashboard Settings");
                    }
                });
            }   
        </script>
    </head>
    <body>
        <div class="background"></div>
        <main>
            <header>
                <p><a href="http://fourfiftyg3.byethost24.com/home.php" style="color: white;">🡰 Dashboard</a></p>
                <h1>Account Settings</h1>

            </header>
            <aside id="settings">
                <ul>
                    <h3>Settings</h3>
                    <li class="settingsOption">User Settings</li>
                    <li class="settingsOption">Dashboard Settings</li>
                </ul>
            </aside>
            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" enctype="multipart/form-data" id="formUserInfo">
                <section id="sectionUserInfo">
                    <h2 id="formHeader">User Information</h2>
                    <div class="formUser" id="gridUsername">
                        <label for="txtUsername">Username</label>
                        <input type="text" id="txtUsername" name="txtUsername" placeholder="Create a username" required>
                    </div>

                    <div class="formUser" id="gridFirstName">
                        <label for="txtFirstName">First Name</label>
                        <input type="text" id="txtFirstName" name="txtFirstName" placeholder="Enter your first name" required>
                    </div>
                    <div class="formUser" id="gridLastName">
                        <label for="txtLastName">Last Name</label>
                        <input type="text" id="txtLastName" name="txtLastName" placeholder="Enter your last name" required>
                    </div>
                    <div class="formUser" id="gridEmail">
                        <label for="txtEmail">Email</label>
                        <input type="email" id="txtEmail" name="txtEmail" placeholder="Enter your email" required>
                    </div>
                    <div class="formUser" id="gridBday">
                        <label for="datBday">Birth Date (Optional)</label>
                        <input type="date" id="datBday" name="datBday" placeholder="Enter your birth date">
                    </div>
                    <div class="formUser" id="gridCity">
                        <label for="txtCity">City</label>
                        <input type="text" id="txtCity" name="city" placeholder="Enter the city you reside in" required>
                    </div>
                    <div class="formSelect" id="gridCountry">
                        <select name="country" id="optCountry" value="">
                            <!--Automatically creates all options from database-->
                            <option disabled selected value>Choose Country</option>
                            <?php 
                                $sql = "SELECT * FROM countries";
                                if($stmt = $conn->prepare($sql)) {
                                    $stmt->execute();
                                    if($stmt->errno) {
                                        print_r("Could not execute prepared statement");
                                    }
                                    $result = $stmt->get_result();
                                    $stmt->free_result();
                                    $stmt->close();
                                }
                                while($row = $result->fetch_assoc()) {    
                                    echo "<option value='" . $row['countryCode'] . "'>" . $row['country'] . "</option>\n";
                                }
                            ?>
                        </select>
                    </div>
                    <div class="formSelect" id="gridState">
                        <select name="state" id="optState" hidden>
                            <!--Automatically creates all options from database-->
                            <option disabled selected value>Choose State</option>
                            <?php
                                $sql = "SELECT * FROM states";
                                if($stmt = $conn->prepare($sql)) {
                                    $stmt->execute();
                                    if($stmt->errno) {
                                        print_r("Could not execute prepared statement");
                                    }
                                    $result = $stmt->get_result();
                                    $stmt->free_result();
                                    $stmt->close();
                                }
                                while($row = $result->fetch_assoc()) {    
                                    echo "<option value='" . $row['stateCode'] . "'>" . $row['state'] . "</option>\n";
                                }
                            ?>
                        </select>
                    </div>
                    <button type="submit" id="submit">Submit</button>

                    <!-- Use a hidden field to tell server which form was submitted-->
                    <input type="hidden" name="formUserInfo" value="true" />
                </section>
                <aside id="asidePic">
                    <div id="gridPicUpload">
                        <label for="filProfilePic">Profile Picture (PNG and JPEG Files)</label>
                        <input type="file" id="filProfilePic" name="picture" accept="image/png, image/jpg">
                    </div>
                    
                    <div id="profileContainer">
                        <img src="<?php echo $thisUser['profile_picture']; ?>" width="250px" height="250px" alt="profile picture" id="profilePic">
                    </div>
                </aside>
                

                
            </form>
            <form id="formPassword" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" enctype="multipart/form-data">
                <h2>Password Change</h2>
                
                <div class="formPass">
                    <label for="txtOldPassword">Confirm Old Password</label>
                    <input type="password" id="txtOldPassword" name="oldPassword" placeholder="Old password" minlength="8" required>
                </div>
                <div class="formPass">
                    <label for="txtPassword">New Password</label>
                    <input type="password" id="txtPassword" name="newPassword" placeholder="Create a password" minlength="8" required>
                </div>
                <div class="formPass">
                    <label for="txtConfirmPassword">Confirm New Password</label>
                    <input type="password" id="txtConfirmPassword" name="confirmPassword" placeholder="Confirm your password" minlength="8" required>
                </div>
                
                <button type="submit" id="submitPass">Submit</button>

                <!-- Use a hidden field to tell server which form was submitted-->
                <input type="hidden" name="formPassword" value="true" />

                <div id="message" class="error"></div>
            </form>
        </main>
    </body>
    <script>
        function setInputValue(name, value) {
            document.getElementById(name).value = value;
        }

        setInputValue('txtFirstName', '<?php echo $thisUser["first_name"]; ?>');
        setInputValue('txtLastName', '<?php echo $thisUser["last_name"]; ?>');
        setInputValue('txtEmail', '<?php echo $thisUser["email"]; ?>');
        setInputValue('txtUsername', '<?php echo $thisUser["username"]; ?>');
        setInputValue('datBday', '<?php echo $thisUser["birth_date"]; ?>');
        setInputValue('txtCity', '<?php echo $thisUser["city"]; ?>');
        setInputValue('optCountry', '<?php echo $thisUser["country"]; ?>');
        setInputValue('optState', '<?php echo $thisUser["state"]; ?>');      
    </script>
</html>
