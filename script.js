// Smooth scroll for anchor links
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

// Add scroll animation to cards
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
        }
    });
}, observerOptions);

// Observe all cards
document.addEventListener('DOMContentLoaded', () => {
    const cards = document.querySelectorAll('.pricing-card, .feature-card');
    cards.forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(card);
    });
});

// Add hover effect to pricing cards
document.querySelectorAll('.pricing-card').forEach(card => {
    card.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-5px) scale(1.02)';
    });
    
    card.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0) scale(1)';
    });
});

// Checkout functions
function openCheckout(package, price) {
    // Update package info in checkout section
    document.getElementById('packageNameDisplay').textContent = package;
    document.getElementById('packagePriceDisplay').textContent = '€' + price;
    document.getElementById('summaryPackage').textContent = package;
    document.getElementById('summaryPrice').textContent = '€' + price;
    document.getElementById('summaryTotal').textContent = '€' + price;
    
    // Hide pricing section and show checkout section
    const pricingSection = document.getElementById('pricing');
    const checkoutSection = document.getElementById('checkoutSection');
    
    if (pricingSection) pricingSection.style.display = 'none';
    if (checkoutSection) {
        checkoutSection.style.display = 'block';
        checkoutSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
    
    // Update URL without reload
    const url = new URL(window.location);
    url.searchParams.set('package', package);
    url.searchParams.set('price', price);
    window.history.pushState({}, '', url);
}

function closeCheckout() {
    // Show pricing section and hide checkout section
    const pricingSection = document.getElementById('pricing');
    const checkoutSection = document.getElementById('checkoutSection');
    
    if (checkoutSection) checkoutSection.style.display = 'none';
    if (pricingSection) {
        pricingSection.style.display = 'block';
        pricingSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
    
    // Clear URL parameters
    const url = new URL(window.location);
    url.searchParams.delete('package');
    url.searchParams.delete('price');
    window.history.pushState({}, '', url);
}

// Check URL parameters on load
document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const package = urlParams.get('package');
    const price = urlParams.get('price');
    
    if (package && price) {
        openCheckout(package, price);
    }
});

