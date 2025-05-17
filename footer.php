<footer class="jigjiga-footer">
 <style>
 
.jigjiga-footer {
  background: linear-gradient(135deg, #1e3c2b 0%, #2a7f62 100%);
  color: #fff;
  position: relative;
  padding-top: 80px;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.footer-wave {
  position: absolute;
  top: -1px;
  left: 0;
  width: 100%;
  overflow: hidden;
  line-height: 0;
}

.footer-wave svg {
  width: calc(100% + 1.3px);
  height: 80px;
  display: block;
}

.footer-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px;
}

/* Premium Contact Card */
.footer-contact-card {
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
  border-radius: 12px;
  padding: 30px;
  margin-bottom: 40px;
  border: 1px solid rgba(255, 255, 255, 0.15);
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
}

.footer-contact-card h3 {
  font-size: 28px;
  margin-bottom: 25px;
  font-weight: 700;
}

.footer-contact-card h3 span {
  color: #f0c14b;
}

.contact-methods {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-bottom: 25px;
}

.contact-method {
  display: flex;
  align-items: center;
  gap: 15px;
}

.contact-method i {
  font-size: 24px;
  color: #f0c14b;
  width: 50px;
  height: 50px;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.contact-method span {
  display: block;
  font-size: 14px;
  color: rgba(255, 255, 255, 0.7);
  margin-bottom: 5px;
}

.contact-method p {
  margin: 0;
  font-size: 16px;
  font-weight: 500;
}

.footer-cta {
  background: #f0c14b;
  color: #1e3c2b;
  border: none;
  padding: 12px 30px;
  font-size: 16px;
  font-weight: 600;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.footer-cta:hover {
  background: #e2b33a;
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(240, 193, 75, 0.3);
}

/* Footer Navigation */
.footer-nav {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 40px;
  margin-bottom: 50px;
}

.footer-col h4 {
  font-size: 18px;
  margin-bottom: 20px;
  position: relative;
  padding-bottom: 10px;
}

.footer-col h4::after {
  content: '';
  position: absolute;
  left: 0;
  bottom: 0;
  width: 40px;
  height: 2px;
  background: #f0c14b;
}

.footer-col ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.footer-col ul li {
  margin-bottom: 12px;
}

.footer-col ul li a {
  color: rgba(255, 255, 255, 0.8);
  text-decoration: none;
  transition: all 0.3s ease;
  font-size: 15px;
}

.footer-col ul li a:hover {
  color: #f0c14b;
  padding-left: 5px;
}

/* Social & Newsletter */
.footer-social .social-links {
  display: flex;
  gap: 15px;
  margin-bottom: 25px;
}

.footer-social .social-links a {
  color: #fff;
  background: rgba(255, 255, 255, 0.1);
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
}

.footer-social .social-links a:hover {
  background: #f0c14b;
  color: #1e3c2b;
  transform: translateY(-3px);
}

.newsletter form {
  display: flex;
  margin-top: 15px;
}

.newsletter input {
  flex: 1;
  padding: 12px 15px;
  border: none;
  border-radius: 4px 0 0 4px;
  background: rgba(255, 255, 255, 0.9);
}

.newsletter button {
  background: #f0c14b;
  color: #1e3c2b;
  border: none;
  padding: 0 18px;
  border-radius: 0 4px 4px 0;
  cursor: pointer;
  transition: background 0.3s ease;
}

.newsletter button:hover {
  background: #e2b33a;
}

/* Footer Bottom */
.footer-bottom {
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  padding: 25px 0;
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  align-items: center;
}

.footer-logo {
  display: flex;
  align-items: center;
  gap: 15px;
}

.footer-logo img {
  height: 40px;
}

.footer-logo span {
  font-size: 14px;
  color: rgba(255, 255, 255, 0.7);
}

.legal-links {
  display: flex;
  gap: 20px;
}

.legal-links a {
  color: rgba(255, 255, 255, 0.7);
  text-decoration: none;
  font-size: 14px;
  transition: color 0.3s ease;
}

.legal-links a:hover {
  color: #f0c14b;
}

.copyright {
  font-size: 14px;
  color: rgba(255, 255, 255, 0.7);
  width: 100%;
  text-align: center;
  margin-top: 15px;
}

.copyright strong {
  color: #f0c14b;
  font-weight: 600;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
  .footer-contact-card {
    text-align: center;
  }
  
  .contact-methods {
    grid-template-columns: 1fr;
  }
  
  .footer-nav {
    grid-template-columns: 1fr 1fr;
  }
  
  .footer-bottom {
    flex-direction: column;
    gap: 15px;
    text-align: center;
  }
  
  .legal-links {
    justify-content: center;
  }
}

@media (max-width: 480px) {
  .footer-nav {
    grid-template-columns: 1fr;
  }
  
  .newsletter form {
    flex-direction: column;
  }
  
  .newsletter input,
  .newsletter button {
    width: 100%;
    border-radius: 4px;
  }
  
  .newsletter button {
    margin-top: 10px;
    padding: 12px;
  }
}
 </style>
  <div class="footer-wave">
    <svg viewBox="0 0 1200 120" preserveAspectRatio="none">
      <path d="M0,0V46.29c47.79,22.2,103.59,32.17,158,28,70.36-5.37,136.33-33.31,206.8-37.5C438.64,32.43,512.34,53.67,583,72.05c69.27,18,138.3,24.88,209.4,13.08,36.15-6,69.85-17.84,104.45-29.34C989.49,25,1113-14.29,1200,52.47V0Z" opacity=".25" fill="#2a7f62"></path>
      <path d="M0,0V15.81C13,36.92,27.64,56.86,47.69,72.05,99.41,111.27,165,111,224.58,91.58c31.15-10.15,60.09-26.07,89.67-39.8,40.92-19,84.73-46,130.83-49.67,36.26-2.85,70.9,9.42,98.6,31.56,31.77,25.39,62.32,62,103.63,73,40.44,10.79,81.35-6.69,119.13-24.28s75.16-39,116.92-43.05c59.73-5.85,113.28,22.88,168.9,38.84,30.2,8.66,59,6.17,87.09-7.5,22.43-10.89,48-26.93,60.65-49.24V0Z" opacity=".5" fill="#2a7f62"></path>
      <path d="M0,0V5.63C149.93,59,314.09,71.32,475.83,42.57c43-7.64,84.23-20.12,127.61-26.46,59-8.63,112.48,12.24,165.56,35.4C827.93,77.22,886,95.24,951.2,90c86.53-7,172.46-45.71,248.8-84.81V0Z" fill="#2a7f62"></path>
    </svg>
  </div>

  <!-- Main Footer Content -->
  <div class="footer-container">
    <!-- Premium Contact Card -->
    <div class="footer-contact-card">
      <h3>Find Your Dream Home in <span>Jigjiga</span></h3>
      <div class="contact-methods">
        <div class="contact-method">
          <i class="fas fa-phone-alt"></i>
          <div>
            <span>Call Us</span>
            <p>+2519123456</p>
          </div>
        </div>
        <div class="contact-method">
          <i class="fas fa-envelope"></i>
          <div>
            <span>Email Us</span>
            <p>rental@jigjigahomes.com</p>
          </div>
        </div>
        <div class="contact-method">
          <i class="fas fa-map-marker-alt"></i>
          <div>
            <span>Visit Our Office</span>
            <p>Garaad Wiil-Waal Ave, Jigjiga</p>
          </div>
        </div>
      </div>
      <button class="footer-cta">Book a Viewing</button>
    </div>

    <!-- Footer Navigation -->
    <div class="footer-nav">
      <!-- Quick Links -->
      <div class="footer-col">
        <h4>Quick Links</h4>
        <ul>
          <li><a href="properties.php">Browse Homes</a></li>
          <li><a href="/agents">Local Agents</a></li>
          <li><a href="/neighborhoods">Jigjiga Neighborhoods</a></li>
          <li><a href="/moving-guide">Moving to Jigjiga</a></li>
          <li><a href="/faq">Rental FAQ</a></li>
        </ul>
      </div>

      <!-- Popular Areas in Jigjiga -->
      <div class="footer-col">
        <h4>Popular Areas</h4>
        <ul>
          <li><a href="/karaamarda">Karaamarda</a></li>
          <li><a href="/awdale">Aw-Dale</a></li>
          <li><a href="/faafan">Faafan District</a></li>
          <li><a href="/dhabaqa">Dhabaqa</a></li>
          <li><a href="/jigjiga-downtown">Downtown</a></li>
        </ul>
      </div>

      <!-- Company Info -->
      <div class="footer-col">
        <h4>Company</h4>
        <ul>
          <li><a href="/about">About Us</a></li>
          <li><a href="/blog">Jigjiga Living Blog</a></li>
          <li><a href="/careers">Careers</a></li>
          <li><a href="/press">Press</a></li>
          <li><a href="/contact">Contact</a></li>
        </ul>
      </div>

      <!-- Social & Newsletter -->
      <div class="footer-col footer-social">
        <h4>Follow Us</h4>
        <div class="social-links">
          <a href="#"><i class="fab fa-facebook-f"></i></a>
          <a href="#"><i class="fab fa-twitter"></i></a>
          <a href="#"><i class="fab fa-instagram"></i></a>
          <a href="#"><i class="fab fa-telegram"></i></a>
        </div>

        <div class="newsletter">
          <h4>Get Jigjiga Listings</h4>
          <form>
            <input type="email" placeholder="Your Email" required>
            <button type="submit"><i class="fas fa-paper-plane"></i></button>
          </form>
        </div>
      </div>
    </div>

    <!-- Footer Bottom -->
    <div class="footer-bottom">
      <div class="footer-logo">
        <img src="img/jigjigacity.jpeg" alt="Jigjiga Homes">
        <span>Premier Rentals in the Somali Region</span>
      </div>
      <div class="legal-links">
        <a href="privacy.php">Privacy Policy</a>
        <a href="terms.php">Terms of Service</a>
        <a href="sitemap.php">Sitemap</a>
      </div>
      <div class="copyright">
        &copy; 2025 <strong>Jigjiga Homes</strong>. All rights reserved.
      </div>
    </div>
  </div>
</footer>