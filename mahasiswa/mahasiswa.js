/* ============= ENHANCED SIDEBAR & NAVIGATION FUNCTIONALITY ============= */

// Global variables
let searchTimeout;
let notificationCount = 0;

// Initialize everything when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeNavigation();
    initializeUserDropdown();
    initializeSearch();
    initializeMobileMenu();
    initializeAnimations();
    initializeNotifications();
    initializeTimeUpdates();
    initializeKeyboardNavigation();
    
    updateNotificationBadge(); 
    
    autoHideAlerts();
    initializeTooltips();
    
    console.log('Enhanced sidebar and navigation initialized');
});

/* ============= NAVIGATION FUNCTIONS ============= */
function initializeNavigation() {
    setActiveNavItem();
    addNavigationEffects();
    addLoadingStates();
}

function setActiveNavItem() {
    const currentPage = window.location.pathname.split('/').pop();
    const navLinks = document.querySelectorAll('.nav-link');
    
    // Remove all active classes
    navLinks.forEach(link => link.classList.remove('active'));
    
    // Enhanced page mapping with alternatives
    const pageMapping = {
        'dashboard_mahasiswa.php': 0,
        'dashboard.php': 0,
        'index.php': 0,
        'lab_virtual.php': 1,
        'virtual_lab.php': 1,
        'lab.php': 1,
        'matakuliah.php': 2,
        'mata_kuliah.php': 2,
        'mata_kuliah_detail.php': 2,
        'courses.php': 2,
        'tugas.php': 3,
        'assignment.php': 3,
        'assignments.php': 3,
        'nilai.php': 4,
        'grades.php': 4,
        'profile.php': 5,
        'profil.php': 5,
        'account.php': 5
    };
    
    // Set active based on current page
    const activeIndex = pageMapping[currentPage];
    if (activeIndex !== undefined && navLinks[activeIndex]) {
        navLinks[activeIndex].classList.add('active');
    }
    
    // If no match found, try to match by href
    if (activeIndex === undefined) {
        navLinks.forEach(link => {
            const href = link.getAttribute('href');
            if (href && (href === currentPage || href.includes(currentPage))) {
                link.classList.add('active');
            }
        });
    }
}

function addNavigationEffects() {
    const navLinks = document.querySelectorAll('.nav-link');
    
    navLinks.forEach(link => {
        // Add click effects
        link.addEventListener('click', function(e) {
            // Remove active from all links
            navLinks.forEach(l => l.classList.remove('active'));
            
            // Add active to clicked link
            this.classList.add('active');
            
            // Add loading effect
            showLoadingEffect();
            
            // Add ripple effect
            createRippleEffect(e, this);
        });
        
        // Add hover effects
        link.addEventListener('mouseenter', function() {
            if (!this.classList.contains('active')) {
                this.style.transform = 'translateX(8px)';
            }
        });
        
        link.addEventListener('mouseleave', function() {
            if (!this.classList.contains('active')) {
                this.style.transform = 'translateX(0)';
            }
        });
    });
}

function addLoadingStates() {
    const navLinks = document.querySelectorAll('.nav-link');
    
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const icon = this.querySelector('i');
            const originalClass = icon.className;
            
            // Add loading spinner
            icon.className = 'fas fa-spinner fa-spin';
            
            // Restore original icon after delay
            setTimeout(() => {
                icon.className = originalClass;
            }, 1000);
        });
    });
}

function createRippleEffect(event, element) {
    const ripple = document.createElement('span');
    const rect = element.getBoundingClientRect();
    const size = Math.max(rect.width, rect.height);
    const x = event.clientX - rect.left - size / 2;
    const y = event.clientY - rect.top - size / 2;
    
    ripple.style.width = ripple.style.height = size + 'px';
    ripple.style.left = x + 'px';
    ripple.style.top = y + 'px';
    ripple.classList.add('ripple');
    
    element.appendChild(ripple);
    
    setTimeout(() => {
        ripple.remove();
    }, 600);
}

