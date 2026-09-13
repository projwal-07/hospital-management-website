<?php
declare(strict_types=1);

/** Shared page footer for dynamic pages. Closes <main> opened in header.php. */
?>
    </main>

    <footer class="site-footer">
        <div class="container footer-inner">
            <div class="footer-brand">
                <span class="footer-mark" aria-hidden="true">+</span>
                <p class="footer-name">Evergreen Community Hospital<small>Hospital Management System</small></p>
                <p class="footer-tagline">Practical, respectful healthcare for Western Sydney families. Our commitment is to provide quality, accessible and compassionate care to our community.</p>
            </div>

            <nav class="footer-col" aria-label="Footer quick links">
                <h2 class="footer-heading">Quick links</h2>
                <ul class="footer-links">
                    <li><a href="<?= e(BASE_URL) ?>/index.html">Home</a></li>
                    <li><a href="<?= e(BASE_URL) ?>/about.html">About</a></li>
                    <li><a href="<?= e(BASE_URL) ?>/services.html">Services</a></li>
                    <li><a href="<?= e(BASE_URL) ?>/testimonials.html">Patient Stories</a></li>
                    <li><a href="<?= e(BASE_URL) ?>/gallery.html">Gallery</a></li>
                    <li><a href="<?= e(BASE_URL) ?>/contact.html">Contact</a></li>
                </ul>
            </nav>

            <nav class="footer-col" aria-label="Footer patient access">
                <h2 class="footer-heading">Patient access</h2>
                <ul class="footer-links">
                    <li><a href="<?= e(BASE_URL) ?>/auth/login.php">Log in</a></li>
                    <li><a href="<?= e(BASE_URL) ?>/auth/register.php">Register as a patient</a></li>
                    <li><a href="<?= e(BASE_URL) ?>/patient/book.php">Book an appointment</a></li>
                    <li><a href="<?= e(BASE_URL) ?>/patient/appointments.php">My appointments</a></li>
                </ul>
            </nav>

            <div class="footer-col footer-contact">
                <h2 class="footer-heading">Contact</h2>
                <ul class="footer-contact-list">
                    <li><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 21.5s-6.5-5.6-6.5-11A6.5 6.5 0 0 1 18.5 10.5c0 5.4-6.5 11-6.5 11z"/><circle cx="12" cy="10.5" r="2.3"/></svg><span>42 Park Road, Parramatta NSW 2150</span></li>
                    <li><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2 4.2 2 2 0 0 1 4 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7A2 2 0 0 1 22 16.9z"/></svg><a href="tel:+61291234567">(02) 9123 4567</a></li>
                    <li><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><rect x="2.5" y="4.5" width="19" height="15" rx="2"/><path d="m3 6 9 6.5L21 6"/></svg><a href="mailto:reception@evergreenhospital.example">reception@evergreenhospital.example</a></li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <div class="container">
                <p class="footer-copy">&copy; <?= date('Y') ?> Evergreen Community Hospital. All rights reserved.</p>
                <p class="footer-motto"><span class="footer-motto-rule" aria-hidden="true"></span>Your health &middot; Our community &middot; A healthier tomorrow</p>
            </div>
        </div>
    </footer>
</body>
</html>
