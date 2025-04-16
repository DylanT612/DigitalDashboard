<!-- 
Calendar Page(index)
CSC 450 Capstone Final Project Byethost
Dylan Theis: theisd@csp.edu
Keagan Haar: haark@csp.edu
Ty Steinbach: steinbat1@csp.edu
1/25/25
Revisions (For all event calendar): 
03/01/25: Ty Steinbach implimented the basic event calendar (source below)
03/02/25: Ty Steinbach added some basic stylings to the calendar
03/05/25: Ty Steinbach started looking into ways to impliment shared events
03/16/25: Ty Steinbach got event sharing implimented to a static group of users
03/07/25: Ty Steinbach added dynamic friend options and squashed some bugs
03/25/25: Ty Steinbach changes users.id reference and method for fetching friends from database

Sources:
https://github.com/obadaKraishan/EventCalendarPHP
-->
<?php
    session_start();
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


    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

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

    include 'templates/header.php';
    include 'templates/navbar.php';
    include 'includes/functions.php';
?>
<div class="container mt-5">
    <h1><?php echo $_SESSION['username'];?>'s Calendar</h1>
    <div id="calendar"></div>
</div>

<!-- Modal for adding/editing events -->
<div class="modal fade" id="eventModal" tabindex="-1" role="dialog" aria-labelledby="eventModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="eventModalLabel">Add/Edit Event</h5>
                
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div id="createdBy"></div>
            <div class="modal-body">
                <form id="eventForm">
                    <input type="hidden" id="eventId">
                    <div class="form-group">
                        <label for="eventTitle">Event Title</label>
                        <input type="text" class="form-control" id="eventTitle" required>
                    </div>
                    <div class="form-group">
                        <label for="startTime">Start Time</label>
                        <input type="text" class="form-control datetimepicker" id="startTime" required>
                    </div>
                    <div class="form-group">
                        <label for="endTime">End Time</label>
                        <input type="text" class="form-control datetimepicker" id="endTime" required>
                    </div>
                    <div class="form-group">
                        <select id="optFriend" name="optFriend">
                            <option disabled selected value="">Add Friends</option>
                            <?php
                                $stmt = $conn->prepare("
                                    SELECT users.id, users.username FROM friends
                                    JOIN users ON (friends.user1_id = users.id OR friends.user2_id = users.id) 
                                    WHERE (friends.user1_id = ? OR friends.user2_id = ?) AND users.id != ?
                                ");
                                $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);
                                $friends = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($friends as $friend) {
                                    echo '<option value="'.$friend['id'].'">'.$friend['username'].'</option>';
                                }
                            
                            ?>

                        </select>
                           

                    </div>
                    <div id="friends">
                        <div id="friendsResults"></div>
                        <strong>Added Friends:</strong>
                        <div id="friendsAdded"></div>
                    </div>
                    <button type="submit" class="btn btn-primary" id="saveEvent">Save Event</button>
                    <button type="button" class="btn btn-danger" id="deleteEvent">Delete Event</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include 'templates/footer.php'; ?>