/* ============= USER DROPDOWN FUNCTIONS ============= */
function initializeUserDropdown() {
    const userAvatar = document.querySelector('.user-avatar');
    const dropdown = document.getElementById('userDropdown');
    
    if (userAvatar && dropdown) {
        userAvatar.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleDropdown();
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            if (!userAvatar.contains(event.target) && !dropdown.contains(event.target)) {
                dropdown.classList.remove('show');
            }
        });
        
        // Add keyboard support
        userAvatar.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                toggleDropdown();
            }
        });
    }
}

function toggleDropdown() {
    const dropdown = document.getElementById('userDropdown');
    if (dropdown) {
        const isShown = dropdown.classList.contains('show');
        
        if (isShown) {
            dropdown.classList.remove('show');
        } else {
            dropdown.classList.add('show');
            // Focus first item for keyboard navigation
            const firstItem = dropdown.querySelector('.dropdown-item');
            if (firstItem) {
                firstItem.focus();
            }
        }
    }
}

/* ============= SEARCH FUNCTIONS ============= */
function initializeSearch() {
    const searchInput = document.querySelector('.search-input');
    const searchBox = document.querySelector('.search-box');
    
    if (searchInput) {
        // Enhanced search functionality
        searchInput.addEventListener('input', function() {
            const query = this.value.trim();
            clearTimeout(searchTimeout);
            
            if (query.length > 2) {
                searchTimeout = setTimeout(() => {
                    showSearchSuggestions(query);
                }, 300);
            } else {
                hideSearchSuggestions();
            }
        });
        
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                performSearch(this.value.trim());
            } else if (e.key === 'Escape') {
                this.blur();
                hideSearchSuggestions();
            }
        });
        
        // Focus and blur effects
        searchInput.addEventListener('focus', function() {
            searchBox.classList.add('focused');
        });
        
        searchInput.addEventListener('blur', function() {
            setTimeout(() => {
                searchBox.classList.remove('focused');
                hideSearchSuggestions();
            }, 200);
        });
    }
}

function performSearch(query) {
    if (!query) return;
    
    console.log('Performing search for:', query);
    
    // Add search to history
    addSearchToHistory(query);
    
    // Simulate search loading
    showSearchLoading();
    
    // In a real application, you would make an API call here
    setTimeout(() => {
        hideSearchLoading();
        // Redirect to search results or filter current content
        // window.location.href = `search.php?q=${encodeURIComponent(query)}`;
        
        // For now, just filter current page content
        filterCurrentPageContent(query);
    }, 1000);
}

function showSearchSuggestions(query) {
    const searchBox = document.querySelector('.search-box');
    if (!searchBox) return;
    
    // Remove existing suggestions
    hideSearchSuggestions();
    
    // Create suggestions container
    const suggestions = document.createElement('div');
    suggestions.className = 'search-suggestions';
    suggestions.innerHTML = generateSearchSuggestions(query);
    
    searchBox.appendChild(suggestions);
    
    // Add click handlers to suggestions
    suggestions.addEventListener('click', function(e) {
        const suggestion = e.target.closest('.suggestion-item');
        if (suggestion) {
            const query = suggestion.textContent.trim();
            document.querySelector('.search-input').value = query;
            performSearch(query);
            hideSearchSuggestions();
        }
    });
}

function generateSearchSuggestions(query) {
    // Sample suggestions - in real app, fetch from server
    const suggestions = [
        'Database Design',
        'Web Programming',
        'Data Structures',
        'Computer Networks',
        'Software Engineering'
    ].filter(item => item.toLowerCase().includes(query.toLowerCase()));
    
    if (suggestions.length === 0) {
        return '<div class="no-suggestions">Tidak ada saran</div>';
    }
    
    return suggestions.map(item => 
        `<div class="suggestion-item">
            <i class="fas fa-search"></i>
            <span>${item}</span>
        </div>`
    ).join('');
}

function hideSearchSuggestions() {
    const suggestions = document.querySelector('.search-suggestions');
    if (suggestions) {
        suggestions.remove();
    }
}

function showSearchLoading() {
    const searchIcon = document.querySelector('.search-icon');
    if (searchIcon) {
        searchIcon.className = 'fas fa-spinner fa-spin search-icon';
    }
}

function hideSearchLoading() {
    const searchIcon = document.querySelector('.search-icon');
    if (searchIcon) {
        searchIcon.className = 'fas fa-search search-icon';
    }
}

