<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CRUD App</title>
    <link rel="stylesheet" href="assets/css/crud.css">
</head>
<body>
    <div class="crud-container">
        <h1>Manage Records</h1>
        <form id="crudForm">
            <input type="hidden" name="id" id="recordId">
            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" name="name" id="name" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" required>
            </div>
            <button type="submit" id="submitBtn">Add Record</button>
            <button type="button" id="updateBtn" style="display:none;">Update</button>
            <button type="button" id="cancelBtn" style="display:none;">Cancel</button>
        </form>
        <div id="message"></div>
        <table id="recordsTable">
            <thead>
                <tr><th>ID</th><th>Name</th><th>Email</th><th>Created</th><th>Actions</th></tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
    <script src="assets/js/crud.js"></script>
</body>
</html>
