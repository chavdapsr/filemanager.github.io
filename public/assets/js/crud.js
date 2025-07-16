document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('crudForm');
    const tableBody = document.querySelector('#recordsTable tbody');
    const message = document.getElementById('message');
    const submitBtn = document.getElementById('submitBtn');
    const updateBtn = document.getElementById('updateBtn');
    const cancelBtn = document.getElementById('cancelBtn');
    let editingId = null;

    function showMessage(msg, isError = true) {
        message.textContent = msg;
        message.style.color = isError ? '#d9534f' : '#28a745';
        setTimeout(() => { message.textContent = ''; }, 2000);
    }

    function fetchRecords() {
        fetch('crud.php?action=read')
            .then(res => res.json())
            .then(data => {
                tableBody.innerHTML = '';
                if (data.success && data.data.length) {
                    data.data.forEach(row => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `<td>${row.id}</td><td>${row.name}</td><td>${row.email}</td><td>${row.created_at}</td>
                            <td>
                                <button onclick="editRecord(${row.id}, '${row.name}', '${row.email}')">Edit</button>
                                <button onclick="deleteRecord(${row.id})">Delete</button>
                            </td>`;
                        tableBody.appendChild(tr);
                    });
                }
            });
    }

    window.editRecord = function(id, name, email) {
        document.getElementById('recordId').value = id;
        document.getElementById('name').value = name;
        document.getElementById('email').value = email;
        submitBtn.style.display = 'none';
        updateBtn.style.display = 'inline-block';
        cancelBtn.style.display = 'inline-block';
        editingId = id;
    };

    window.deleteRecord = function(id) {
        if (!confirm('Delete this record?')) return;
        fetch('crud.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=delete&id=${id}`
        })
        .then(res => res.json())
        .then(data => {
            showMessage(data.message, !data.success);
            fetchRecords();
        });
    };

    updateBtn.onclick = function() {
        const id = document.getElementById('recordId').value;
        const name = document.getElementById('name').value.trim();
        const email = document.getElementById('email').value.trim();
        if (!name || !email) { showMessage('All fields required.'); return; }
        fetch('crud.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=update&id=${id}&name=${encodeURIComponent(name)}&email=${encodeURIComponent(email)}`
        })
        .then(res => res.json())
        .then(data => {
            showMessage(data.message, !data.success);
            fetchRecords();
            form.reset();
            submitBtn.style.display = 'inline-block';
            updateBtn.style.display = 'none';
            cancelBtn.style.display = 'none';
            editingId = null;
        });
    };

    cancelBtn.onclick = function() {
        form.reset();
        submitBtn.style.display = 'inline-block';
        updateBtn.style.display = 'none';
        cancelBtn.style.display = 'none';
        editingId = null;
    };

    form.onsubmit = function(e) {
        e.preventDefault();
        if (editingId) return;
        const name = document.getElementById('name').value.trim();
        const email = document.getElementById('email').value.trim();
        if (!name || !email) { showMessage('All fields required.'); return; }
        fetch('crud.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=create&name=${encodeURIComponent(name)}&email=${encodeURIComponent(email)}`
        })
        .then(res => res.json())
        .then(data => {
            showMessage(data.message, !data.success);
            fetchRecords();
            form.reset();
        });
    };

    fetchRecords();
});
