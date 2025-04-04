<?php
require_once 'db-connect.php';
redirectIfNotAdmin();

// Handle organization actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_org'])) {
        // Create new organization
        $name = sanitizeInput($_POST['name']);
        $description = sanitizeInput($_POST['description']);
        $mission = sanitizeInput($_POST['mission']);
        $adviser = sanitizeInput($_POST['adviser']);
        $contactEmail = sanitizeInput($_POST['contact_email']);

        try {
            executeQuery(
                "INSERT INTO organizations (name, description, mission, adviser, contact_email, created_by) 
                VALUES (?, ?, ?, ?, ?, ?)",
                [$name, $description, $mission, $adviser, $contactEmail, $_SESSION['user_id']]
            );
            $_SESSION['success'] = "Organization created successfully!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error creating organization: " . $e->getMessage();
        }
    } elseif (isset($_POST['update_org'])) {
        // Update existing organization
        $orgId = sanitizeInput($_POST['org_id']);
        $name = sanitizeInput($_POST['name']);
        $description = sanitizeInput($_POST['description']);
        $mission = sanitizeInput($_POST['mission']);
        $adviser = sanitizeInput($_POST['adviser']);
        $contactEmail = sanitizeInput($_POST['contact_email']);

        try {
            executeQuery(
                "UPDATE organizations SET name = ?, description = ?, mission = ?, adviser = ?, contact_email = ? 
                WHERE id = ?",
                [$name, $description, $mission, $adviser, $contactEmail, $orgId]
            );
            $_SESSION['success'] = "Organization updated successfully!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error updating organization: " . $e->getMessage();
        }
    } elseif (isset($_POST['delete_org'])) {
        // Delete organization
        $orgId = sanitizeInput($_POST['org_id']);

        try {
            executeQuery("DELETE FROM organizations WHERE id = ?", [$orgId]);
            $_SESSION['success'] = "Organization deleted successfully!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error deleting organization: " . $e->getMessage();
        }
    }
    header("Location: org-management.php");
    exit();
}

// Get all organizations
$organizations = executeQuery("
    SELECT o.*, u.full_name as created_by_name 
    FROM organizations o 
    LEFT JOIN users u ON o.created_by = u.id
")->fetchAll();

// Get all org officers for dropdown
$officers = executeQuery("SELECT id, full_name FROM users WHERE role = 'org_officer'")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organization Management | DYCI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100">
    <?php include 'admin-dashboard.php'; ?>

    <div class="ml-64 p-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Organization Management</h1>
            <button onclick="document.getElementById('createOrgModal').classList.remove('hidden')" 
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                <i class="fas fa-plus-circle mr-2"></i> Add Organization
            </button>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Adviser</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created By</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($organizations as $org): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="font-medium"><?php echo $org['name']; ?></div>
                                    <div class="text-sm text-gray-500"><?php echo substr($org['description'], 0, 50) . '...'; ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo $org['adviser']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo $org['contact_email']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo $org['created_by_name']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <button onclick="openEditModal(
                                        '<?php echo $org['id']; ?>',
                                        '<?php echo htmlspecialchars($org['name'], ENT_QUOTES); ?>',
                                        '<?php echo htmlspecialchars($org['description'], ENT_QUOTES); ?>',
                                        '<?php echo htmlspecialchars($org['mission'], ENT_QUOTES); ?>',
                                        '<?php echo htmlspecialchars($org['adviser'], ENT_QUOTES); ?>',
                                        '<?php echo htmlspecialchars($org['contact_email'], ENT_QUOTES); ?>'
                                    )" class="text-blue-600 hover:text-blue-900 mr-3">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="confirmDelete('<?php echo $org['id']; ?>')" class="text-red-600 hover:text-red-900">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                    <a href="org-details.php?id=<?php echo $org['id']; ?>" class="text-green-600 hover:text-green-900 ml-3">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Create Organization Modal -->
    <div id="createOrgModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">Create New Organization</h3>
                <button onclick="document.getElementById('createOrgModal').classList.add('hidden')" class="text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Name</label>
                        <input type="text" name="name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea name="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Mission</label>
                        <textarea name="mission" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Adviser</label>
                        <input type="text" name="adviser" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Contact Email</label>
                        <input type="email" name="contact_email" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="document.getElementById('createOrgModal').classList.add('hidden')" 
                        class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" name="create_org" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700">
                        Create Organization
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Organization Modal -->
    <div id="editOrgModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">Edit Organization</h3>
                <button onclick="document.getElementById('editOrgModal').classList.add('hidden')" class="text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST">
                <input type="hidden" name="org_id" id="editOrgId">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Name</label>
                        <input type="text" name="name" id="editName" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea name="description" id="editDescription" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Mission</label>
                        <textarea name="mission" id="editMission" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Adviser</label>
                        <input type="text" name="adviser" id="editAdviser" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Contact Email</label>
                        <input type="email" name="contact_email" id="editContactEmail" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="document.getElementById('editOrgModal').classList.add('hidden')" 
                        class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" name="update_org" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700">
                        Update Organization
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteOrgModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="text-center">
                <i class="fas fa-exclamation-triangle text-red-500 text-4xl mb-4"></i>
                <h3 class="text-lg font-semibold">Confirm Deletion</h3>
                <p class="text-gray-600 mt-2">Are you sure you want to delete this organization? This action cannot be undone.</p>
            </div>
            <form method="POST" class="mt-6 flex justify-center space-x-3">
                <input type="hidden" name="org_id" id="deleteOrgId">
                <button type="button" onclick="document.getElementById('deleteOrgModal').classList.add('hidden')" 
                    class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" name="delete_org" 
                    class="px-4 py-2 bg-red-600 text-white rounded-md text-sm font-medium hover:bg-red-700">
                    Delete
                </button>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(id, name, description, mission, adviser, contactEmail) {
            document.getElementById('editOrgId').value = id;
            document.getElementById('editName').value = name;
            document.getElementById('editDescription').value = description;
            document.getElementById('editMission').value = mission;
            document.getElementById('editAdviser').value = adviser;
            document.getElementById('editContactEmail').value = contactEmail;
            
            document.getElementById('editOrgModal').classList.remove('hidden');
        }

        function confirmDelete(orgId) {
            document.getElementById('deleteOrgId').value = orgId;
            document.getElementById('deleteOrgModal').classList.remove('hidden');
        }
    </script>
</body>
</html>