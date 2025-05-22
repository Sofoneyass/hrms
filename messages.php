<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in and is a tenant or owner
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['tenant', 'owner'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Fetch conversations
$conversations = [];
if ($role === 'tenant') {
    $query = $conn->prepare("
        SELECT DISTINCT p.property_id, p.title, p.owner_id, u.full_name,
               (SELECT COUNT(*) FROM messages m WHERE m.property_id = p.property_id AND m.receiver_id = ? AND m.status = 'unread') as unread_count
        FROM properties p
        LEFT JOIN users u ON p.owner_id = u.user_id
        LEFT JOIN leases l ON l.property_id = p.property_id AND l.tenant_id = ? AND l.status = 'active'
        LEFT JOIN bookings b ON b.property_id = p.property_id AND b.tenant_id = ? AND b.status = 'confirmed'
        WHERE (l.lease_id IS NOT NULL OR b.booking_id IS NOT NULL) AND p.owner_id IS NOT NULL
        ORDER BY p.created_at DESC
    ");
    $query->bind_param("sss", $user_id, $user_id, $user_id);
} else {
    $query = $conn->prepare("
        SELECT p.property_id, p.title,
               COALESCE(u.full_name, p.title) as display_name,
               NULL as owner_id,
               (SELECT COUNT(*) FROM messages m WHERE m.property_id = p.property_id AND m.receiver_id = ? AND m.status = 'unread') as unread_count
        FROM properties p
        LEFT JOIN leases l ON l.property_id = p.property_id AND l.status = 'active'
        LEFT JOIN bookings b ON b.property_id = p.property_id AND b.status = 'confirmed' AND (l.lease_id IS NULL OR b.booking_date > l.created_at)
        LEFT JOIN users u ON u.user_id = COALESCE(l.tenant_id, b.tenant_id)
        WHERE p.owner_id = ? AND p.status IN ('available', 'reserved', 'rented')
        ORDER BY p.created_at DESC
    ");
    $query->bind_param("ss", $user_id, $user_id);
}
$query->execute();
$result = $query->get_result();
while ($row = $result->fetch_assoc()) {
    $conversations[] = $row;
}
$query->close();

// Generate CSRF token
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages | JIGJIGAHOMES</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #1a2a44 0%, #2a4066 100%);
            color: #ffffff;
            min-height: 100vh;
            display: flex;
        }

        .sidebar {
            width: 250px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 20px;
            height: 100vh;
            position: fixed;
            border-right: 1px solid rgba(255, 255, 255, 0.2);
        }

        .sidebar h2 {
            color: #FFD700;
            font-size: 24px;
            margin-bottom: 30px;
            text-align: center;
        }

        .sidebar a {
            display: block;
            color: #ffffff;
            text-decoration: none;
            padding: 10px;
            margin: 5px 0;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .sidebar a:hover, .sidebar a.active {
            background: rgba(255, 215, 0, 0.2);
            color: #FFD700;
        }

        .main-content {
            margin-left: 250px;
            flex: 1;
            padding: 20px;
        }

        .header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 24px;
            color: #FFD700;
        }

        .profile-dropdown {
            position: relative;
        }

        .profile-btn {
            background: none;
            border: none;
            color: #ffffff;
            cursor: pointer;
            font-size: 16px;
            padding: 10px;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 5px;
            min-width: 150px;
            z-index: 1;
        }

        .dropdown-content a {
            color: #ffffff;
            padding: 12px 16px;
            display: block;
            text-decoration: none;
        }

        .dropdown-content a:hover {
            background: rgba(255, 215, 0, 0.2);
        }

        .profile-dropdown:hover .dropdown-content {
            display: block;
        }

        .card {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(255, 215, 0, 0.3);
        }

        .conversation-list {
            max-height: 600px;
            overflow-y: auto;
        }

        .conversation-item {
            padding: 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            cursor: pointer;
            transition: background 0.3s;
        }

        .conversation-item:hover {
            background: rgba(255, 215, 0, 0.1);
        }

        .chat-container {
            height: 500px;
            overflow-y: auto;
            scroll-behavior: smooth;
            background: rgba(255, 255, 255, 0.05);
            padding: 15px;
            border-radius: 8px;
        }

        .message-sent {
            background: #FFD700;
            color: #1a2a44;
            align-self: flex-end;
            border-radius: 10px 10px 0 10px;
            padding: 10px;
            max-width: 70%;
            margin: 5px;
        }

        .message-received {
            background: rgba(255, 255, 255, 0.2);
            color: #ffffff;
            align-self: flex-start;
            border-radius: 10px 10px 10px 0;
            padding: 10px;
            max-width: 70%;
            margin: 5px;
        }

        .message-form input, .message-form textarea {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: #ffffff;
            padding: 8px;
            border-radius: 5px;
            width: 100%;
            margin-bottom: 10px;
        }

        .message-form textarea {
            resize: vertical;
            min-height: 80px;
        }

        .message-form button {
            background: #FFD700;
            border: none;
            color: #1a2a44;
            padding: 8px 16px;
            border-radius: 20px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .message-form button:hover {
            background: #e6c200;
        }

        .error-message {
            color: #FF6347;
            font-size: 14px;
            margin-top: 10px;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 200px;
            }
            .main-content {
                margin-left: 200px;
            }
        }

        @media (max-width: 600px) {
            .sidebar {
                position: absolute;
                z-index: 1000;
                transform: translateX(-100%);
                transition: transform 0.3s;
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
            }
            .header {
                position: relative;
            }
            .menu-toggle {
                display: block;
                cursor: pointer;
                font-size: 24px;
                color: #FFD700;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar" id="sidebar">
        <h2>JIGJIGAHOMES</h2>
        <?php if ($role === 'owner'): ?>
            <a href="owner_dashboard.php">Dashboard</a>
            <a href="my_properties.php">My Properties</a>
            <a href="manage_leases.php">Manage Leases</a>
            <a href="messages.php" class="active">Messages</a>
            <a href="view_payments.php">View Payments</a>
            <a href="profile.php">Profile</a>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="tenant_dashboard.php">Dashboard</a>
            <a href="my_leases.php">My Leases</a>
            <a href="messages.php" class="active">Messages</a>
            <a href="profile.php">Profile</a>
            <a href="logout.php">Logout</a>
        <?php endif; ?>
    </div>
    <div class="main-content">
        <div class="header">
            <span class="menu-toggle" onclick="toggleSidebar()">☰</span>
            <h1>Messages</h1>
            <div class="profile-dropdown">
                <button class="profile-btn" aria-label="Profile menu">Profile</button>
                <div class="dropdown-content">
                    <a href="profile.php">Edit Profile</a>
                    <a href="logout.php">Logout</a>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="flex flex-col md:flex-row gap-4">
                <!-- Conversations List -->
                <div class="w-full md:w-1/3 conversation-list">
                    <h3 class="text-lg font-semibold text-[#FFD700] mb-4">Conversations</h3>
                    <?php if (empty($conversations)): ?>
                        <p class="text-gray-300">No conversations found.</p>
                    <?php else: ?>
                        <div class="space-y-2">
                            <?php foreach ($conversations as $conv): ?>
                                <div class="conversation-item flex items-center justify-between" 
                                     data-property-id="<?php echo htmlspecialchars($conv['property_id']); ?>" 
                                     data-receiver-id="<?php echo htmlspecialchars($conv['owner_id'] ?? ''); ?>">
                                    <div>
                                        <p class="font-medium"><?php echo htmlspecialchars($role === 'tenant' ? $conv['title'] : $conv['display_name']); ?></p>
                                        <p class="text-sm text-gray-400">
                                            <?php echo $role === 'tenant' ? htmlspecialchars($conv['full_name'] ?? 'Owner') : htmlspecialchars($conv['title']); ?>
                                        </p>
                                    </div>
                                    <?php if ($conv['unread_count'] > 0): ?>
                                        <span class="bg-[#FF6347] text-white text-xs rounded-full px-2 py-1"><?php echo $conv['unread_count']; ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <!-- Chat Area -->
                <div class="w-full md:w-2/3 flex flex-col">
                    <div id="chatHeader" class="p-4">
                        <p class="text-gray-300">Select a conversation to start messaging</p>
                    </div>
                    <div id="chatMessages" class="chat-container flex flex-col gap-3"></div>
                    <form id="messageForm" class="message-form mt-4" hidden>
                        <input type="hidden" name="property_id" id="propertyId">
                        <input type="hidden" name="receiver_id" id="receiverId">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                        <input type="file" name="attachment" accept="image/*,application/pdf">
                        <textarea id="messageContent" name="message" placeholder="Type your message..." required></textarea>
                        <button type="submit">Send</button>
                    </form>
                    <p id="formError" class="error-message hidden"></p>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Sidebar toggle for mobile
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('active');
        }

        document.addEventListener('DOMContentLoaded', () => {
            const chatMessages = document.getElementById('chatMessages');
            const messageForm = document.getElementById('messageForm');
            const formError = document.getElementById('formError');
            const propertyIdInput = document.getElementById('propertyId');
            const receiverIdInput = document.getElementById('receiverId');
            const messageContent = document.getElementById('messageContent');
            let currentPropertyId = null;

            // Load messages for a property
            async function loadMessages(propertyId, receiverId, title, name) {
                currentPropertyId = propertyId;
                propertyIdInput.value = propertyId;
                receiverIdInput.value = receiverId;
                messageForm.removeAttribute('hidden');
                document.getElementById('chatHeader').innerHTML = `
                    <p class="font-medium text-[#FFD700]">${title}</p>
                    <p class="text-sm text-gray-400">${name}</p>
                `;
                chatMessages.innerHTML = '<p class="text-gray-300">Loading messages...</p>';

                try {
                    const response = await fetch(`message_handler.php?property_id=${propertyId}`);
                    const messages = await response.json();
                    if (messages.error) {
                        chatMessages.innerHTML = `<p class="text-[#FF6347]">${messages.error}</p>`;
                        messageForm.setAttribute('hidden', '');
                        return;
                    }

                    chatMessages.innerHTML = '';
                    messages.forEach(msg => {
                        const isSent = msg.sender_id === '<?php echo $user_id; ?>';
                        const messageClass = isSent ? 'message-sent' : 'message-received';
                        const alignment = isSent ? 'ml-auto' : 'mr-auto';
                        chatMessages.innerHTML += `
                            <div class="${messageClass} ${alignment}">
                                <p>${msg.message}</p>
                                <p class="text-xs opacity-70">${new Date(msg.sent_at).toLocaleString()}</p>
                                <p class="text-xs opacity-70">Status: ${msg.status}</p>
                            </div>
                        `;
                    });
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                } catch (error) {
                    chatMessages.innerHTML = '<p class="text-[#FF6347]">Failed to load messages.</p>';
                }
            }

            // Handle conversation selection
            document.querySelectorAll('.conversation-item').forEach(item => {
                item.addEventListener('click', () => {
                    const propertyId = item.dataset.propertyId;
                    const receiverId = item.dataset.receiverId || '';
                    const title = item.querySelector('p.font-medium').textContent;
                    const name = item.querySelector('p.text-sm').textContent;
                    loadMessages(propertyId, receiverId, title, name);
                });
            });

            // Handle message submission
            messageForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                formError.classList.add('hidden');

                const formData = new FormData(messageForm);
                try {
                    const response = await fetch('message_handler.php', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();
                    if (result.success) {
                        messageContent.value = '';
                        document.querySelector('input[name="attachment"]').value = '';
                        loadMessages(currentPropertyId, receiverIdInput.value, 
                            document.querySelector('#chatHeader p.font-medium').textContent, 
                            document.querySelector('#chatHeader p.text-sm').textContent);
                    } else {
                        formError.textContent = result.error || 'Failed to send message';
                        formError.classList.remove('hidden');
                    }
                } catch (error) {
                    formError.textContent = 'Network error occurred';
                    formError.classList.remove('hidden');
                }
            });

            // Real-time polling
            setInterval(() => {
                if (currentPropertyId) {
                    loadMessages(currentPropertyId, receiverIdInput.value, 
                        document.querySelector('#chatHeader p.font-medium').textContent, 
                        document.querySelector('#chatHeader p.text-sm').textContent);
                }
            }, 10000);
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>