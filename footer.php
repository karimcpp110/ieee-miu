<footer class="main-footer">
    <div class="container footer-content">
        <div class="footer-info">
            <img src="logo.png?v=1" alt="IEEE MIU Logo"
                style="height: 50px; width: auto; margin-bottom: 1.5rem; opacity: 0.9;">
            <p>&copy;
                <?= date('Y') ?> IEEE. All rights reserved.
            </p>
            <p class="hosting-notice">Use of this website signifies your agreement to the <a
                    href="https://www.ieee.org/about/help/site-terms-conditions.html" target="_blank">IEEE Terms and
                    Conditions</a>.</p>
            <p class="mission-text">A non-profit organization, IEEE is the world's largest technical professional
                organization dedicated to advancing technology for the benefit of humanity.</p>
        </div>
        <div class="footer-social">
            <a href="https://www.facebook.com/share/17tUvXf92f/?mibextid=wwXIfr" target="_blank" title="Facebook"><i
                    class="fab fa-facebook-f"></i></a>
            <a href="https://www.instagram.com/ieeemiu_sb" target="_blank" title="Instagram"><i
                    class="fab fa-instagram"></i></a>
            <a href="https://www.linkedin.com/company/ieee-miu/" target="_blank" title="LinkedIn"><i
                    class="fab fa-linkedin-in"></i></a>
            <a href="#" target="_blank" title="Share"><i class="fas fa-share-alt"></i></a>
        </div>
        <div class="footer-links" style="text-align: left;">
            <p><strong>Quick Links</strong></p>
            <p><a href="index.php" style="color: inherit; text-decoration: none; opacity: 0.7;">Home</a></p>
            <p><a href="courses.php" style="color: inherit; text-decoration: none; opacity: 0.7;">Courses</a></p>
            <p><a href="events.php" style="color: inherit; text-decoration: none; opacity: 0.7;">Events & Gallery</a>
            </p>
            <p><a href="board.php" style="color: inherit; text-decoration: none; opacity: 0.7;">Board</a></p>
        </div>
        <div class="footer-credits">
            <p>IEEE MIU Student Branch</p>
            <p>Developed by <a href="https://www.linkedin.com/in/karim-wael-40132b360/" target="_blank"
                    class="text-gradient">Karim Wael</a></p>
        </div>
    </div>
</footer>

<style>
    .main-footer {
        padding: 6rem 2rem 4rem;
        border-top: 1px solid var(--glass-border);
        margin-top: 6rem;
        background: linear-gradient(to top, rgba(0, 98, 155, 0.05) 0%, transparent 100%);
    }

    .footer-content {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        gap: 4rem;
    }

    .footer-info {
        max-width: 600px;
    }

    .footer-info p {
        margin-bottom: 0.5rem;
        font-size: 0.95rem;
        color: var(--text-main);
    }

    .hosting-notice {
        font-size: 0.85rem !important;
        color: var(--text-muted) !important;
    }

    .hosting-notice a {
        color: var(--primary-neon);
        text-decoration: none;
    }

    .mission-text {
        font-size: 0.8rem !important;
        color: var(--text-muted) !important;
        font-style: italic;
        margin-top: 1.5rem;
    }

    .footer-credits {
        text-align: right;
        font-weight: 700;
    }

    .footer-credits p {
        margin-bottom: 0.25rem;
    }

    .footer-credits a {
        text-decoration: none;
        font-size: 1.1rem;
    }

    /* Social Media Icons */
    .footer-social {
        display: flex;
        gap: 1.5rem;
        margin-bottom: 0;
    }

    .footer-social a {
        width: 45px;
        height: 45px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--glass-bg-bright);
        border: 1px solid var(--glass-border);
        border-radius: 50%;
        color: var(--text-main);
        font-size: 1.2rem;
        transition: var(--transition);
        text-decoration: none;
    }

    .footer-social a:hover {
        background: var(--primary-neon);
        color: #fff;
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0, 98, 155, 0.4);
        border-color: var(--primary-neon);
    }

    @media (max-width: 768px) {
        .footer-content {
            flex-direction: column;
            text-align: center;
            align-items: center;
            gap: 2rem;
        }

        .footer-credits {
            text-align: center;
        }
    }
</style>