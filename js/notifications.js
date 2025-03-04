// Function to fetch notifications
function fetchNotifications() {
    fetch('notifications.php?fetch=notifications')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateNotificationBadge(data.count);
                updateNotificationDropdown(data.notifications);
            }
        })
        .catch(error => console.error('Error fetching notifications:', error));
}

// Function to update notification badge count
function updateNotificationBadge(count) {
    const badge = document.querySelector('.notification-badge');
    if (badge) {
        badge.textContent = count;
        badge.style.display = count > 0 ? 'block' : 'none';
    }
}

// Function to update notification dropdown content
function updateNotificationDropdown(notifications) {
    const dropdownMenu = document.querySelector('.notification-dropdown-menu');
    if (!dropdownMenu) return;

    // Clear existing notifications
    dropdownMenu.innerHTML = '';

    if (notifications.length === 0) {
        dropdownMenu.innerHTML = '<div class="dropdown-item text-center">No notifications</div>';
        return;
    }

    // Add each notification to the dropdown
    notifications.forEach(notification => {
        const item = document.createElement('a');
        item.href = notification.url;
        item.className = 'dropdown-item notification-item';
        
        item.innerHTML = `
            <div class="d-flex align-items-center">
                <div class="flex-grow-1">
                    <div class="notification-message">${notification.message}</div>
                    <div class="small text-muted">${notification.time}</div>
                </div>
                <span class="badge bg-${notification.badge} ms-2">${notification.status}</span>
            </div>
        `;
        
        dropdownMenu.appendChild(item);
    });

    // Add "View All" link
    const viewAll = document.createElement('div');
    viewAll.className = 'dropdown-item text-center view-all';
    viewAll.innerHTML = '<a href="low_stock.php">View All Notifications</a>';
    dropdownMenu.appendChild(viewAll);
}

// Fetch notifications every 30 seconds
fetchNotifications();
setInterval(fetchNotifications, 30000); 