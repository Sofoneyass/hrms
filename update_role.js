function changeRole(userId, newRole) {
    if (confirm("Are you sure you want to change the user role to " + newRole + "?")) {
        // Send an AJAX POST request to update the user role
        fetch('update_user_role.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                'user_id': userId,
                'role': newRole
            })
        })
        .then(response => response.text())
        .then(data => {
            if (data === 'success') {
               
                alert('User role updated successfully.');
               
                setTimeout(function() {
                    window.location.href = 'manage_user.php';
                }, 1500);  // Redirect after 1.5 seconds
            } else {
                alert('Failed to update user role.');
            }
        })
        .catch(error => alert('Error updating user role.'));
    }
}
