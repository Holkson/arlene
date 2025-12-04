<?php
// admin.php - Add this at the VERY TOP lines 1-7
session_start();

// Check if user is logged in
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    // If not, kick them to login page
    header("Location: login.php");
    exit;
}

require_once 'db_connect.php'; // Load DB Connection
require_once 'functions.php';  // Load Business Logic

$message = "";
$view = 'list'; // default view
$editData = null;

// --- 1. Handle Form Submission (Update) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_booking'])) {
    calculateBookingAndSave($_POST['id'], $_POST, $pdo);
    $message = "Booking updated and recalculated successfully!";
    // refresh edit data after save
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
    $stmt->execute([$_POST['id']]);
    $editData = $stmt->fetch();
    $view = 'edit';
}

// --- 2. Handle Edit View Request ---
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $view = 'edit';
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $editData = $stmt->fetch();
    if (!$editData) die("Booking not found.");
}

// --- 3. Handle Delete Request ---
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("DELETE FROM bookings WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $message = "Booking deleted successfully!";
    $view = 'list';
}

// --- 4. Filters (for list view) ---
$filter_status = $_GET['status'] ?? '';
$filter_organizer = $_GET['organizer'] ?? '';
$filter_date = $_GET['date'] ?? '';

// --- 5. Fetch Data (FIXED: Added this section) ---
$bookings = []; // Initialize to empty array to prevent "Undefined variable" error

