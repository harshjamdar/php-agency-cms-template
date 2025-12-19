/**
 * CodeFiesta Main Script
 * Author: Harsh Jamdar
 */

document.addEventListener('DOMContentLoaded', () => {
    // --- Particle Background ---
    const initParticles = () => {
        const canvas = document.getElementById('particle-canvas');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        if (!ctx) return;

        let particles = [];
        let animationFrameId;

        class Particle {
            constructor() {
                this.x = Math.random() * canvas.width;
                this.y = Math.random() * canvas.height;
                this.directionX = (Math.random() * 0.5) - 0.25;
                this.directionY = (Math.random() * 0.5) - 0.25;
                this.size = Math.random() * 2;
            }

            update() {
                if (this.x > canvas.width || this.x < 0) this.directionX = -this.directionX;
                if (this.y > canvas.height || this.y < 0) this.directionY = -this.directionY;

                this.x += this.directionX;
                this.y += this.directionY;
            }

            draw() {
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                ctx.fillStyle = 'rgba(139, 92, 246, 0.5)';
                ctx.fill();
            }
        }

        const createParticles = () => {
            particles = [];
            const numberOfParticles = (window.innerWidth * window.innerHeight) / 15000;
            for (let i = 0; i < numberOfParticles; i++) {
                particles.push(new Particle());
            }
        };

        const animate = () => {
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            for (let i = 0; i < particles.length; i++) {
                particles[i].update();
                particles[i].draw();

                for (let j = i; j < particles.length; j++) {
                    const dx = particles[i].x - particles[j].x;
                    const dy = particles[i].y - particles[j].y;
                    const distance = Math.sqrt(dx * dx + dy * dy);

                    if (distance < 100) {
                        ctx.beginPath();
                        ctx.strokeStyle = `rgba(139, 92, 246, ${1 - distance / 100})`;
                        ctx.lineWidth = 0.5;
                        ctx.moveTo(particles[i].x, particles[i].y);
                        ctx.lineTo(particles[j].x, particles[j].y);
                        ctx.stroke();
                    }
                }
            }
            animationFrameId = requestAnimationFrame(animate);
        };

        const resizeCanvas = () => {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
            createParticles();
        };

        window.addEventListener('resize', resizeCanvas);
        resizeCanvas();
        animate();
    };
    initParticles();

    // --- Mobile Menu ---
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');
    const mobileMenuIcon = mobileMenuBtn?.querySelector('svg'); // Assuming SVG icon

    if (mobileMenuBtn && mobileMenu) {
        mobileMenuBtn.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
            // Simple icon toggle logic could be added here if needed
        });
    }

    // --- Cost Estimator ---
    const serviceBtns = document.querySelectorAll('.service-btn');
    const complexityBtns = document.querySelectorAll('.complexity-btn');
    const priceDisplay = document.getElementById('price-display');
    const featuresList = document.getElementById('features-list');
    
    let currentService = 'web';
    let currentComplexity = 'basic';

    const prices = {
        web: {
            basic: "₹15,000 - ₹25,000",
            standard: "₹30,000 - ₹60,000",
            premium: "₹70,000+",
        },
        app: {
            basic: "₹40,000 - ₹70,000",
            standard: "₹80,000 - ₹1,50,000",
            premium: "₹2,00,000+",
        },
        marketing: {
            basic: "₹10,000 / mo",
            standard: "₹25,000 / mo",
            premium: "₹50,000 / mo",
        }
    };

    const getFeatures = (s, c) => {
        if (s === 'web') {
            if (c === 'basic') return ['5 Page Responsive Site', 'Contact Form', 'Basic SEO', '1 Month Support'];
            if (c === 'standard') return ['CMS Integration', 'Blog Setup', 'Advanced SEO', 'Social Media Integration', '3 Months Support'];
            return ['Custom Web App', 'E-commerce', 'Database Integration', 'Payment Gateway', '6 Months Support'];
        }
        if (s === 'app') {
            if (c === 'basic') return ['Android Only', 'Standard UI', 'Basic Features', 'Play Store Submission'];
            if (c === 'standard') return ['Android & iOS (Flutter)', 'Custom UI/UX', 'Push Notifications', 'API Integration'];
            return ['Native Development', 'Complex Backend', 'Real-time Features', 'Advanced Analytics', 'Premium Support'];
        }
        return ['Social Media Basics', 'Content Creation (4 posts)', 'Basic Ad Setup', 'Monthly Report'];
    };

    const updateEstimator = () => {
        // Update Buttons UI
        serviceBtns.forEach(btn => {
            if (btn.dataset.service === currentService) {
                btn.classList.add('bg-primary', 'text-white', 'border-primary', 'shadow-[0_0_15px_rgba(139,92,246,0.3)]');
                btn.classList.remove('bg-slate-800', 'text-gray-300', 'border-slate-700');
            } else {
                btn.classList.remove('bg-primary', 'text-white', 'border-primary', 'shadow-[0_0_15px_rgba(139,92,246,0.3)]');
                btn.classList.add('bg-slate-800', 'text-gray-300', 'border-slate-700');
            }
        });

        complexityBtns.forEach(btn => {
            if (btn.dataset.complexity === currentComplexity) {
                btn.classList.add('bg-secondary', 'text-white', 'border-secondary', 'shadow-[0_0_15px_rgba(6,182,212,0.3)]');
                btn.classList.remove('bg-slate-800', 'text-gray-300', 'border-slate-700');
            } else {
                btn.classList.remove('bg-secondary', 'text-white', 'border-secondary', 'shadow-[0_0_15px_rgba(6,182,212,0.3)]');
                btn.classList.add('bg-slate-800', 'text-gray-300', 'border-slate-700');
            }
        });

        // Update Price
        if (priceDisplay) {
            priceDisplay.textContent = prices[currentService][currentComplexity];
        }

        // Update Features
        if (featuresList) {
            featuresList.innerHTML = '';
            const features = getFeatures(currentService, currentComplexity);
            features.forEach(feature => {
                const li = document.createElement('li');
                li.className = 'flex items-center gap-3 text-gray-300';
                li.innerHTML = `<svg class="h-5 w-5 text-green-400 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg><span>${feature}</span>`;
                featuresList.appendChild(li);
            });
        }

        // Update Contact Form Message if it exists
        const messageInput = document.getElementById('message');
        if (messageInput) {
             const serviceLabels = {
                web: 'Website Development',
                app: 'Mobile App Development',
                marketing: 'Digital Marketing'
            };
            const complexityLabels = {
                basic: 'Basic',
                standard: 'Standard',
                premium: 'Premium'
            };
            messageInput.value = `Hi! I'm interested in ${serviceLabels[currentService]} (${complexityLabels[currentComplexity]} tier, ${prices[currentService][currentComplexity]}). Please contact me to discuss this project.`;
        }
    };

    serviceBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            currentService = btn.dataset.service;
            updateEstimator();
        });
    });

    complexityBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            currentComplexity = btn.dataset.complexity;
            updateEstimator();
        });
    });

    // Initialize Estimator
    if (serviceBtns.length > 0) updateEstimator();


    // --- FAQ Toggle ---
    const faqButtons = document.querySelectorAll('.faq-btn');
    faqButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const content = btn.nextElementSibling;
            const icon = btn.querySelector('svg');
            
            // Toggle current
            content.classList.toggle('hidden');
            icon.classList.toggle('rotate-180');
            icon.classList.toggle('text-secondary');

            // Close others (optional, but good UX)
            faqButtons.forEach(otherBtn => {
                if (otherBtn !== btn) {
                    otherBtn.nextElementSibling.classList.add('hidden');
                    const otherIcon = otherBtn.querySelector('svg');
                    otherIcon.classList.remove('rotate-180');
                    otherIcon.classList.remove('text-secondary');
                }
            });
        });
    });

    // --- Testimonials Slider ---
    const testimonials = document.querySelectorAll('.testimonial-slide');
    let currentTestimonial = 0;
    
    const showTestimonial = (index) => {
        testimonials.forEach((slide, i) => {
            if (i === index) {
                slide.classList.remove('hidden', 'opacity-0', 'translate-x-full', '-translate-x-full');
                slide.classList.add('opacity-100', 'translate-x-0');
            } else {
                slide.classList.add('hidden', 'opacity-0');
                slide.classList.remove('opacity-100', 'translate-x-0');
            }
        });
    };

    const nextTestimonial = () => {
        currentTestimonial = (currentTestimonial + 1) % testimonials.length;
        showTestimonial(currentTestimonial);
    };

    if (testimonials.length > 0) {
        showTestimonial(0);
        setInterval(nextTestimonial, 6000);
    }


    // --- Scroll Animations ---
    const observerOptions = {
        root: null,
        rootMargin: '0px',
        threshold: 0.1
    };

    const observer = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    document.querySelectorAll('.fade-in, .fade-in-up').forEach(el => {
        observer.observe(el);
    });

    // --- Scroll To Top ---
    const scrollToTopBtn = document.getElementById('scroll-to-top');
    if (scrollToTopBtn) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 300) {
                scrollToTopBtn.classList.remove('opacity-0', 'pointer-events-none');
                scrollToTopBtn.classList.add('opacity-100', 'pointer-events-auto');
            } else {
                scrollToTopBtn.classList.add('opacity-0', 'pointer-events-none');
                scrollToTopBtn.classList.remove('opacity-100', 'pointer-events-auto');
            }
        });

        scrollToTopBtn.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    // --- Cookie Consent ---
    const cookieBanner = document.getElementById('cookie-banner');
    const acceptBtn = document.getElementById('cookie-accept');
    const declineBtn = document.getElementById('cookie-decline');

    if (cookieBanner && acceptBtn && declineBtn) {
        // Check if user has already made a choice
        const cookieConsent = localStorage.getItem('cookieConsent');

        if (!cookieConsent) {
            // Show banner after a short delay
            setTimeout(() => {
                cookieBanner.classList.remove('translate-y-full');
            }, 1000);
        }

        acceptBtn.addEventListener('click', () => {
            localStorage.setItem('cookieConsent', 'accepted');
            cookieBanner.classList.add('translate-y-full');
        });

        declineBtn.addEventListener('click', () => {
            localStorage.setItem('cookieConsent', 'declined');
            cookieBanner.classList.add('translate-y-full');
        });
    }

    // --- reCAPTCHA v3 Form Integration ---
    // Add reCAPTCHA token to contact form
    const contactForm = document.querySelector('form[action*="submit-inquiry"]');
    if (contactForm && typeof getRecaptchaToken === 'function') {
        contactForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn ? submitBtn.innerHTML : '';
            
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span>Verifying...</span>';
            }
            
            try {
                const token = await getRecaptchaToken('contact_form');
                
                // Add token to form
                let tokenInput = this.querySelector('input[name="recaptcha_token"]');
                if (!tokenInput) {
                    tokenInput = document.createElement('input');
                    tokenInput.type = 'hidden';
                    tokenInput.name = 'recaptcha_token';
                    this.appendChild(tokenInput);
                }
                tokenInput.value = token;
                
                // Submit form
                this.submit();
            } catch (error) {
                console.error('reCAPTCHA error:', error);
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
                alert('Security verification failed. Please try again.');
            }
        });
    }

    // Add reCAPTCHA token to newsletter form
    const newsletterForms = document.querySelectorAll('form[action*="newsletter-subscribe"], .newsletter-form');
    newsletterForms.forEach(form => {
        if (typeof getRecaptchaToken === 'function') {
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn ? submitBtn.innerHTML : '';
                
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span>Verifying...</span>';
                }
                
                try {
                    const token = await getRecaptchaToken('newsletter_subscribe');
                    
                    const formData = new FormData(this);
                    formData.append('recaptcha_token', token);
                    
                    const response = await fetch(this.action || '/newsletter-subscribe.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        alert(result.message || 'Successfully subscribed!');
                        this.reset();
                    } else {
                        alert(result.message || 'Subscription failed. Please try again.');
                    }
                } catch (error) {
                    console.error('Newsletter subscription error:', error);
                    alert('An error occurred. Please try again.');
                } finally {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                    }
                }
            });
        }
    });
});
