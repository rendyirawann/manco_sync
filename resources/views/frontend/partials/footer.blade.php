<footer class="site-footer">
    <div class="footer-container">
        <div class="footer-top">
            <div class="footer-brand">
                <a href="{{ route('frontend.home') }}" class="footer-logo">
                    <i class="fas fa-book-open"></i> Manco<em>Sync</em>
                </a>
                <p>Platform baca manga, manhwa, dan manhua terlengkap dan terupdate setiap hari. Nikmati ribuan judul favorit Anda secara gratis!</p>
                <div class="footer-social">
                    <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" aria-label="Discord"><i class="fab fa-discord"></i></a>
                </div>
            </div>
            <div class="footer-links">
                <div class="footer-col">
                    <h4>Navigasi</h4>
                    <ul>
                        <li><a href="{{ route('frontend.home') }}">Beranda</a></li>
                        <li><a href="{{ route('portal.stream.index', 'anime') }}">Anime</a></li>
                        <li><a href="{{ route('portal.stream.index', 'comic') }}">Manga &amp; Manhwa</a></li>
                        <li><a href="{{ route('portal.film.index') }}">Film &amp; Movie</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Kategori</h4>
                    <ul>
                        <li><a href="{{ route('portal.stream.index', 'anime') }}">Anime (Jepang)</a></li>
                        <li><a href="{{ route('portal.stream.index', 'donghua') }}">Donghua (China)</a></li>
                        <li><a href="{{ route('portal.stream.index', 'drama') }}">Drama</a></li>
                        <li><a href="{{ route('portal.film.index') }}">Film &amp; Movie</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Informasi</h4>
                    <ul>
                        <li><a href="#">Tentang Kami</a></li>
                        <li><a href="#">Kebijakan Privasi</a></li>
                        <li><a href="#">Syarat Penggunaan</a></li>
                        <li><a href="#">Hubungi Kami</a></li>
                        <li><a href="/admin/login">Admin Panel</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; {{ date('Y') }} <strong>MancoSync</strong>. All rights reserved. Dibuat dengan <i class="fas fa-heart" style="color:#e74c3c"></i> menggunakan Laravel & Rust.</p>
        </div>
    </div>
</footer>
