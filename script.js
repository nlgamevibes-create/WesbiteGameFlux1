// --- Smooth scroll voor interne links ---
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            e.preventDefault();
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// --- Animatie bij scrollen voor kaarten ---
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

document.addEventListener('DOMContentLoaded', () => {
    const cards = document.querySelectorAll('.pricing-card, .feature-card');
    cards.forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(card);
    });
});

// --- Hover-effect op pricing cards ---
document.querySelectorAll('.pricing-card').forEach(card => {
    card.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-5px) scale(1.02)';
    });

    card.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0) scale(1)';
    });
});

// --- Stripe Payment Links (van laag naar hoog) ---
const paymentLinks = {
    "FXServer I": "https://buy.stripe.com/7sYaEY9ObaD8bXX6eZ53O02",
    "FXServer II": "https://buy.stripe.com/28EaEYgcz7qW2nn7j353O03",
    "FXServer III": "https://buy.stripe.com/cNifZi9ObeTo3rrdHr53O04",
    "FXServer IV": "https://buy.stripe.com/9B6aEYf8v12y7HH1YJ53O05",
    "FXServer V": "https://buy.stripe.com/eVqcN6bWjfXs6DD0UF53O06",
    "EUP Key": "https://buy.stripe.com/9B628s4tR9z40ffcDn53O07"
};

// --- Dynamische Stripe link handler ---
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.card-button').forEach(button => {
        button.addEventListener('click', (e) => {
            const card = e.target.closest('.pricing-card');
            if (!card) return;

            const packageName = card.querySelector('.package-name')?.textContent?.trim();
            const link = paymentLinks[packageName];

            if (link) {
                window.open(link, '_blank');
            } else {
                alert('Stripe-link voor ' + packageName + ' is nog niet ingesteld.');
            }
        });
    });
});
