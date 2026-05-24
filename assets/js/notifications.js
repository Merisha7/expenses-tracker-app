document.addEventListener("DOMContentLoaded", () => {

    const notificationBtn =
        document.getElementById("notificationBtn");
    const notificationBox =
        document.getElementById("notificationBox");
    const notificationList =
        document.getElementById("notificationList");
    const notificationCount =
        document.getElementById("notificationCount");

    // Checks whether all required notification elements exist
    if (
        !notificationBtn ||
        !notificationBox ||
        !notificationList ||
        !notificationCount
    ) {

        // Shows error in console if elements are missing
        console.error("Notification elements missing");

        return;
    }

    /*TOGGLE DROPDOWN*/

    // Adds click event listener to notification button
    notificationBtn.addEventListener("click", (e) => {

        // Prevents click event from bubbling to document
        e.stopPropagation();

        // Checks if notification box is currently visible
        if (notificationBox.style.display === "block") {

            // Hides the notification box
            notificationBox.style.display = "none";

        } else {

            // Shows the notification box
            notificationBox.style.display = "block";

            // Sends request to mark notifications as read
            fetch("../notifications/notifications.php?read=1");

            // Sets notification count to 0
            notificationCount.innerText = 0;
        }
    });

    /* CLOSE WHEN CLICK OUTSIDE */

    // Adds click event listener to whole document
    document.addEventListener("click", (e) => {

        // Checks if click happened outside notification box and button
        if (
            !notificationBox.contains(e.target) &&
            !notificationBtn.contains(e.target)
        ) {

            // Hides notification dropdown
            notificationBox.style.display = "none";
        }
    });

    /* ESCAPE HTML*/

    // Function to safely escape HTML characters
    function escapeHTML(str) {

        // Creates temporary div element
        const div = document.createElement("div");

        // Inserts text into div safely
        div.innerText = str;

        // Returns escaped HTML version
        return div.innerHTML;
    }

    /* LOAD NOTIFICATIONS*/

    // Async function to fetch notifications from server
    async function loadNotifications() {

        try {

            // Sends request to notifications PHP file
            const response =
                await fetch("../notifications/notifications.php");

            // Converts response into text format
            const text = await response.text();

            // Prints raw response in console for debugging
            console.log(text);

            // Converts JSON text into JavaScript object
            const data = JSON.parse(text);

            // Clears previous notification list
            notificationList.innerHTML = "";

            // Variable to count unread notifications
            let unreadCount = 0;

            // Checks if there are no notifications
            if (data.length === 0) {

                // Displays no notifications message
                notificationList.innerHTML = `
                    <div class="notification-item">
                        No notifications yet
                    </div>
                `;

                // Sets notification badge count to 0
                notificationCount.innerText = 0;

                // Stops function execution
                return;
            }

            // Loops through each notification
            data.forEach(notification => {

                // Checks if notification is unread
                if (notification.is_read == 0) {

                    // Increases unread count
                    unreadCount++;
                }

                // Creates new div element for notification
                const item =
                    document.createElement("div");

                // Adds CSS class to notification item
                item.classList.add("notification-item");

                // Inserts notification content into div
                item.innerHTML = `
                    <div class="notification-message">
                        ${escapeHTML(notification.message)}
                    </div>

                    <small>
                        ${escapeHTML(notification.created_at)}
                    </small>
                `;

                // Adds notification item to notification list
                notificationList.appendChild(item);
            });

            // Updates notification badge count
            notificationCount.innerText = unreadCount;

        } catch (error) {

            // Prints error in console
            console.error(error);

            // Displays error message in notification box
            notificationList.innerHTML = `
                <div class="notification-item">
                    Error loading notifications
                </div>
            `;
        }
    }

    /* MARK AS READ*/

    // Async function to mark notifications as read
    async function markAsRead() {

        try {

            // Sends request to mark notifications as read
            await fetch(
                "../notifications/mark_notifications_read.php"
            );

            // Resets notification badge count to 0
            notificationCount.innerText = 0;

        } catch (error) {

            // Prints error in console
            console.error(error);
        }
    }

    /* INITIAL LOAD*/

    // Loads notifications immediately when page opens
    loadNotifications();

    // Reloads notifications every 5 seconds
    setInterval(loadNotifications, 5000);

});