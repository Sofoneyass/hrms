<section class="why-choose-us">
    <div class="container">
        <div class="section-header">
            <h2>Why Choose <span>Jigjiga Homes</span></h2>
            <p>Discover the difference of renting with Ethiopia's most trusted housing platform</p>
        </div>

        <div class="features-grid">
            <!-- Feature 1 -->
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-home"></i>
                </div>
                <h3>Verified Properties</h3>
                <p>Every listing is personally inspected by our team to ensure quality and accurate descriptions.</p>
            </div>

            <!-- Feature 2 -->
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-map-marked-alt"></i>
                </div>
                <h3>Local Expertise</h3>
                <p>Our Jigjiga-based agents know every neighborhood from Karaamarda to Aw-Dale.</p>
            </div>

            <!-- Feature 3 -->
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-hand-holding-usd"></i>
                </div>
                <h3>No Hidden Fees</h3>
                <p>Transparent pricing with no surprise charges. What you see is what you pay.</p>
            </div>

            <!-- Feature 4 -->
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3>Secure Contracts</h3>
                <p>Legally vetted rental agreements that protect both tenants and property owners.</p>
            </div>

            <!-- Feature 5 -->
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-headset"></i>
                </div>
                <h3>24/7 Somali Support</h3>
                <p>Dedicated customer service in Somali and English whenever you need assistance.</p>
            </div>

            <!-- Feature 6 -->
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-star"></i>
                </div>
                <h3>Premium Listings</h3>
                <p>Exclusive access to the best homes in Jigjiga before they hit the market.</p>
            </div>
        </div>
    </div>
</section>

<style>
    /* Why Choose Us Section */
    .why-choose-us {
        padding: 80px 0;
        background-color: #f9f9f9;
        position: relative;
        overflow: hidden;
    }

    body.dark-mode .why-choose-us {
        background-color: #121212;
    }

    .why-choose-us .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }

    .section-header {
        text-align: center;
        margin-bottom: 60px;
    }

    .section-header h2 {
        font-family: 'Playfair Display', serif;
        font-size: 2.5rem;
        color: #1e3c2b;
        margin-bottom: 15px;
    }

    body.dark-mode .section-header h2 {
        color: #f8f9fa;
    }

    .section-header h2 span {
        color: #2a7f62;
    }

    body.dark-mode .section-header h2 span {
        color: #f0c14b;
    }

    .section-header p {
        font-size: 1.1rem;
        color: #555;
        max-width: 700px;
        margin: 0 auto;
    }

    body.dark-mode .section-header p {
        color: #ccc;
    }

    .features-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 30px;
    }

    .feature-card {
        background: white;
        border-radius: 12px;
        padding: 35px 30px;
        text-align: center;
        transition: all 0.3s ease;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        border: 1px solid rgba(0, 0, 0, 0.03);
    }

    body.dark-mode .feature-card {
        background: #1e3c2b;
        border: 1px solid rgba(255, 255, 255, 0.05);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }

    .feature-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 30px rgba(42, 127, 98, 0.15);
    }

    body.dark-mode .feature-card:hover {
        box-shadow: 0 15px 30px rgba(240, 193, 75, 0.1);
    }

    .feature-icon {
        width: 80px;
        height: 80px;
        background: rgba(42, 127, 98, 0.1);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 25px;
        color: #2a7f62;
        font-size: 32px;
        transition: all 0.3s ease;
    }

    body.dark-mode .feature-icon {
        background: rgba(240, 193, 75, 0.1);
        color: #f0c14b;
    }

    .feature-card:hover .feature-icon {
        background: #2a7f62;
        color: white;
        transform: scale(1.1);
    }

    body.dark-mode .feature-card:hover .feature-icon {
        background: #f0c14b;
        color: #1e3c2b;
    }

    .feature-card h3 {
        font-size: 1.3rem;
        margin-bottom: 15px;
        color: #333;
    }

    body.dark-mode .feature-card h3 {
        color: #f8f9fa;
    }

    .feature-card p {
        color: #666;
        line-height: 1.6;
        font-size: 0.95rem;
    }

    body.dark-mode .feature-card p {
        color: #ccc;
    }

    /* Responsive Adjustments */
    @media (max-width: 768px) {
        .why-choose-us {
            padding: 60px 0;
        }
        
        .section-header h2 {
            font-size: 2rem;
        }
        
        .features-grid {
            grid-template-columns: 1fr 1fr;
        }
    }

    @media (max-width: 480px) {
        .features-grid {
            grid-template-columns: 1fr;
        }
        
        .feature-card {
            padding: 25px 20px;
        }
    }
</style>