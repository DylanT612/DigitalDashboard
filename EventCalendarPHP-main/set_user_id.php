<?php
    // Confirm login of user from index
    if (!isset($_SESSION['username'])) {
        header("Location: ../index.php");
        exit();
    }

    $host = '';
    $user = '';
    $pass = ''; 
    $dbname = '';

    $conn = new mysqli($host, $user, $pass, $dbname);

    // If host, db, user, or pass is incorrect create error
    if ($conn->connect_error) {
        die("Failed to connect: " . $conn->connect_error);
    }

    $sql = "SELECT * FROM users WHERE username = ? LIMIT 1";
        
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

    $_SESSION['user_id'] = $row["id"];

?>
