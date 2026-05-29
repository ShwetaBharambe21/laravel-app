// Auto-dismiss success toast after 3 seconds
const toast = document.querySelector('.toast');
if (toast) {
    setTimeout(() => {
        toast.style.transition = 'opacity .4s';
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 400);
    }, 3000);
}

// Sync character counter on page load (handles back-navigation with filled textarea)
const body = document.getElementById('body');
const cc   = document.getElementById('cc');
if (body && cc) {
    cc.textContent = body.value.length + '/500';
}

// Scroll to first message card after a successful post (redirect back)
if (document.referrer.includes(window.location.host)) {
    document.querySelector('.message-card')
        ?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}
