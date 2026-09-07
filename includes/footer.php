<?php
declare(strict_types=1);

/** Shared page footer for dynamic pages. Closes <main> opened in header.php. */
?>
    </main>

    <footer class="site-footer">
        <section class="container footer-grid">
            <section>
                <h2>Evergreen Community Hospital</h2>
                <p>Practical, respectful healthcare for Western Sydney families.</p>
            </section>
            <nav aria-label="Footer navigation">
                <h3>Quick links</h3>
                <ul class="footer-links">
                    <li><a href="<?= e(BASE_URL) ?>/services.html">Services</a></li>
                    <li><a href="<?= e(BASE_URL) ?>/contact.html">Contact</a></li>
                    <li><a href="<?= e(BASE_URL) ?>/auth/login.php">Log in</a></li>
                </ul>
            </nav>
            <section>
                <h3>Contact</h3>
                <address>42 Park Road, Parramatta NSW 2150<br>
                    <a href="mailto:reception@evergreenhospital.example">reception@evergreenhospital.example</a>
                </address>
            </section>
        </section>
        <p class="copyright">Copyright &copy; <?= date('Y') ?> Evergreen Community Hospital. Demonstration system.</p>
    </footer>
</body>
</html>