function addSearchToHistory(query) {
    let history = JSON.parse(localStorage.getItem('searchHistory') || '[]');
    history = [query, ...history.filter(item => item !== query)].slice(0, 10);
    localStorage.setItem('searchHistory', JSON.stringify(history));
}

function filterCurrentPageContent(query) {
    const contentElements = document.querySelectorAll('.course-card, .tugas-item, .assignment-item');
    let visibleCount = 0;
    
    contentElements.forEach(element => {
        const text = element.textContent.toLowerCase();
        const matches = text.includes(query.toLowerCase());
        
        if (matches) {
            element.style.display = 'block';
            element.classList.add('search-highlight');
            visibleCount++;
        } else {
            element.style.display = 'none';
            element.classList.remove('search-highlight');
        }
    });
    
    // Show search results count
    showSearchResultsCount(visibleCount, query);
}

function showSearchResultsCount(count, query) {
    // Remove existing result info
    const existingInfo = document.querySelector('.search-results-info');
    if (existingInfo) {
        existingInfo.remove();
    }
    
    // Create result info
    const resultInfo = document.createElement('div');
    resultInfo.className = 'search-results-info';
    resultInfo.innerHTML = `
        <div class="alert alert-info">
            <i class="fas fa-search"></i>
            Menampilkan ${count} hasil untuk "<strong>${query}</strong>"
            <button class="btn-clear-search" onclick="clearSearch()">
                <i class="fas fa-times"></i> Hapus Filter
            </button>
        </div>
    `;
    
    // Insert after header
    const contentArea = document.querySelector('.content-area');
    if (contentArea) {
        contentArea.insertBefore(resultInfo, contentArea.firstChild);
    }
}

function clearSearch() {
    // Clear search input
    const searchInput = document.querySelector('.search-input');
    if (searchInput) {
        searchInput.value = '';
    }
    
    // Show all elements
    const contentElements = document.querySelectorAll('.course-card, .tugas-item, .assignment-item');
    contentElements.forEach(element => {
        element.style.display = 'block';
        element.classList.remove('search-highlight');
    });
    
    // Remove search results info
    const resultInfo = document.querySelector('.search-results-info');
    if (resultInfo) {
        resultInfo.remove();
    }
}

/* ============= MOBILE MENU FUNCTIONS ============= */
function initializeMobileMenu() {
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const sidebar = document.getElementById('sidebar');
    
    if (mobileMenuBtn && sidebar) {
        mobileMenuBtn.addEventListener('click', function() {
            toggleSidebar();
        });
    }
}

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    
    if (sidebar) {
        const isActive = sidebar.classList.contains('active');
        
        if (isActive) {
            sidebar.classList.remove('active');
            removeSidebarOverlay();
        } else {
            sidebar.classList.add('active');
            createSidebarOverlay();
        }
    }
}

