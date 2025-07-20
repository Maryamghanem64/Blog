/**
 * Professional Blog Platform - Enhanced JavaScript with Animations
 * Modern, responsive animations and interactions
 */

class BlogAnimations {
    constructor() {
        this.init();
    }

    init() {
        this.setupAnimations();
        this.setupScrollAnimations();
        this.setupHoverEffects();
        this.setupFormAnimations();
        this.setupLoadingAnimations();
        this.setupTypingEffect();
        this.setupParallaxEffects();
        this.setupStaggeredAnimations();
    }

    // Setup initial page load animations
    setupAnimations() {
        // Animate elements on page load
        const animatedElements = document.querySelectorAll('.animate-fade-in-up, .animate-slide-in-left, .animate-slide-in-right, .animate-scale-in');
        
        animatedElements.forEach((element, index) => {
            element.style.opacity = '0';
            element.style.transform = 'translateY(30px)';
            
            setTimeout(() => {
                element.style.transition = 'all 0.8s cubic-bezier(0.4, 0, 0.2, 1)';
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
            }, index * 100);
        });

        // Animate cards with stagger effect
        const cards = document.querySelectorAll('.post-card, .category-card, .stat-card');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(50px) scale(0.9)';
            
            setTimeout(() => {
                card.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0) scale(1)';
            }, index * 150);
        });
    }

    // Setup scroll-triggered animations
    setupScrollAnimations() {
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-in');
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        // Observe elements for scroll animations
        const scrollElements = document.querySelectorAll('.post-card, .category-card, .stat-card, .comment, .alert');
        scrollElements.forEach(el => {
            el.classList.add('scroll-animate');
            observer.observe(el);
        });
    }

    // Setup enhanced hover effects
    setupHoverEffects() {
        // Enhanced card hover effects
        const cards = document.querySelectorAll('.post-card, .category-card, .stat-card');
        cards.forEach(card => {
            card.addEventListener('mouseenter', (e) => {
                this.addHoverEffect(e.target);
            });
            
            card.addEventListener('mouseleave', (e) => {
                this.removeHoverEffect(e.target);
            });
        });

        // Button hover effects
        const buttons = document.querySelectorAll('.btn, .btn-login, .btn-register, .btn-create-post');
        buttons.forEach(button => {
            button.addEventListener('mouseenter', (e) => {
                this.addButtonHoverEffect(e.target);
            });
            
            button.addEventListener('mouseleave', (e) => {
                this.removeButtonHoverEffect(e.target);
            });
        });

        // Link hover effects
        const links = document.querySelectorAll('a:not(.btn)');
        links.forEach(link => {
            link.addEventListener('mouseenter', (e) => {
                this.addLinkHoverEffect(e.target);
            });
            
            link.addEventListener('mouseleave', (e) => {
                this.removeLinkHoverEffect(e.target);
            });
        });
    }

    // Add hover effect to cards
    addHoverEffect(element) {
        element.style.transform = 'translateY(-10px) scale(1.02)';
        element.style.boxShadow = '0 20px 40px rgba(167, 201, 87, 0.3)';
        element.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
    }

    // Remove hover effect from cards
    removeHoverEffect(element) {
        element.style.transform = 'translateY(0) scale(1)';
        element.style.boxShadow = '0 10px 25px rgba(0,0,0,0.1)';
    }

    // Add button hover effect
    addButtonHoverEffect(element) {
        element.style.transform = 'translateY(-3px) scale(1.05)';
        element.style.boxShadow = '0 10px 25px rgba(167, 201, 87, 0.4)';
        element.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
    }

    // Remove button hover effect
    removeButtonHoverEffect(element) {
        element.style.transform = 'translateY(0) scale(1)';
        element.style.boxShadow = 'none';
    }

    // Add link hover effect
    addLinkHoverEffect(element) {
        element.style.transform = 'translateY(-2px)';
        element.style.color = '#8fb84a';
        element.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
    }

    // Remove link hover effect
    removeLinkHoverEffect(element) {
        element.style.transform = 'translateY(0)';
        element.style.color = '#a7c957';
    }

    // Setup form animations
    setupFormAnimations() {
        const formInputs = document.querySelectorAll('input, textarea, select');
        
        formInputs.forEach(input => {
            // Focus animation
            input.addEventListener('focus', (e) => {
                this.addInputFocusEffect(e.target);
            });
            
            // Blur animation
            input.addEventListener('blur', (e) => {
                this.removeInputFocusEffect(e.target);
            });
            
            // Input animation
            input.addEventListener('input', (e) => {
                this.addInputAnimation(e.target);
            });
        });
    }

    // Add input focus effect
    addInputFocusEffect(element) {
        element.style.transform = 'scale(1.02)';
        element.style.boxShadow = '0 0 0 3px rgba(167, 201, 87, 0.3)';
        element.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
    }

    // Remove input focus effect
    removeInputFocusEffect(element) {
        element.style.transform = 'scale(1)';
        element.style.boxShadow = 'none';
    }

    // Add input animation
    addInputAnimation(element) {
        element.style.transform = 'scale(1.01)';
        setTimeout(() => {
            element.style.transform = 'scale(1)';
        }, 100);
    }

    // Setup loading animations
    setupLoadingAnimations() {
        // Page loading animation
        window.addEventListener('load', () => {
            this.hideLoadingScreen();
        });

        // Show loading screen
        this.showLoadingScreen();
    }

    // Show loading screen
    showLoadingScreen() {
        const loader = document.createElement('div');
        loader.id = 'page-loader';
        loader.innerHTML = `
            <div class="loader-content">
                <div class="loader-spinner"></div>
                <div class="loader-text">Loading...</div>
            </div>
        `;
        loader.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #a7c957 0%, #8fb84a 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            transition: opacity 0.5s ease;
        `;
        
        const loaderContent = loader.querySelector('.loader-content');
        loaderContent.style.cssText = `
            text-align: center;
            color: white;
        `;
        
        const spinner = loader.querySelector('.loader-spinner');
        spinner.style.cssText = `
            width: 50px;
            height: 50px;
            border: 4px solid rgba(255,255,255,0.3);
            border-top: 4px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        `;
        
        const text = loader.querySelector('.loader-text');
        text.style.cssText = `
            font-size: 18px;
            font-weight: 600;
        `;
        
        document.body.appendChild(loader);
    }

    // Hide loading screen
    hideLoadingScreen() {
        const loader = document.getElementById('page-loader');
        if (loader) {
            loader.style.opacity = '0';
            setTimeout(() => {
                loader.remove();
            }, 500);
        }
    }

    // Setup typing effect for headers
    setupTypingEffect() {
        const headers = document.querySelectorAll('h1, h2');
        headers.forEach(header => {
            if (header.textContent.length > 10) {
                this.addTypingEffect(header);
            }
        });
    }

    // Add typing effect to element
    addTypingEffect(element) {
        const text = element.textContent;
        element.textContent = '';
        element.style.borderRight = '2px solid #a7c957';
        
        let i = 0;
        const typeWriter = () => {
            if (i < text.length) {
                element.textContent += text.charAt(i);
                i++;
                setTimeout(typeWriter, 100);
            } else {
                element.style.borderRight = 'none';
            }
        };
        
        setTimeout(typeWriter, 1000);
    }

    // Setup parallax effects
    setupParallaxEffects() {
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            const parallaxElements = document.querySelectorAll('.parallax');
            
            parallaxElements.forEach(element => {
                const speed = element.dataset.speed || 0.5;
                element.style.transform = `translateY(${scrolled * speed}px)`;
            });
        });
    }

    // Setup staggered animations
    setupStaggeredAnimations() {
        const staggeredElements = document.querySelectorAll('.stagger-animate');
        
        staggeredElements.forEach((element, index) => {
            element.style.opacity = '0';
            element.style.transform = 'translateY(30px)';
            
            setTimeout(() => {
                element.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
            }, index * 200);
        });
    }

    // Utility function to add ripple effect
    addRippleEffect(event) {
        const button = event.currentTarget;
        const ripple = document.createElement('span');
        const rect = button.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = event.clientX - rect.left - size / 2;
        const y = event.clientY - rect.top - size / 2;
        
        ripple.style.cssText = `
            position: absolute;
            width: ${size}px;
            height: ${size}px;
            left: ${x}px;
            top: ${y}px;
            background: rgba(167, 201, 87, 0.3);
            border-radius: 50%;
            transform: scale(0);
            animation: ripple 0.6s linear;
            pointer-events: none;
        `;
        
        button.appendChild(ripple);
        
        setTimeout(() => {
            ripple.remove();
        }, 600);
    }

    // Utility function to add shake effect
    addShakeEffect(element) {
        element.style.animation = 'shake 0.5s ease-in-out';
        setTimeout(() => {
            element.style.animation = '';
        }, 500);
    }

    // Utility function to add pulse effect
    addPulseEffect(element) {
        element.style.animation = 'pulse 0.5s ease-in-out';
        setTimeout(() => {
            element.style.animation = '';
        }, 500);
    }
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    @keyframes ripple {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
    
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
        20%, 40%, 60%, 80% { transform: translateX(5px); }
    }
    
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }
    
    .scroll-animate {
        opacity: 0;
        transform: translateY(30px);
        transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .scroll-animate.animate-in {
        opacity: 1;
        transform: translateY(0);
    }
    
    .stagger-animate {
        opacity: 0;
        transform: translateY(30px);
    }
    
    .parallax {
        transition: transform 0.1s ease-out;
    }
    
    /* Enhanced button styles */
    .btn, .btn-login, .btn-register, .btn-create-post {
        position: relative;
        overflow: hidden;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    /* Enhanced card styles */
    .post-card, .category-card, .stat-card {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: pointer;
    }
    
    /* Enhanced form styles */
    input, textarea, select {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    /* Enhanced link styles */
    a:not(.btn) {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
`;

document.head.appendChild(style);

// Initialize animations when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new BlogAnimations();
    
    // Add ripple effect to buttons
    const buttons = document.querySelectorAll('.btn, .btn-login, .btn-register, .btn-create-post');
    buttons.forEach(button => {
        button.addEventListener('click', (e) => {
            const animations = new BlogAnimations();
            animations.addRippleEffect(e);
        });
    });
    
    // Add shake effect to form validation errors
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', (e) => {
            const animations = new BlogAnimations();
            const inputs = form.querySelectorAll('input[required], textarea[required]');
            inputs.forEach(input => {
                if (!input.value.trim()) {
                    animations.addShakeEffect(input);
                }
            });
        });
    });
    
    // Add pulse effect to success messages
    const alerts = document.querySelectorAll('.alert-success');
    alerts.forEach(alert => {
        const animations = new BlogAnimations();
        animations.addPulseEffect(alert);
    });
});

// Export for use in other scripts
window.BlogAnimations = BlogAnimations; 