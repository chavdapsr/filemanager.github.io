<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>File Manager</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="styles.css">
<style>
    body {
  margin: 0;
  font-family: 'Segoe UI', Arial, sans-serif;
  background: #f4f6f8;
}

.file-manager {
  display: flex;
  height: 100vh;
  min-height: 100vh;
}

.sidebar {
  width: 220px;
  background: #232946;
  color: #fff;
  padding: 24px 0;
  box-shadow: 2px 0 8px rgba(0,0,0,0.04);
}

.sidebar h2 {
  margin: 0 0 24px 24px;
  font-size: 1.2em;
  letter-spacing: 1px;
}

.sidebar ul {
  list-style: none;
  padding: 0;
}

.sidebar li {
  padding: 12px 24px;
  cursor: pointer;
  border-left: 4px solid transparent;
  transition: background 0.2s, border-color 0.2s;
}

.sidebar li.active,
.sidebar li:hover {
  background: #393e6e;
  border-left: 4px solid #eebbc3;
}

.main-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-width: 0;
  padding: 0;
}

.fm-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #fff;
  padding: 0 32px;
  height: 64px;
  box-shadow: 0 2px 8px rgba(35,41,70,0.04);
  border-radius: 0 0 8px 8px;
}

.fm-header-left {
  display: flex;
  align-items: center;
  gap: 24px;
}

.fm-logo {
  font-weight: bold;
  font-size: 1.3em;
  color: #232946;
  letter-spacing: 1px;
}

.fm-search {
  display: flex;
  align-items: center;
  background: #f4f6f8;
  border-radius: 6px;
  padding: 0 8px;
  height: 36px;
  box-shadow: 0 1px 2px rgba(35,41,70,0.03);
}

.fm-search input[type="search"] {
  border: none;
  background: transparent;
  outline: none;
  padding: 0 8px;
  font-size: 1em;
  width: 180px;
}

.fm-search button {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 1.1em;
  color: #393e6e;
}

.fm-header-right {
  display: flex;
  align-items: center;
  gap: 18px;
}

.fm-icon-btn {
  background: none;
  border: none;
  position: relative;
  cursor: pointer;
  padding: 6px;
  border-radius: 50%;
  transition: background 0.2s;
}

.fm-icon-btn:hover {
  background: #f4f6f8;
}

.fm-badge {
  position: absolute;
  top: 2px;
  right: 2px;
  background: #eebbc3;
  color: #232946;
  font-size: 0.75em;
  border-radius: 50%;
  padding: 2px 5px;
  min-width: 16px;
  text-align: center;
  font-weight: bold;
}

.fm-profile img {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid #eebbc3;
}

.file-table-section {
  flex: 1;
  overflow-x: auto;
  background: #fff;
  margin: 24px 32px 32px 32px;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(35,41,70,0.04);
  padding: 0;
}

.file-table {
  width: 100%;
  border-collapse: collapse;
  min-width: 650px;
}

.file-table thead {
  background: #f4f6f8;
}

.file-table th, .file-table td {
  padding: 14px 12px;
  text-align: left;
  font-size: 1em;
}

.file-table tbody tr {
  transition: background 0.2s;
  cursor: pointer;
}

.file-table tbody tr:hover {
  background: #f0e9f7;
}

.file-table td.menu-cell {
  width: 40px;
  text-align: center;
}

.menu-btn {
  background: none;
  border: none;
  font-size: 1.2em;
  cursor: pointer;
  color: #393e6e;
  border-radius: 50%;
  padding: 4px;
  transition: background 0.2s;
}

.menu-btn:hover {
  background: #eebbc3;
  color: #232946;
}

.popup-menu {
  display: none;
  position: absolute;
  z-index: 1000;
  background: #fff;
  box-shadow: 0 4px 24px rgba(35,41,70,0.13);
  border-radius: 8px;
  min-width: 140px;
  font-size: 1em;
  padding: 8px 0;
  animation: fadeIn 0.2s;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-10px);}
  to { opacity: 1; transform: translateY(0);}
}

.popup-menu ul {
  list-style: none;
  margin: 0;
  padding: 0;
}

.popup-menu li {
  padding: 10px 20px;
  cursor: pointer;
  transition: background 0.2s;
}

.popup-menu li:hover {
  background: #f4f6f8;
}

.hover-popup {
  display: none;
  position: absolute;
  z-index: 1001;
  background: #fff;
  color: #232946;
  padding: 10px 18px;
  border-radius: 8px;
  box-shadow: 0 4px 24px rgba(35,41,70,0.13);
  font-size: 0.96em;
  pointer-events: none;
  max-width: 260px;
}

