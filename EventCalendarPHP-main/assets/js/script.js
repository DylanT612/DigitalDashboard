$(document).ready(function() {
    // Initialize the FullCalendar
    $('#calendar').fullCalendar({
        header: {
            left: 'prev,next today', // Navigation buttons on the left
            center: 'title', // Title in the center
            right: 'month,agendaWeek,agendaDay' // View options on the right
        },
        events: 'fetch_events.php', // URL to fetch events
        selectable: true, // Allow dates to be selectable
        selectHelper: true, // Show a placeholder when selecting
        select: function(start, end) {
            // Clear the form inputs
            $('#eventId').val('');
            $('#eventTitle').val('');
            $('#startTime').val(moment(start).format("YYYY-MM-DD HH:mm:ss"));
            $('#endTime').val(moment(end).format("YYYY-MM-DD HH:mm:ss"));

            $('#friendsAdded').empty();
            $('#friendsResults').empty();
            
            $('#optFriend').prop('disabled', false);
            $('#eventTitle').prop('disabled', false);
            $('#startTime').prop('disabled', false);
            $('#endTime').prop('disabled', false);
            $('#deleteEvent').prop('disabled', false);
            $('#saveEvent').prop('disabled', false);
            // Show the modal for adding a new event
            $('#eventModal').modal('show');
        },
        editable: true, // Allow events to be editable
        eventDrop: function(event) {
            // Update the event's new start and end times
            var start = moment(event.start).format("YYYY-MM-DD HH:mm:ss");
            var end = moment(event.end).format("YYYY-MM-DD HH:mm:ss");
            $.ajax({
                url: 'edit_event.php', // URL to update event
                data: {
                    id: event.id, // Event ID
                    title: event.title, // Event title
                    start: start, // New start time
                    end: end, // New end time
                    created_by: event.created_by
                },
                type: "POST",
                success: function(data) {
                }
            });
        },
        eventClick: function(event) {
            $('#friendsAdded').empty();
            $('#friendsResults').empty();
            // Populate the form inputs with the event data
            $('#createdBy').text("Created by: " + event.created_by_username);
            $('#eventId').val(event.id);
            $('#eventTitle').val(event.title);
            $('#startTime').val(moment(event.start).format("YYYY-MM-DD HH:mm:ss"));
            $('#endTime').val(moment(event.end).format("YYYY-MM-DD HH:mm:ss"));
            $.each(event.friends, function(index, value) {
                addFriend(value.username, value.id_user, false, event.user_created);
            });
            if (event.user_created == false) {
                $('#optFriend').prop('disabled', true);
                $('#eventTitle').prop('disabled', true);
                $('#startTime').prop('disabled', true);
                $('#endTime').prop('disabled', true);
                $('#deleteEvent').prop('disabled', true);
                $('#saveEvent').prop('disabled', true);
            } 
            else {
                $('#optFriend').prop('disabled', false);
                $('#eventTitle').prop('disabled', false);
                $('#startTime').prop('disabled', false);
                $('#endTime').prop('disabled', false);
                $('#deleteEvent').prop('disabled', false);
                $('#saveEvent').prop('disabled', false);
            }
            // Show the modal for editing the event
            $('#eventModal').modal('show');
        }
    });

    // Initialize datetimepicker for start and end time inputs
    $('.datetimepicker').datetimepicker({
        format: 'Y-MM-DD HH:mm:ss' // Date-time format
    });

    // Handle form submission for adding/editing events
    $('#eventForm').on('submit', function(e) {
        e.preventDefault();
        var id = $('#eventId').val(); // Event ID (if editing)
        var title = $('#eventTitle').val(); // Event title
        var start = $('#startTime').val(); // Start time
        var end = $('#endTime').val(); // End time

        
        // Determine whether to add or edit event
        var url = id ? 'edit_event.php' : 'add_event.php';
        var data = id ? { id: id, title: title, start: start, end: end } : { title: title, start: start, end: end };

        $.ajax({
            url: url,
            data: data,
            type: "POST",
            success: function(data) {
                $('#calendar').fullCalendar('refetchEvents'); // Refresh events
                $('#eventModal').modal('hide'); // Hide the modal
            }
        });


        var friends = [];
        var friendsRemove = [];

        $('#friendsAdded div').each(function() {
            if ($(this).is(':hidden')) { // Check if the div is hidden
                friendsRemove.push(this.id); // Push the id of hidden divs
                $(this).remove(); // Remove the hidden
            }
            else if ($(this).hasClass('adding')) { // Check if the div has class 'adding'
                friends.push(this.id); // Push the id of divs with 'adding' class
                $(this).removeClass('adding'); // Remove the 'adding' class
            }
        });
        
        $.ajax({
            url: 'add_event_friends.php',
            data: { friends: friends },
            type: "POST",
            success: function(data) {
                console.log(data);
            }
        });

        $.ajax({
            url: 'delete_event_friends.php',
            data: { friends: friendsRemove },
            type: "POST",
            success: function(data) {
            }
        });
    });

    // Handle event deletion
    $('#deleteEvent').on('click', function() {
        var id = $('#eventId').val(); // Event ID
        if (id) {
            $.ajax({
                url: 'delete_event.php', // URL to delete event
                data: { id: id }, // Event ID to delete
                type: "POST",
                success: function(data) {
                    $('#calendar').fullCalendar('removeEvents', id); // Remove event from calendar
                    $('#eventModal').modal('hide'); // Hide the modal
                }
            });
            $.ajax({
                url: 'delete_all_friends.php', // URL to delete event
                data: { event_id: id }, // Event ID to delete
                type: "POST",
                success: function(data) {
                }
            });
        }
    });
});
