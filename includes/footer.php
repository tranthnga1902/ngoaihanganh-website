<footer class="footer">
        <?php
        // Define the footer menu structure
        $footerMenus = [
            'Premier League' => [
                'Home' => '#',
                'Video' => '#',
                'Fixtures' => '#',
                'Results' => '#',
                'Tables' => '#',
                'Stats' => '#',
                'News' => '#',
                'Clubs' => '#',
                'Players' => '#',
                'Transfers' => '#',
                'Awards' => '#',
                'ePremier League' => '#',
            ],
            'Fantasy' => [
                'FPL Home' => '#',
                'My Team' => '#',
                'FPL Transfers' => '#',
                'Leagues' => '#',
                'FPL Fixtures' => '#',
                'Statistics' => '#',
                'The Scout' => '#',
                'FPL Draft' => '#',
            ],
            'Football & Community' => [
                'Wider Football' => '#',
                'PL Charitable Fund' => '#',
                'Community' => '#',
                'Youth Development' => '#',
                'No Room for Racism' => '#',
                'Mental Health' => '#',
                'Rainbow Laces' => '#',
                'Poppy' => '#',
                'PL on YouTube' => '#',
            ],
            'About' => [
                'Overview' => '#',
                'What we do' => '#',
                'Governance' => '#',
                'Statement of Principles' => '#',
                'Inclusion' => '#',
                'Publications' => '#',
                'Partners' => '#',
                'Legal' => '#',
                'Safeguarding' => '#',
                'Careers' => '#',
                'Media' => '#',
            ],
            'Stats' => [
                'Dashboard' => '#',
                'Player Stats' => '#',
                'Club Stats' => '#',
                'All-time Stats' => '#',
                'Milestones' => '#',
                'Records' => '#',
                'Head-to-Head' => '#',
                'Player Comparison' => '#',
            ],
            'More' => [
                'Nike Ball Hub' => '#',
                'FAQs' => '#',
                'Contact Us' => '#',
                'PL30' => '#',
                'References' => '#',
                'Tickets' => '#',
            ],
            'Social' => [
                'PL on TikTok' => '#',
                'PL on Facebook' => '#',
                'PL on X' => '#',
                'PL Communities on X' => '#',
                'Youth on X' => '#',
                'PL on Instagram' => '#',
                'PL USA on Instagram' => '#',
                'La Premier on Instagram' => '#',
                'PL India on X' => '#',
                'PL India on Instagram' => '#',
                'PL Arabic on X' => '#',
                'La Premier on TikTok' => '#',
                'PL Music on Spotify' => '#',
            ],
        ];

        // Loop through each menu category
        foreach ($footerMenus as $category => $links) {
            echo '<div class="footer-column">';
            echo '<h3>' . htmlspecialchars($category) . '</h3>';
            echo '<ul>';
            foreach ($links as $title => $url) {
                echo '<li><a href="' . htmlspecialchars($url) . '">' . htmlspecialchars($title) . '</a></li>';
            }
            echo '</ul>';
            echo '</div>';
        }
        ?>
        <div class="footer-bottom">
            <p>&copy; PREMIER LEAGUE 2025</p>
            <ul>
                <li><a href="#">MODERN SLAVERY STATEMENT</a></li>
                <li><a href="#">EQUALITY, DIVERSITY AND INCLUSION STANDARD</a></li>
                <li><a href="#">TERMS & CONDITIONS</a></li>
                <li><a href="#">POLICIES</a></li>
                <li><a href="#">COOKIE POLICY</a></li>
                <li><a href="#">BACK TO TOP</a></li>
            </ul>
            <img src="<?php echo BASE_URL; ?>assets/img/thongke/pl-logo-full.png" alt="Logo Trang chủ" class="home-logo-footer">
        </div>
    </footer>