@media (max-width: 900px) {
  .sidebar { display: none; }
  .main-content { padding: 0; }
  .file-table-section { margin: 12px; }
}
@media (max-width: 600px) {
  .fm-header, .file-table-section { margin: 0; border-radius: 0; }
  .file-table { min-width: 400px; }
}
</style>
</head>
<body>
  <div class="file-manager">
    <aside class="sidebar">
      <h2>Folders</h2>
      <ul>
        <li class="active">My Files</li>
        <li>Documents</li>
        <li>Pictures</li>
        <li>Videos</li>
        <li>Trash</li>
      </ul>
    </aside>
    <main class="main-content">
      <header class="fm-header">
        <div class="fm-header-left">
          <span class="fm-logo">FileManager</span>
          <div class="fm-search">
            <input type="search" placeholder="Search files..." id="fmSearchInput" />
            <button id="fmSearchBtn" title="Search">🔍</button>
          </div>
        </div>
        <div class="fm-header-right">
          <button class="fm-icon-btn" id="fmNotifBtn" title="Notifications">🔔<span class="fm-badge">3</span></button>
          <button class="fm-icon-btn" id="fmSettingsBtn" title="Settings">⚙️</button>
          <div class="fm-profile">
            <img src="https://i.pravatar.cc/40?img=3" alt="Profile" />
          </div>
        </div>
      </header>
      <section class="file-table-section">
        <table class="file-table">
          <thead>
            <tr>
              <th>File Name</th>
              <th>Size</th>
              <th>Last Modified</th>
              <th>Matter</th>
              <th></th>
            </tr>
          </thead>
          <tbody id="fileTableBody">
            <!-- JS will populate rows -->
          </tbody>
        </table>
      </section>
    </main>
  </div>

  <!-- Popup Menu -->
  <div class="popup-menu" id="popupMenu">
    <ul>
      <li>Open</li>
      <li>Download</li>
      <li>Rename</li>
      <li>Delete</li>
    </ul>
  </div>

  <!-- Hover Preview Popup -->
  <div class="hover-popup" id="hoverPopup"></div>
  <script>
    // Sample file data
const files = [
  {
    name: "Resume.pdf",
    size: "120 KB",
    modified: "2025-07-10 15:23",
    matter: "Job Application",
    type: "pdf",
    details: "A PDF document containing the latest resume for job applications."
  },
  {
    name: "Project.zip",
    size: "4.2 MB",
    modified: "2025-07-08 09:10",
    matter: "Project Alpha",
    type: "zip",
    details: "Compressed archive of Project Alpha source code and documentation."
  },
  {
    name: "Photo.jpg",
    size: "2.1 MB",
    modified: "2025-07-09 18:05",
    matter: "Vacation",
    type: "image",
    details: "High-resolution photo from the last vacation."
  },
  {
    name: "Notes.txt",
    size: "6 KB",
    modified: "2025-07-07 11:00",
    matter: "Personal",
    type: "txt",
    details: "Personal notes and reminders."
  },
  {
    name: "Meeting.pptx",
    size: "1.3 MB",
    modified: "2025-07-05 14:30",
    matter: "Work",
    type: "pptx",
    details: "Presentation for the upcoming work meeting."
  }
];

const icons = {
  pdf: "📄",
  zip: "🗜️",
  image: "🖼️",
  txt: "📝",
  pptx: "📊",
  default: "📁"
};

const fileTableBody = document.getElementById('fileTableBody');
const popupMenu = document.getElementById('popupMenu');
const hoverPopup = document.getElementById('hoverPopup');

function renderFiles() {
  fileTableBody.innerHTML = '';
  files.forEach((file, idx) => {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td class="file-name-cell" data-idx="${idx}">
        <span class="file-icon">${icons[file.type] || icons.default}</span> 
        ${file.name}
      </td>
      <td>${file.size}</td>
      <td>${file.modified}</td>
      <td>${file.matter}</td>
      <td class="menu-cell">
        <button class="menu-btn" data-idx="${idx}" title="File actions">⋮</button>
      </td>
    `;
    fileTableBody.appendChild(tr);
  });
}

renderFiles();

// Popup menu logic
document.addEventListener('click', function(e) {
  if (e.target.classList.contains('menu-btn')) {
    const idx = e.target.getAttribute('data-idx');
    showPopupMenu(e.target, idx);
  } else if (!popupMenu.contains(e.target)) {
    popupMenu.style.display = 'none';
  }
});

function showPopupMenu(target, idx) {
  popupMenu.style.display = 'block';
  const rect = target.getBoundingClientRect();
  popupMenu.style.top = (window.scrollY + rect.bottom + 4) + 'px';
  popupMenu.style.left = (window.scrollX + rect.left - 60) + 'px';
  popupMenu.setAttribute('data-idx', idx);
}

// Popup menu item click
popupMenu.addEventListener('click', function(e) {
  if (e.target.tagName === 'LI') {
    const idx = popupMenu.getAttribute('data-idx');
    alert(`"${e.target.textContent}" clicked for file: ${files[idx].name}`);
    popupMenu.style.display = 'none';
  }
});

// Hover popup logic for file name cells
fileTableBody.addEventListener('mouseover', function(e) {
  const cell = e.target.closest('.file-name-cell');
  if (cell) {
    const idx = cell.getAttribute('data-idx');
    hoverPopup.textContent = files[idx].details;
    hoverPopup.style.display = 'block';
    const rect = cell.getBoundingClientRect();
    hoverPopup.style.top = (window.scrollY + rect.bottom + 6) + 'px';
    hoverPopup.style.left = (window.scrollX + rect.left + 8) + 'px';
  }
});
fileTableBody.addEventListener('mouseout', function(e) {
  if (e.target.closest('.file-name-cell')) {
    hoverPopup.style.display = 'none';
  }
});

// Responsive repositioning of popups on scroll/resize
window.addEventListener('scroll', () => {
  popupMenu.style.display = 'none';
  hoverPopup.style.display = 'none';
});
window.addEventListener('resize', () => {
  popupMenu.style.display = 'none';
  hoverPopup.style.display = 'none';
});

// Header interactivity
document.getElementById('fmSearchBtn').addEventListener('click', function() {
  const query = document.getElementById('fmSearchInput').value.trim();
  alert('Searching for: ' + query);
});
document.getElementById('fmNotifBtn').addEventListener('click', function() {
  alert('You have 3 new notifications.');
});
document.getElementById('fmSettingsBtn').addEventListener('click', function() {
  alert('Open settings panel.');
});

    </script>  <script src="script.js"></script>
</body>
</html>
