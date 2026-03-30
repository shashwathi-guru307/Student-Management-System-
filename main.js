/**
 * Main application JS utility file
 */

// Helper to show alert messages in forms
function showAlert(elementId, message, type = 'error') {
    const alertDiv = document.getElementById(elementId);
    if (!alertDiv) return;
    
    alertDiv.textContent = message;
    alertDiv.className = `alert alert-${type}`;
    alertDiv.style.display = 'block';
    
    // Auto hide after 5 seconds
    setTimeout(() => {
        alertDiv.style.display = 'none';
    }, 5000);
}

// Global logout function
async function logout() {
    if(confirm('Are you sure you want to logout?')) {
        try {
            const response = await fetch('api/auth.php?action=logout');
            const data = await response.json();
            if(data.success) {
                window.location.href = 'index.html';
            }
        } catch (error) {
            console.error('Logout error:', error);
        }
    }
}

// Load common dashboard interactions
document.addEventListener('DOMContentLoaded', () => {
    // Add logout listener to any button with id 'logout-btn'
    const logoutBtn = document.getElementById('logout-btn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', (e) => {
            e.preventDefault();
            logout();
        });
    }
});
