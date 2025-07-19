/**
 * Professional Blog Platform - Main JavaScript
 * Modern functionality with dark mode and interactive features
 */

class BlogApp {
    constructor() {
        this.init();
    }
    
    init() {
        this.setupDarkMode();
        this.setupEventListeners();
        this.setupAnimations();
        this.setupFormValidation();
        this.setupSearch();
        this.setupNotifications();
    }
    
    /**
     * Dark mode functionality
     */
    setupDarkMode() {
        const darkModeToggle = document.getElementById('darkModeToggle');
        const html = document.documentElement;
        
        // Check for saved preference
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme) {
            html.setAttribute('data-theme', savedTheme);
            this.updateDarkModeIcon(savedTheme === 'dark');
        }
        
        // Toggle dark mode
        if (darkModeToggle) {
            darkModeToggle.addEventListener('click', () => {
                const currentTheme = html.getAttribute('data-theme');
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                
                html.setAttribute('data-theme', newTheme);
                localStorage.setItem('theme', newTheme);
                this.updateDarkModeIcon(newTheme === 'dark');
                
                // Trigger custom event
                document.dispatchEvent(new CustomEvent('themeChanged', { detail: newTheme }));
            });
        }
    }
    
    updateDarkModeIcon(isDark) {
        const icon = document.getElementById('darkModeIcon');
        if (icon) {
            icon.className = isDark ? 'fas fa-sun' : 'fas fa-moon';
        }
    }
    
    /**
     * Event listeners setup
     */
    setupEventListeners() {
        // Mobile menu toggle
        const mobileMenuToggle = document.querySelector('.navbar-toggler');
        const navbarCollapse = document.querySelector('.navbar-collapse');
        
        if (mobileMenuToggle && navbarCollapse) {
            mobileMenuToggle.addEventListener('click', () => {
                navbarCollapse.classList.toggle('show');
            });
        }
        
        // Close mobile menu when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.navbar') && navbarCollapse?.classList.contains('show')) {
                navbarCollapse.classList.remove('show');
            }
        });
        
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
        
        // Lazy loading for images
        this.setupLazyLoading();
        
        // Infinite scroll for posts
        this.setupInfiniteScroll();
    }
    
    /**
     * Lazy loading for images
     */
    setupLazyLoading() {
        const images = document.querySelectorAll('img[data-src]');
        
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                        imageObserver.unobserve(img);
                    }
                });
            });
            
            images.forEach(img => imageObserver.observe(img));
        } else {
            // Fallback for older browsers
            images.forEach(img => {
                img.src = img.dataset.src;
                img.classList.remove('lazy');
            });
        }
    }
    
    /**
     * Infinite scroll functionality
     */
    setupInfiniteScroll() {
        const postsContainer = document.querySelector('.posts-container');
        if (!postsContainer) return;
        
        let page = 1;
        let loading = false;
        let hasMore = true;
        
        const loadMorePosts = async () => {
            if (loading || !hasMore) return;
            
            loading = true;
            this.showLoadingSpinner();
            
            try {
                const response = await fetch(`/api/posts?page=${page + 1}`);
                const data = await response.json();
                
                if (data.posts && data.posts.length > 0) {
                    data.posts.forEach(post => {
                        const postElement = this.createPostElement(post);
                        postsContainer.appendChild(postElement);
                    });
                    page++;
                } else {
                    hasMore = false;
                }
            } catch (error) {
                console.error('Error loading more posts:', error);
            } finally {
                loading = false;
                this.hideLoadingSpinner();
            }
        };
        
        // Intersection Observer for infinite scroll
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && hasMore) {
                    loadMorePosts();
                }
            });
        }, { threshold: 0.1 });
        
        // Observe the last post
        const observeLastPost = () => {
            const posts = postsContainer.querySelectorAll('.post-card');
            if (posts.length > 0) {
                observer.observe(posts[posts.length - 1]);
            }
        };
        
        observeLastPost();
    }
    
    /**
     * Create post element for infinite scroll
     */
    createPostElement(post) {
        const div = document.createElement('div');
        div.className = 'card post-card mb-4 fade-in';
        div.innerHTML = `
            <div class="card-body">
                <h5 class="card-title">
                    <a href="/post.php?id=${post.id}">${this.escapeHtml(post.title)}</a>
                </h5>
                <p class="card-text">${this.escapeHtml(post.excerpt || post.content.substring(0, 150))}...</p>
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        By ${this.escapeHtml(post.username)} on ${this.formatDate(post.created_at)}
                    </small>
                    <span class="badge" style="background-color: var(--pistachio); color: var(--dark);">${this.escapeHtml(post.category_name)}</span>
                </div>
            </div>
        `;
        return div;
    }
    
    /**
     * Setup animations
     */
    setupAnimations() {
        // Animate elements on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('fade-in');
                }
            });
        }, observerOptions);
        
        // Observe all cards and sections
        document.querySelectorAll('.card, .section').forEach(el => {
            observer.observe(el);
        });
        
        // Counter animations
        this.setupCounterAnimations();
    }
    
    /**
     * Counter animations
     */
    setupCounterAnimations() {
        const counters = document.querySelectorAll('.counter');
        
        const animateCounter = (counter) => {
            const target = parseInt(counter.getAttribute('data-target'));
            const duration = 2000; // 2 seconds
            const step = target / (duration / 16); // 60fps
            let current = 0;
            
            const timer = setInterval(() => {
                current += step;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }
                counter.textContent = Math.floor(current);
            }, 16);
        };
        
        const counterObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateCounter(entry.target);
                    counterObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });
        
        counters.forEach(counter => counterObserver.observe(counter));
    }
    
    /**
     * Form validation
     */
    setupFormValidation() {
        const forms = document.querySelectorAll('form[data-validate]');
        
        forms.forEach(form => {
            form.addEventListener('submit', (e) => {
                if (!this.validateForm(form)) {
                    e.preventDefault();
                }
            });
            
            // Real-time validation
            const inputs = form.querySelectorAll('input, textarea, select');
            inputs.forEach(input => {
                input.addEventListener('blur', () => {
                    this.validateField(input);
                });
                
                input.addEventListener('input', () => {
                    this.clearFieldError(input);
                });
            });
        });
    }
    
    validateForm(form) {
        let isValid = true;
        const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
        
        inputs.forEach(input => {
            if (!this.validateField(input)) {
                isValid = false;
            }
        });
        
        return isValid;
    }
    
    validateField(field) {
        const value = field.value.trim();
        let isValid = true;
        let errorMessage = '';
        
        // Required validation
        if (field.hasAttribute('required') && !value) {
            isValid = false;
            errorMessage = 'This field is required.';
        }
        
        // Email validation
        if (field.type === 'email' && value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                isValid = false;
                errorMessage = 'Please enter a valid email address.';
            }
        }
        
        // Password validation
        if (field.type === 'password' && value) {
            if (value.length < 8) {
                isValid = false;
                errorMessage = 'Password must be at least 8 characters long.';
            }
        }
        
        // URL validation
        if (field.type === 'url' && value) {
            try {
                new URL(value);
            } catch {
                isValid = false;
                errorMessage = 'Please enter a valid URL.';
            }
        }
        
        if (!isValid) {
            this.showFieldError(field, errorMessage);
        } else {
            this.clearFieldError(field);
        }
        
        return isValid;
    }
    
    showFieldError(field, message) {
        this.clearFieldError(field);
        
        field.classList.add('is-invalid');
        const errorDiv = document.createElement('div');
        errorDiv.className = 'invalid-feedback';
        errorDiv.textContent = message;
        field.parentNode.appendChild(errorDiv);
    }
    
    clearFieldError(field) {
        field.classList.remove('is-invalid');
        const errorDiv = field.parentNode.querySelector('.invalid-feedback');
        if (errorDiv) {
            errorDiv.remove();
        }
    }
    
    /**
     * Search functionality
     */
    setupSearch() {
        const searchForm = document.querySelector('.search-form');
        const searchInput = document.querySelector('.search-input');
        
        if (searchForm && searchInput) {
            let searchTimeout;
            
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                const query = e.target.value.trim();
                
                if (query.length >= 2) {
                    searchTimeout = setTimeout(() => {
                        this.performSearch(query);
                    }, 300);
                } else if (query.length === 0) {
                    this.clearSearchResults();
                }
            });
            
            searchForm.addEventListener('submit', (e) => {
                e.preventDefault();
                const query = searchInput.value.trim();
                if (query) {
                    this.performSearch(query);
                }
            });
        }
    }
    
    async performSearch(query) {
        try {
            const response = await fetch(`/api/search?q=${encodeURIComponent(query)}`);
            const data = await response.json();
            this.displaySearchResults(data.results, query);
        } catch (error) {
            console.error('Search error:', error);
            this.showNotification('Search failed. Please try again.', 'error');
        }
    }
    
    displaySearchResults(results, query) {
        const resultsContainer = document.querySelector('.search-results');
        if (!resultsContainer) return;
        
        if (results.length === 0) {
            resultsContainer.innerHTML = `
                <div class="alert alert-info">
                    No results found for "${this.escapeHtml(query)}"
                </div>
            `;
            return;
        }
        
        const resultsHtml = results.map(result => `
            <div class="search-result-item">
                <h6><a href="${result.url}">${this.escapeHtml(result.title)}</a></h6>
                <p class="text-muted">${this.escapeHtml(result.excerpt)}</p>
                <small class="text-muted">${this.formatDate(result.created_at)}</small>
            </div>
        `).join('');
        
        resultsContainer.innerHTML = resultsHtml;
        resultsContainer.style.display = 'block';
    }
    
    clearSearchResults() {
        const resultsContainer = document.querySelector('.search-results');
        if (resultsContainer) {
            resultsContainer.style.display = 'none';
            resultsContainer.innerHTML = '';
        }
    }
    
    /**
     * Notification system
     */
    setupNotifications() {
        // Create notification container
        if (!document.querySelector('.notification-container')) {
            const container = document.createElement('div');
            container.className = 'notification-container position-fixed top-0 end-0 p-3';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
        }
    }
    
    showNotification(message, type = 'info', duration = 5000) {
        const container = document.querySelector('.notification-container');
        const notification = document.createElement('div');
        
        notification.className = `alert alert-${type} alert-dismissible fade show`;
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        container.appendChild(notification);
        
        // Auto-remove after duration
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, duration);
    }
    
    /**
     * Loading spinner
     */
    showLoadingSpinner() {
        const spinner = document.createElement('div');
        spinner.className = 'loading-spinner text-center py-4';
        spinner.innerHTML = '<div class="spinner-border" style="color: var(--pistachio);" role="status"><span class="visually-hidden">Loading...</span></div>';
        
        const postsContainer = document.querySelector('.posts-container');
        if (postsContainer) {
            postsContainer.appendChild(spinner);
        }
    }
    
    hideLoadingSpinner() {
        const spinner = document.querySelector('.loading-spinner');
        if (spinner) {
            spinner.remove();
        }
    }
    
    /**
     * Utility functions
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }
    
    /**
     * AJAX helper
     */
    async fetchAPI(url, options = {}) {
        try {
            const response = await fetch(url, {
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...options.headers
                },
                ...options
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return await response.json();
        } catch (error) {
            console.error('API request failed:', error);
            throw error;
        }
    }
    
    /**
     * Debounce function
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    /**
     * Throttle function
     */
    throttle(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }
}

// Initialize the app when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.blogApp = new BlogApp();
});

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = BlogApp;
} 