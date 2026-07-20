/* ===== MANCO SYNC FRONTEND JS ===== */

(function () {
    'use strict';

    /* ──────────────────────────────────────────
       1. THEME (Dark / Light) — persisted in localStorage
    ────────────────────────────────────────── */
    const THEME_KEY = 'manco_theme';
    const html      = document.documentElement;
    const themeBtn  = document.getElementById('theme-toggle');
    const themeIcon = document.getElementById('theme-icon');

    function applyTheme(theme) {
        html.setAttribute('data-theme', theme);
        if (themeIcon) {
            themeIcon.className = theme === 'dark' ? 'fas fa-moon' : 'fas fa-sun';
        }
        localStorage.setItem(THEME_KEY, theme);
    }

    // Init theme
    const savedTheme = localStorage.getItem(THEME_KEY) || 'dark';
    applyTheme(savedTheme);

    if (themeBtn) {
        themeBtn.addEventListener('click', () => {
            const current = html.getAttribute('data-theme');
            applyTheme(current === 'dark' ? 'light' : 'dark');
        });
    }

    /* ──────────────────────────────────────────
       2. SPLASH SCREEN — show once per day
    ────────────────────────────────────────── */
    const SPLASH_KEY  = 'manco_splash_date';
    const splashEl    = document.getElementById('splash-screen');
    const mainSite    = document.getElementById('main-site');

    function todayStr() {
        return new Date().toISOString().slice(0, 10); // YYYY-MM-DD
    }

    function hideSplash() {
        if (!splashEl) return;
        splashEl.classList.add('hide');
        setTimeout(() => {
            splashEl.style.display = 'none';
            if (mainSite) mainSite.style.display = 'block';
        }, 720);
        localStorage.setItem(SPLASH_KEY, todayStr());
    }

    if (splashEl) {
        const lastSeen = localStorage.getItem(SPLASH_KEY);
        if (lastSeen === todayStr()) {
            // Already seen today — skip splash
            splashEl.style.display = 'none';
            if (mainSite) mainSite.style.display = 'block';
        } else {
            // Show splash
            if (mainSite) mainSite.style.display = 'none';
            splashEl.style.display = 'flex';

            // Generate floating particles
            const particlesEl = document.getElementById('splash-particles');
            if (particlesEl) {
                for (let i = 0; i < 20; i++) {
                    const p = document.createElement('span');
                    p.className = 'particle';
                    p.style.cssText = `
                        position:absolute;
                        width:${Math.random()*6+3}px;
                        height:${Math.random()*6+3}px;
                        border-radius:50%;
                        background:rgba(139,92,246,${Math.random()*0.6+0.2});
                        top:${Math.random()*100}%;
                        left:${Math.random()*100}%;
                        animation: float-particle ${Math.random()*6+4}s ease-in-out infinite;
                        animation-delay: ${Math.random()*4}s;
                    `;
                    particlesEl.appendChild(p);
                }
            }

            // Click anywhere OR button to enter
            const enterBtn = document.getElementById('splash-enter');
            if (enterBtn) enterBtn.addEventListener('click', (e) => { e.stopPropagation(); hideSplash(); });
            splashEl.addEventListener('click', hideSplash);
        }
    }

    // CSS for floating particles (injected dynamically)
    const particleStyle = document.createElement('style');
    particleStyle.textContent = `
        @keyframes float-particle {
            0%, 100% { transform: translateY(0) scale(1); opacity: 0.6; }
            50%       { transform: translateY(-40px) scale(1.3); opacity: 1; }
        }
    `;
    document.head.appendChild(particleStyle);

    /* ──────────────────────────────────────────
       3. HERO SLIDER
    ────────────────────────────────────────── */
    const track     = document.getElementById('slider-track');
    const dotsEl    = document.getElementById('slider-dots');
    const prevBtn   = document.getElementById('slider-prev');
    const nextBtn   = document.getElementById('slider-next');

    if (track) {
        const slides = track.querySelectorAll('.slide');
        let current  = 0;
        let autoTimer;

        // Build dots
        slides.forEach((_, i) => {
            const dot = document.createElement('button');
            dot.className = 'slider-dot' + (i === 0 ? ' active' : '');
            dot.setAttribute('aria-label', 'Slide ' + (i+1));
            dot.addEventListener('click', () => goTo(i));
            if (dotsEl) dotsEl.appendChild(dot);
        });

        function goTo(index) {
            current = (index + slides.length) % slides.length;
            track.style.transform = `translateX(-${current * 100}%)`;
            document.querySelectorAll('.slider-dot').forEach((d, i) => {
                d.classList.toggle('active', i === current);
            });
        }

        function startAuto() {
            clearInterval(autoTimer);
            autoTimer = setInterval(() => goTo(current + 1), 5000);
        }

        if (prevBtn) prevBtn.addEventListener('click', () => { goTo(current - 1); startAuto(); });
        if (nextBtn) nextBtn.addEventListener('click', () => { goTo(current + 1); startAuto(); });

        // Swipe support
        let touchStartX = 0;
        track.addEventListener('touchstart', e => { touchStartX = e.touches[0].clientX; }, { passive: true });
        track.addEventListener('touchend', e => {
            const diff = touchStartX - e.changedTouches[0].clientX;
            if (Math.abs(diff) > 50) { goTo(diff > 0 ? current + 1 : current - 1); startAuto(); }
        });

        if (slides.length > 1) startAuto();
    }

    /* ──────────────────────────────────────────
       4. HEADER SCROLL EFFECT
    ────────────────────────────────────────── */
    const header = document.getElementById('site-header');
    if (header) {
        window.addEventListener('scroll', () => {
            header.style.boxShadow = window.scrollY > 10
                ? '0 4px 24px rgba(0,0,0,0.5)' : 'none';
        }, { passive: true });
    }

    /* ──────────────────────────────────────────
       5. HAMBURGER (mobile nav)
    ────────────────────────────────────────── */
    const hamburger = document.getElementById('hamburger');
    const navMenu   = document.getElementById('nav-menu');
    if (hamburger && navMenu) {
        hamburger.addEventListener('click', () => {
            navMenu.classList.toggle('open');
        });
        document.addEventListener('click', e => {
            if (!hamburger.contains(e.target) && !navMenu.contains(e.target)) {
                navMenu.classList.remove('open');
            }
        });
    }

    /* ──────────────────────────────────────────
       6. LIVE SEARCH (AJAX)
    ────────────────────────────────────────── */
    const searchInput    = document.getElementById('search-input');
    const searchDropdown = document.getElementById('search-dropdown');
    let   searchTimer;

    if (searchInput && searchDropdown) {
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimer);
            const q = searchInput.value.trim();
            if (q.length < 2) { searchDropdown.classList.remove('show'); searchDropdown.innerHTML = ''; return; }

            searchTimer = setTimeout(() => {
                fetch(`/api/search?q=${encodeURIComponent(q)}`)
                    .then(r => r.json())
                    .then(data => {
                        if (!data.length) { searchDropdown.classList.remove('show'); return; }
                        searchDropdown.innerHTML = data.map(m => `
                            <a href="${m.url}" class="search-item">
                                <img src="${m.cover_url}" alt="${m.title}" onerror="this.src='/images/frontend/no-cover.jpg'">
                                <div>
                                    <div style="font-size:.85rem;font-weight:600">${m.title}</div>
                                    <div style="font-size:.72rem;text-transform:uppercase;color:var(--text-muted)">${m.type}</div>
                                </div>
                            </a>
                        `).join('');
                        searchDropdown.classList.add('show');
                    })
                    .catch(() => searchDropdown.classList.remove('show'));
            }, 350);
        });

        document.addEventListener('click', e => {
            if (!searchInput.contains(e.target) && !searchDropdown.contains(e.target)) {
                searchDropdown.classList.remove('show');
            }
        });

        searchInput.addEventListener('keydown', e => {
            if (e.key === 'Enter' && searchInput.value.trim()) {
                window.location.href = `/daftar-komik?q=${encodeURIComponent(searchInput.value.trim())}`;
            }
        });
    }

})();
