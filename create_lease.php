<?php
session_start();
require_once 'db_connection.php';

// Check login & role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tenant') {
    http_response_code(403);
    die("Access denied. Only tenants can create leases.");
}

$tenant_id = $_SESSION['user_id'];

// Validate UUID format for property_id
if (!isset($_GET['property_id']) || !preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $_GET['property_id'])) {
    http_response_code(400);
    die("Invalid property ID format.");
}

$property_id = $_GET['property_id'];


// Fetch property details
$query = $conn->prepare("SELECT title, price_per_month FROM properties WHERE property_id = ?");
$query->bind_param("s", $property_id);
$query->execute();
$result = $query->get_result();
$property = $result->fetch_assoc();
$query->close();

if (!$property) {
    http_response_code(404);
    $conn->close();
    die("Property not found.");
}

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
    <title>Create Lease | JIGJIGAHOMES</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .glass-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .dark .glass-card {
            background: rgba(30, 30, 30, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .theme-toggle {
            transition: background-color 0.3s ease;
        }
        .form-control:focus {
            box-shadow: 0 0 0 3px rgba(240, 193, 75, 0.2);
        }
        .error-message {
            color: #dc2626;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900 font-sans min-h-screen flex flex-col">
    <div class="container max-w-2xl mx-auto p-4 flex-1">
        <div class="glass-card shadow-xl rounded-2xl p-6">
            <div class="text-center mb-6">
                <h1 class="text-2xl font-bold text-blue-600 dark:text-blue-400">Create New Lease</h1>
                <div class="flex items-center justify-center gap-2 mt-2 text-lg text-blue-600 dark:text-blue-400">
                    <i class="fas fa-home"></i>
                    <span><?php echo htmlspecialchars($property['title']); ?></span>
                </div>
            </div>

            <form id="leaseForm" method="POST" action="create_lease_handler.php">
                <input type="hidden" name="tenant_id" value="<?php echo htmlspecialchars($tenant_id); ?>">
                <input type="hidden" name="property_id" value="<?php echo htmlspecialchars($property_id); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <input type="hidden" name="monthly_rent" value="<?php echo htmlspecialchars($property['price_per_month']); ?>">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Start Date</label>
                        <input type="date" id="start_date" name="start_date" class="mt-1 w-full border rounded-lg p-2 focus:ring-2 focus:ring-yellow-400 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 form-control" required>
                        <p id="start_date_error" class="error-message hidden"></p>
                    </div>
                    <div>
                        <label for="lease_months" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Lease Duration (in months)</label>
                        <select id="lease_months" name="lease_months" class="mt-1 w-full border rounded-lg p-2 focus:ring-2 focus:ring-yellow-400 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 form-control">
                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i . ' month' . ($i > 1 ? 's' : ''); ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div>
                        <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">End Date</label>
                        <input type="date" id="end_date" name="end_date" class="mt-1 w-full border rounded-lg p-2 bg-gray-100 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 form-control" readonly required>
                        <p id="end_date_error" class="error-message hidden"></p>
                    </div>
                </div>

                <div class="bg-blue-50 dark:bg-blue-900/50 p-4 rounded-lg mb-6 border-l-4 border-yellow-400">
                    <h3 class="text-sm font-medium text-blue-600 dark:text-blue-400 flex items-center gap-2">
                        <i class="fas fa-file-contract"></i> Standard Terms
                    </h3>
                    <ul class="list-disc pl-5 text-sm text-gray-600 dark:text-gray-300">
                        <li>Minimum lease duration: 4 months</li>
                        <li>Security deposit: 1 month's rent</li>
                        <li>Rent due on the 1st of each month</li>
                        <li>Late payment fee: 5% after 5 days grace period</li>
                        <li>No subletting without owner's consent</li>
                    </ul>
                </div>

                <button type="submit" class="w-full bg-blue-500 text-white py-3 rounded-lg hover:bg-blue-600 focus:ring-2 focus:ring-yellow-400 flex items-center justify-center gap-2">
                    <i class="fas fa-file-signature"></i> Create Lease Agreement
                </button>
            </form>
        </div>
    </div>

    <button class="theme-toggle fixed bottom-4 right-4 bg-blue-500 text-white w-10 h-10 rounded-full flex items-center justify-center shadow-lg hover:bg-blue-600" aria-label="Toggle theme">
        <i class="fas fa-moon"></i>
    </button>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const startDateInput = document.getElementById('start_date');
            const leaseMonthsInput = document.getElementById('lease_months');
            const endDateInput = document.getElementById('end_date');
            const leaseForm = document.getElementById('leaseForm');
            const today = new Date().toISOString().split('T')[0];
            startDateInput.min = today;

            function updateEndDate() {
                const startDate = new Date(startDateInput.value);
                const leaseMonths = parseInt(leaseMonthsInput.value);
                if (!isNaN(startDate.getTime()) && leaseMonths) {
                    const endDate = new Date(startDate);
                    endDate.setMonth(endDate.getMonth() + leaseMonths);
                    endDateInput.value = endDate.toISOString().split('T')[0];
                    endDateInput.min = startDateInput.value;
                }
            }

            startDateInput.addEventListener('change', updateEndDate);
            leaseMonthsInput.addEventListener('change', updateEndDate);

            leaseForm.addEventListener('submit', (e) => {
                let valid = true;
                const startDateError = document.getElementById('start_date_error');
                const endDateError = document.getElementById('end_date_error');

                startDateError.classList.add('hidden');
                endDateError.classList.add('hidden');

                const startDate = new Date(startDateInput.value);
                const endDate = new Date(endDateInput.value);

                if (startDate < new Date(today)) {
                    startDateError.textContent = 'Start date cannot be in the past.';
                    startDateError.classList.remove('hidden');
                    valid = false;
                }

                if (endDate <= startDate) {
                    endDateError.textContent = 'End date must be after start date.';
                    endDateError.classList.remove('hidden');
                    valid = false;
                }

                const monthsDiff = (endDate.getFullYear() - startDate.getFullYear()) * 12 + endDate.getMonth() - startDate.getMonth();
                if (monthsDiff < 4) {
                    endDateError.textContent = 'Minimum lease duration is 4 months.';
                    endDateError.classList.remove('hidden');
                    valid = false;
                }

                if (!valid) {
                    e.preventDefault();
                }
            });

            const themeToggle = document.querySelector('.theme-toggle');
            themeToggle.addEventListener('click', () => {
                const html = document.documentElement;
                const isDark = html.classList.contains('dark');
                if (isDark) {
                    html.classList.remove('dark');
                    themeToggle.innerHTML = '<i class="fas fa-moon"></i>';
                    localStorage.setItem('theme', 'light');
                } else {
                    html.classList.add('dark');
                    themeToggle.innerHTML = '<i class="fas fa-sun"></i>';
                    localStorage.setItem('theme', 'dark');
                }
            });

            if (localStorage.getItem('theme') === 'dark') {
                document.documentElement.classList.add('dark');
                themeToggle.innerHTML = '<i class="fas fa-sun"></i>';
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>