if ($view === 'list') {
    $sql = "SELECT * FROM bookings WHERE 1=1";
    $params = [];

    // Apply Filters
    if (!empty($filter_status)) {
        $sql .= " AND status = ?";
        $params[] = $filter_status;
    }
    if (!empty($filter_organizer)) {
        $sql .= " AND organizer LIKE ?";
        $params[] = "%$filter_organizer%";
    }
    if (!empty($filter_date)) {
        $sql .= " AND DATE(date_time) = ?";
        $params[] = $filter_date;
    }

    $sql .= " ORDER BY date_time DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $bookings = $stmt->fetchAll();
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | FHTA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>@media print { .no-print { display: none; } }</style>
</head>
<body class="bg-gray-100 min-h-screen font-sans">

    <nav class="bg-green-800 text-white p-4 shadow-md">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <div class="flex gap-4">
            <a href="admin.php" class="hover:underline">Dashboard</a>
            <a href="logout.php" class="bg-red-600 px-3 py-1 rounded text-sm hover:bg-red-700">Logout</a>
        </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto p-6">
        
        <?php if ($message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($view === 'edit' && $editData): ?>
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <div class="bg-gray-50 px-6 py-4 border-b flex justify-between items-center">
                    <h2 class="text-xl font-semibold text-gray-800">Edit Booking #<?= htmlspecialchars($editData['id']) ?></h2>
                    <div class="flex items-center gap-2">
                        <a href="pdf_generator.php?id=<?= htmlspecialchars($editData['id']) ?>" target="_blank" class="bg-red-600 text-white px-4 py-2 rounded text-sm hover:bg-red-700 mr-2 no-print">
                            Download PDF Invoice
                        </a>
                        <a href="admin.php" class="inline-block bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 no-print">Back to List</a>                    
                    </div>
                </div>

                <form method="POST" action="admin.php?action=edit&id=<?= htmlspecialchars($editData['id']) ?>" class="p-6 space-y-6">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($editData['id']) ?>">

                    <!-- Row: Primary details -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-blue-50 p-4 rounded border border-blue-100">
                            <h3 class="font-bold text-blue-800 mb-2">Primary Details</h3>

                            <label class="block text-xs font-bold text-gray-600">Date & Time</label>
                            <input type="datetime-local" name="date_time" value="<?= htmlspecialchars(date('Y-m-d\TH:i', strtotime($editData['date_time'] ?? '')) ) ?>" class="w-full border p-2 rounded mb-3">

                            <label class="block text-xs font-bold text-gray-600">Organizer</label>
                            <input type="text" name="organizer" value="<?= htmlspecialchars($editData['organizer'] ?? '') ?>" class="w-full border p-2 rounded mb-3">

                            <label class="block text-xs font-bold text-gray-600">Phone</label>
                            <input type="text" name="phone" value="<?= htmlspecialchars($editData['phone'] ?? '') ?>" class="w-full border p-2 rounded mb-3">

                            <label class="block text-xs font-bold text-gray-600">Guide Count</label>
                            <input type="number" name="guide_count" value="<?= htmlspecialchars($editData['guide_count'] ?? 0) ?>" class="w-full border p-2 rounded mb-3">

                            <label class="block text-xs font-bold text-gray-600">Remark</label>
                            <textarea name="remark" class="w-full border p-2 rounded" rows="3"><?= htmlspecialchars($editData['remark'] ?? '') ?></textarea>
                        </div>

                        <div class="bg-yellow-50 p-4 rounded border border-yellow-100">
                            <h3 class="font-bold text-yellow-800 mb-2">Verifications & Files</h3>

                            <div class="mb-3 text-sm">
                                <div class="font-semibold">Tour Guide License</div>
                                <?php if (!empty($editData['guide_file_path'])): ?>
                                    <div><a href="<?= htmlspecialchars($editData['guide_file_path']) ?>" target="_blank" class="text-blue-600 underline">View License</a></div>
                                <?php else: ?>
                                    <div class="text-gray-400">(No file)</div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3 text-sm">
                                <div class="font-semibold">OKU Document</div>
                                <?php if (!empty($editData['oku_file_path'])): ?>
                                    <div><a href="<?= htmlspecialchars($editData['oku_file_path']) ?>" target="_blank" class="text-blue-600 underline">View Document</a></div>
                                <?php else: ?>
                                    <div class="text-gray-400">(No file)</div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="bg-gray-100 p-4 rounded">
                            <h3 class="font-bold text-gray-800 mb-2">Admin Controls</h3>

                            <label class="block text-sm font-semibold">Status</label>
                            <select name="status" class="w-full border p-2 rounded mb-3">
                                <?php foreach(['Pending','Confirmed','Cancelled','Completed'] as $s): ?>
                                    <option value="<?= htmlspecialchars($s) ?>" <?= (isset($editData['status']) && $editData['status'] == $s) ? 'selected' : '' ?>><?= htmlspecialchars($s) ?></option>
                                <?php endforeach; ?>
                            </select>

                            <label class="block text-sm font-semibold">Discount (%)</label>
                            <input type="number" step="0.01" name="discount_percent" value="<?= htmlspecialchars($editData['discount_percent'] ?? 0) ?>" class="w-full border p-2 rounded mb-2">
                            <p class="text-xs text-gray-500 mb-2">Applied to total after FOC deduction.</p>

                            <label class="block text-sm font-semibold">Paid Teachers (readonly)</label>
                            <input type="number" readonly value="<?= htmlspecialchars($editData['paid_teachers'] ?? 0) ?>" class="w-full border p-2 rounded mb-2 bg-gray-50">

                            <label class="block text-sm font-semibold">FOC Teachers (readonly)</label>
                            <input type="number" readonly value="<?= htmlspecialchars($editData['foc_teachers'] ?? 0) ?>" class="w-full border p-2 rounded mb-2 bg-gray-50">
                        </div>
                    </div>

                    <!-- Ticket counts (editable) -->
                    <div class="bg-white p-4 rounded border">
                        <h3 class="font-bold text-gray-800 mb-3 border-b pb-1">Ticket Counts (Editable)</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-600">Adult MY (RM15)</label>
                                <input type="number" name="adult_my" value="<?= htmlspecialchars($editData['adult_my'] ?? 0) ?>" class="w-full border p-2 rounded">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-600">Adult Foreign (RM25)</label>
                                <input type="number" name="adult_foreign" value="<?= htmlspecialchars($editData['adult_foreign'] ?? 0) ?>" class="w-full border p-2 rounded">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-600">Child MY 1-5 (RM10)</label>
                                <input type="number" name="child_my_1to5" value="<?= htmlspecialchars($editData['child_my_1to5'] ?? 0) ?>" class="w-full border p-2 rounded">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-600">Child MY &gt;5 (RM15)</label>
                                <input type="number" name="child_my_above5" value="<?= htmlspecialchars($editData['child_my_above5'] ?? 0) ?>" class="w-full border p-2 rounded">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-600">Child For. 1-5 (RM20)</label>
                                <input type="number" name="child_foreign_1to5" value="<?= htmlspecialchars($editData['child_foreign_1to5'] ?? 0) ?>" class="w-full border p-2 rounded">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-600">Child For. &gt;5 (RM25)</label>
                                <input type="number" name="child_foreign_above5" value="<?= htmlspecialchars($editData['child_foreign_above5'] ?? 0) ?>" class="w-full border p-2 rounded">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-600">OKU MY (RM10)</label>
                                <input type="number" name="oku_my" value="<?= htmlspecialchars($editData['oku_my'] ?? 0) ?>" class="w-full border p-2 rounded">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-600">OKU Foreign (RM20)</label>
                                <input type="number" name="oku_foreign" value="<?= htmlspecialchars($editData['oku_foreign'] ?? 0) ?>" class="w-full border p-2 rounded">
                            </div>
                        </div>
                    </div>

                    <!-- Calculation summary (readonly) -->
                    <div class="bg-green-50 p-4 rounded border border-green-200">
                        <h3 class="font-bold text-green-800 mb-2">Current Calculation Results (readonly)</h3>
                        <div class="flex justify-between border-b py-1">
                            <span>Subtotal (Raw):</span>
                            <span class="font-mono">RM<?= number_format((float)($editData['subtotal_amount'] ?? 0), 2) ?></span>
                        </div>
                        <div class="flex justify-between border-b py-1 text-sm text-gray-600">
                            <span>FOC Teachers (Ratio 1:10):</span>
                            <span class="font-mono"><?= htmlspecialchars($editData['foc_teachers'] ?? 0) ?> Pax</span>
                        </div>
                        <div class="flex justify-between border-b py-1 text-sm text-gray-600">
                            <span>Paid Teachers:</span>
                            <span class="font-mono"><?= htmlspecialchars($editData['paid_teachers'] ?? 0) ?> Pax</span>
                        </div>
                        <div class="flex justify-between border-b py-1 text-sm text-gray-600">
                            <span>Discount Applied:</span>
                            <span class="font-mono"><?= htmlspecialchars($editData['discount_percent'] ?? 0) ?>%</span>
                        </div>
                        <div class="flex justify-between py-2 text-xl font-bold text-green-700 mt-2">
                            <span>FINAL PRICE:</span>
                            <span>RM<?= number_format((float)($editData['final_price'] ?? 0), 2) ?></span>
                        </div>
                    </div>

                    <div class="text-right">
                        <button type="submit" name="save_booking" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 shadow font-semibold no-print">
                            Save Changes & Recalculate
                        </button>
                        <a href="admin.php" class="inline-block bg-gray-200 text-gray-800 px-4 py-3 rounded-lg ml-2 no-print">Cancel</a>
                    </div>
                </form>
            </div>

        <?php else: ?>
            <!-- LIST VIEW + FILTER UI -->
            <div class="bg-white p-4 mb-4 rounded-lg shadow-md">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    <div>
                        <label class="block text-sm font-semibold">Status</label>
                        <select name="status" class="w-full border p-2 rounded">
                            <option value="">All</option>
                            <option value="Pending" <?= ($filter_status=='Pending'?'selected':'') ?>>Pending</option>
                            <option value="Confirmed" <?= ($filter_status=='Confirmed'?'selected':'') ?>>Confirmed</option>
                            <option value="Cancelled" <?= ($filter_status=='Cancelled'?'selected':'') ?>>Cancelled</option>
                            <option value="Completed" <?= ($filter_status=='Completed'?'selected':'') ?>>Completed</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold">Organizer</label>
                        <input type="text" name="organizer" value="<?= htmlspecialchars($filter_organizer) ?>" class="w-full border p-2 rounded" placeholder="Organizer name">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold">Date</label>
                        <input type="date" name="date" value="<?= htmlspecialchars($filter_date) ?>" class="w-full border p-2 rounded">
                    </div>

                    <div class="flex gap-2">
                        <button class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 w-full">Filter</button>
                        <a href="admin.php" class="bg-gray-200 text-gray-800 px-4 py-2 rounded w-full text-center">Reset</a>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-lg shadow overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
                        <tr>
                            <th class="p-4 border-b">ID</th>
                            <th class="p-4 border-b">Date</th>
                            <th class="p-4 border-b">Organizer</th>
                            <th class="p-4 border-b">Status</th>
                            <th class="p-4 border-b">Paid / FOC Tch</th>
                            <th class="p-4 border-b">Final Price</th>
                            <th class="p-4 border-b">Action</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm">
                        <?php foreach ($bookings as $b): ?>
                        <tr class="hover:bg-gray-50 border-b">
                            <td class="p-4 font-bold text-gray-700">#<?= htmlspecialchars($b['id']) ?></td>
                            <td class="p-4"><?= htmlspecialchars(date('Y-m-d H:i', strtotime($b['date_time'] ?? ''))) ?></td>
                            <td class="p-4">
                                <div class="font-semibold"><?= htmlspecialchars($b['organizer'] ?? '') ?></div>
                                <div class="text-xs text-gray-500"><?= htmlspecialchars($b['phone'] ?? '') ?></div>
                            </td>
                            <td class="p-4">
                                <?php
                                    $status = $b['status'] ?? 'Pending';
                                    $statusClass = 'bg-gray-100 text-gray-800';
                                    if ($status == 'Confirmed') $statusClass = 'bg-green-100 text-green-800';
                                    elseif ($status == 'Pending') $statusClass = 'bg-yellow-100 text-yellow-800';
                                    elseif ($status == 'Cancelled') $statusClass = 'bg-red-100 text-red-800';
                                    elseif ($status == 'Completed') $statusClass = 'bg-blue-100 text-blue-800';
                                ?>
                                <span class="px-2 py-1 rounded text-xs font-semibold <?= $statusClass ?>">
                                    <?= htmlspecialchars($status) ?>
                                </span>
                            </td>
                            <td class="p-4">
                                Paid: <?= htmlspecialchars($b['paid_teachers'] ?? 0) ?><br>
                                <span class="text-green-600 font-bold">FOC: <?= htmlspecialchars($b['foc_teachers'] ?? 0) ?></span>
                            </td>
                            <td class="p-4 font-bold">RM<?= number_format((float)($b['final_price'] ?? 0), 2) ?></td>
                            <td class="p-4">
                                <div class="flex gap-2">
                                    <a href="admin.php?action=edit&id=<?= htmlspecialchars($b['id']) ?>" class="text-blue-600 hover:text-blue-800 font-semibold border border-blue-600 px-3 py-1 rounded hover:bg-blue-50 no-print">Edit</a>

                                    <a href="admin.php?action=delete&id=<?= htmlspecialchars($b['id']) ?>" 
                                       onclick="return confirm('Are you sure you want to delete this booking?');"
                                       class="text-red-600 hover:text-red-800 font-semibold border border-red-600 px-3 py-1 rounded hover:bg-red-50 no-print">
                                       Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($bookings)): ?>
                            <tr><td colspan="7" class="p-8 text-center text-gray-500">No bookings found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>