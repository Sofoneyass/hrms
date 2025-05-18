<?php
$pageTitle = "Manage Messages";
require_once 'db_connection.php';
require_once 'admin_header.php';
require_once 'admin_sidebar.php';

// Get messages with sender and property info
$messageQuery = "
    SELECT m.*, 
           s.full_name as sender_name,
           r.full_name as receiver_name,
           p.title as property_title
    FROM messages m
    JOIN users s ON m.sender_id = s.user_id
    JOIN users r ON m.receiver_id = r.user_id
    JOIN properties p ON m.property_id = p.property_id
    WHERE m.receiver_id = ? OR m.sender_id = ?
    ORDER BY m.sent_at DESC
";
$messageStmt = $conn->prepare($messageQuery);
$messageStmt->bind_param("ss", $_SESSION['user_id'], $_SESSION['user_id']);
$messageStmt->execute();
$messages = $messageStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$messageStmt->close();
?>

<div class="main-content">
    <div class="header">
        <h1>Manage Messages</h1>
        <a href="compose_message.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Compose Message
        </a>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">All Messages</h5>
            <div class="filter-options">
                <select class="form-select form-select-sm" id="messageStatusFilter">
                    <option value="">All Statuses</option>
                    <option value="unread">Unread</option>
                    <option value="read">Read</option>
                    <option value="replied">Replied</option>
                </select>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="messagesTable">
                    <thead>
                        <tr>
                            <th>From</th>
                            <th>To</th>
                            <th>Property</th>
                            <th>Message</th>
                            <th>Sent On</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($messages as $message): ?>
                        <tr data-status="<?php echo $message['status']; ?>">
                            <td><?php echo htmlspecialchars($message['sender_name']); ?></td>
                            <td><?php echo htmlspecialchars($message['receiver_name']); ?></td>
                            <td><?php echo htmlspecialchars($message['property_title']); ?></td>
                            <td>
                                <?php echo strlen($message['message']) > 50 ? 
                                    substr(htmlspecialchars($message['message']), 0, 50) . '...' : 
                                    htmlspecialchars($message['message']); 
                                ?>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($message['sent_at'])); ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $message['status'] === 'unread' ? 'warning' : 
                                         ($message['status'] === 'replied' ? 'success' : 'info'); 
                                ?>">
                                    <?php echo ucfirst($message['status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="view_message.php?id=<?php echo $message['message_id']; ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="reply_message.php?id=<?php echo $message['message_id']; ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-reply"></i>
                                </a>
                                <button class="btn btn-sm btn-danger delete-message" data-id="<?php echo $message['message_id']; ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Filter by status
    $('#messageStatusFilter').change(function() {
        const status = $(this).val();
        if (status) {
            $('#messagesTable tbody tr').hide();
            $(`#messagesTable tbody tr[data-status="${status}"]`).show();
        } else {
            $('#messagesTable tbody tr').show();
        }
    });

    $('.delete-message').click(function() {
        if (confirm('Are you sure you want to delete this message?')) {
            const messageId = $(this).data('id');
            window.location.href = 'delete_message.php?id=' + messageId;
        }
    });
});
</script>

<?php 
require_once 'admin_footer.php';
$conn->close();
?>