<header class="fm-header">
  <div class="fm-header-left">
    <span class="fm-logo">📁 FileManager</span>
  </div>
  <div class="fm-header-center">
    <input type="text" class="fm-search" placeholder="Search files, folders...">
  </div>
  <div class="fm-header-right">
    <button class="fm-icon-btn" id="fm-notifications" title="Notifications">
      <span class="fm-icon">🔔</span>
      <span class="fm-badge">3</span>
    </button>
    <button class="fm-icon-btn" id="fm-settings" title="Settings">
      <span class="fm-icon">⚙️</span>
    </button>
    <button class="fm-icon-btn" id="fm-profile" title="Profile">
      <span class="fm-icon">👤</span>
    </button>
  </div>
</header>


<style>
.fm-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: #232946;
  color: #fff;
  padding: 0 32px;
  height: 64px;
  box-shadow: 0 2px 8px rgba(35,41,70,0.04);
  position: sticky;
  top: 0;
  z-index: 100;
}

.fm-header-left .fm-logo {
  font-size: 1.4em;
  font-weight: bold;
  letter-spacing: 1px;
}

.fm-header-center {
  flex: 1;
  display: flex;
  justify-content: center;
}

.fm-search {
  width: 320px;
  max-width: 100%;
  padding: 8px 16px;
  border-radius: 20px;
  border: none;
  font-size: 1em;
  background: #f4f6f8;
  color: #232946;
  outline: none;
  transition: box-shadow 0.2s;
  box-shadow: 0 1px 4px rgba(35,41,70,0.05);
}

.fm-search:focus {
  box-shadow: 0 2px 8px rgba(238,187,195,0.15);
}

.fm-header-right {
  display: flex;
  align-items: center;
  gap: 16px;
}

.fm-icon-btn {
  background: none;
  border: none;
  cursor: pointer;
  position: relative;
  padding: 8px;
  border-radius: 50%;
  transition: background 0.2s;
}

.fm-icon-btn:hover {
  background: #393e6e;
}

.fm-icon {
  font-size: 1.4em;
  vertical-align: middle;
}

.fm-badge {
  position: absolute;
  top: 6px;
  right: 6px;
  background: #eebbc3;
  color: #232946;
  font-size: 0.7em;
  padding: 2px 6px;
  border-radius: 12px;
  font-weight: bold;
  pointer-events: none;
}

</style>
<script>
// Example event listeners for header icons
document.getElementById('fm-notifications').addEventListener('click', () => {
  alert('You have 3 new notifications.');
});

document.getElementById('fm-settings').addEventListener('click', () => {
  alert('Open settings dialog.');
});

document.getElementById('fm-profile').addEventListener('click', () => {
  alert('Open profile menu.');
});

document.querySelector('.fm-search').addEventListener('input', (e) => {
  // Implement search/filter logic here
  console.log('Searching for:', e.target.value);
});

</script>
<script src="assets/lib/jquery/jquery-3.6.0.min.js"></script>
<script src="assets/lib/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jstree/dist/jstree.min.js"></script>