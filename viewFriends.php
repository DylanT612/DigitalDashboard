<!DOCTYPE html>
<!-- 
View User Friends List Page
CSC 450 Capstone Final Project Byethost
Dylan Theis: theisd@csp.edu
Keagan Harr: 
Ty Steinbach:
04/03/25
Revisions: 
04/03/25: Dylan Theis created page to display the users friends
-->
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>View Friends</title>
        <link rel="stylesheet" href="Styles/userAcctStyles.css">
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
        <div class="main-container">
            <!-- Return to home page -->
            <span id="dashboardLink">🡰 Dashboard</span>
            <div class="credentials-container">
                <h1>Your Friends</h1>
                <div id="friendsList"></div>
            </div>

            
        <script>
            // Listen for click to return to dashboard
            document.getElementById('dashboardLink').addEventListener('click', function() {
                window.location.href = 'home.php';
            });
        </script>
        <script>
            // Load accepted friend requests
            function loadFriendsList() {
                // Get status "accepted" friend requests
                fetch('friends_list.php')
                    .then(response => response.json())
                    .then(data => {
                        let friendsDiv = document.getElementById('friendsList');
                        friendsDiv.innerHTML = "";
                        // If no friends
                        if (data.length === 0) {
                            friendsDiv.innerHTML = "<p>No friends yet</p>";
                            return;
                        }
                        // For each friend 
                        data.forEach(friend => {
                            // Create new div with their username
                            let div = document.createElement('div');
                            div.textContent = friend.username;
                            friendsDiv.appendChild(div);
                        });
                    });
            }
            // Run friends list
            loadFriendsList();
        </script>
            
    </body>

</html>