function createSidebarOverlay() {
    // Remove existing overlay
    removeSidebarOverlay();
    
    const overlay = document.createElement('div');
    overlay.id = 'sidebar-overlay';
    overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 999;
        opacity: 0;
        transition: opacity 0.3s ease;
    `;
    
    document.body.appendChild(overlay);
    
    // Fade in overlay
    setTimeout(() => {
        overlay.style.opacity = '1';
    }, 10);
    
    // Close sidebar when overlay is clicked
    overlay.addEventListener('click', function() {
        toggleSidebar();
    });
}

function removeSidebarOverlay() {
    const overlay = document.getElementById('sidebar-overlay');
    if (overlay) {
        overlay.style.opacity = '0';
        setTimeout(() => {
            overlay.remove();
        }, 300);
    }
}

/* ============= ANIMATION FUNCTIONS ============= */
function initializeAnimations() {
    // Fade in animations
    initializeFadeInAnimations();
    
    // Loading animations
    initializeLoadingAnimations();
    
    // Hover animations
    initializeHoverAnimations();
}

function initializeFadeInAnimations() {
    const animatedElements = document.querySelectorAll(
        '.stat-card, .course-card, .section, .tugas-item, .assignment-item, .activity-item'
    );
    
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry, index) => {
            if (entry.isIntersecting) {
                setTimeout(() => {
                    entry.target.classList.add('fade-in', 'visible');
                }, index * 100);
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    animatedElements.forEach(element => {
        element.classList.add('fade-in');
        observer.observe(element);
    });
}

function initializeLoadingAnimations() {
    const cards = document.querySelectorAll('.stat-card, .course-card');
    
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 150);
    });
}

function initializeHoverAnimations() {
    const interactiveElements = document.querySelectorAll(
        '.stat-card, .course-card, .btn, .action-card'
    );
    
    interactiveElements.forEach(element => {
        element.addEventListener('mouseenter', function() {
            this.style.transition = 'transform 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
        });
    });
}

/* ============= NOTIFICATION FUNCTIONS ============= */
function initializeNotifications() {

    const notificationBtn = document.querySelector('.notifications');
    if (notificationBtn) {
        notificationBtn.addEventListener('click', handleNotificationClick);
    }
    
}

function handleNotificationClick() {
    console.log('Notifications clicked');
    showNotificationPanel();
}


function showNotificationPanel() {
    let panel = document.getElementById('notification-panel');

    if (!panel) {
        panel = document.createElement('div');
        panel.id = 'notification-panel';
        panel.className = 'notification-panel';
        document.body.appendChild(panel);
    }
    
    // Tampilkan panel dengan status loading
    panel.classList.toggle('show');
    panel.innerHTML = '<div class="notification-item">Memuat notifikasi...</div>';

    if (panel.classList.contains('show')) {
        // Fetch data notifikasi dari server
        fetch('get_notifications.php')
            .then(response => response.json())
            .then(data => {
                panel.innerHTML = generateNotificationPanelHTML(data);
            })
            .catch(error => {
                console.error('Error fetching notifications:', error);
                panel.innerHTML = '<div class="notification-item">Gagal memuat notifikasi.</div>';
            });
    }

}

function generateNotificationPanelHTML(notifications) {
    let notificationItems = '';

    if (notifications.length === 0) {
        notificationItems = '<div class="notification-item">Tidak ada notifikasi baru.</div>';
    } else {
        notificationItems = notifications.map(notif => `
            <div class="notification-item ${notif.unread ? 'unread' : ''}">
                <div class="notification-icon ${notif.type}">
                    <i class="fas fa-${getNotificationIcon(notif.type)}"></i>
                </div>
                <div class="notification-content">
                    <div class="notification-title">${notif.title}</div>
                    <div class="notification-message">${notif.message}</div>
                    <div class="notification-time">${notif.time}</div>
                </div>
            </div>
        `).join('');
    }
    
    return `
        <div class="notification-header">
            <h3>Notifikasi</h3>
            <button class="mark-all-read" onclick="markAllNotificationsRead()">
                <i class="fas fa-check-double"></i> Tandai Semua Dibaca
            </button>
        </div>
        <div class="notification-list">
            ${notificationItems}
        </div>
        <div class="notification-footer">
            <a href="#">Lihat Semua Notifikasi</a>
        </div>
    `;
}

function getNotificationIcon(type) {
    const icons = {
        assignment: 'tasks',
        grade: 'star',
        announcement: 'bullhorn',
        system: 'cog',
        default: 'bell'
    };
    return icons[type] || icons.default;
}

function updateNotificationBadge() {
    fetch('get_notification_count.php')
        .then(response => response.json())
        .then(data => {
            const count = data.count;
            const badge = document.querySelector('.notification-badge');
            if (badge) {
                badge.textContent = count;
                badge.style.display = count > 0 ? 'flex' : 'none';
            }
            notificationCount = count;
        })
        .catch(error => console.error('Error fetching notification count:', error));
}


function markAllNotificationsRead() {
    fetch('mark_notifications_read.php', { method: 'POST' })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateNotificationBadge(0);
                const unreadItems = document.querySelectorAll('.notification-item.unread');
                unreadItems.forEach(item => {
                    item.classList.remove('unread');
                });
                showToastNotification('Berhasil', 'Semua notifikasi telah ditandai dibaca', 'success');
            } else {
                showToastNotification('Gagal', 'Tidak dapat menandai notifikasi.', 'error');
            }
        })
        .catch(error => console.error('Error marking notifications as read:', error));
}

/* ============= UTILITY FUNCTIONS ============= */
function initializeTimeUpdates() {
    updateTimeDisplay();
    setInterval(updateTimeDisplay, 60000); // Update every minute
}

function updateTimeDisplay() {
    const timeElements = document.querySelectorAll('#current-time, .current-time-display');
    const now = new Date();
    const timeString = now.toLocaleTimeString('id-ID', { 
        hour: '2-digit', 
        minute: '2-digit',
        hour12: false 
    });
    
    timeElements.forEach(element => {
        if (element) {
            element.textContent = timeString;
        }
    });
}

function initializeKeyboardNavigation() {
    document.addEventListener('keydown', function(event) {
        // ESC key functionality
        if (event.key === 'Escape') {
            // Close sidebar
            const sidebar = document.getElementById('sidebar');
            if (sidebar && sidebar.classList.contains('active')) {
                toggleSidebar();
                return;
            }
            
            // Close dropdown
            const dropdown = document.getElementById('userDropdown');
            if (dropdown && dropdown.classList.contains('show')) {
                dropdown.classList.remove('show');
                return;
            }
            
            // Close notification panel
            const notificationPanel = document.getElementById('notification-panel');
            if (notificationPanel && notificationPanel.classList.contains('show')) {
                notificationPanel.classList.remove('show');
                return;
            }
            
            // Clear search
            const searchInput = document.querySelector('.search-input');
            if (searchInput && searchInput === document.activeElement) {
                searchInput.blur();
                clearSearch();
            }
        }
        
        // Ctrl/Cmd + K for search
        if ((event.ctrlKey || event.metaKey) && event.key === 'k') {
            event.preventDefault();
            const searchInput = document.querySelector('.search-input');
            if (searchInput) {
                searchInput.focus();
                searchInput.select();
            }
        }
    });
}

function autoHideAlerts() {
    const alerts = document.querySelectorAll('.alert:not(.permanent)');
    
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            alert.style.transition = 'all 0.5s ease';
            
            setTimeout(() => {
                alert.remove();
            }, 500);
        }, 5000);
    });
}

function initializeTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', showTooltip);
        element.addEventListener('mouseleave', hideTooltip);
    });
}

function showTooltip(event) {
    const element = event.target;
    const tooltipText = element.getAttribute('data-tooltip');
    
    if (!tooltipText) return;
    
    const tooltip = document.createElement('div');
    tooltip.className = 'tooltip';
    tooltip.textContent = tooltipText;
    document.body.appendChild(tooltip);
    
    const rect = element.getBoundingClientRect();
    tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
    tooltip.style.top = rect.top - tooltip.offsetHeight - 5 + 'px';
    
    element.tooltipElement = tooltip;
}

function hideTooltip(event) {
    const element = event.target;
    if (element.tooltipElement) {
        element.tooltipElement.remove();
        delete element.tooltipElement;
    }
}

function showLoadingEffect() {
    const content = document.querySelector('.main-content');
    if (content) {
        content.style.opacity = '0.8';
        content.style.transition = 'opacity 0.3s ease';
        content.style.pointerEvents = 'none';
        
        setTimeout(() => {
            content.style.opacity = '1';
            content.style.pointerEvents = 'auto';
        }, 800);
    }
}

function showToastNotification(title, message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <div class="toast-content">
            <div class="toast-title">${title}</div>
            <div class="toast-message">${message}</div>
        </div>
        <button class="toast-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    // Create toast container if it doesn't exist
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container';
        document.body.appendChild(container);
    }
    
    container.appendChild(toast);
    
    // Auto remove after 4 seconds
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => {
            toast.remove();
        }, 300);
    }, 4000);
}

/* ============= EXPORT FUNCTIONS FOR GLOBAL USE ============= */
window.toggleSidebar = toggleSidebar;
window.toggleDropdown = toggleDropdown;
window.clearSearch = clearSearch;
window.markAllNotificationsRead = markAllNotificationsRead;

// Additional utility functions for backward compatibility
window.showLoading = showLoadingEffect;
window.updateTime = updateTimeDisplay;
window.showToast = showToastNotification;