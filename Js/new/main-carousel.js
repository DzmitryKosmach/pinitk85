// Карусель для главной страницы
(function () {
    if (window.carouselInitialized) {
        return;
    }

    const MOBILE_QUERY = '(max-width: 414px)';

    function isMobileViewport() {
        return window.matchMedia(MOBILE_QUERY).matches;
    }

    function loadSlideImage(slide) {
        const img = slide.querySelector('img[data-src]');
        if (!img || img.dataset.loaded === 'true') {
            return;
        }

        const picture = img.closest('picture');
        const source = picture ? picture.querySelector('source[data-srcset]') : null;
        const src = isMobileViewport() && img.dataset.srcMobile
            ? img.dataset.srcMobile
            : img.dataset.src;

        if (source && source.dataset.srcset) {
            source.srcset = source.dataset.srcset;
            source.removeAttribute('data-srcset');
        }

        img.src = src;
        img.dataset.loaded = 'true';
        img.removeAttribute('data-src');
        img.removeAttribute('data-src-mobile');
    }

    function initCarousel() {
        const carousel = document.getElementById('carousel');
        const pagination = document.getElementById('pagination');

        if (!carousel || !pagination || carousel.dataset.initialized === 'true') {
            return false;
        }

        const slides = carousel.querySelectorAll(':scope > div[role="tabpanel"]');
        const totalSlides = slides.length;

        if (totalSlides === 0) {
            return false;
        }

        let dots = pagination.querySelectorAll('button[data-index]');
        if (dots.length !== totalSlides) {
            pagination.innerHTML = '';
            dots = [];

            for (let i = 0; i < totalSlides; i++) {
                const dot = document.createElement('button');
                dot.type = 'button';
                dot.classList.add(
                    'w-2.5', 'h-2.5', 'rounded-full', 'cursor-pointer',
                    'transition-all', 'border-0', 'p-0'
                );
                dot.dataset.index = String(i);
                dot.setAttribute('role', 'tab');
                dot.setAttribute('aria-label', `Перейти к слайду ${i + 1}`);
                dot.setAttribute('aria-selected', i === 0 ? 'true' : 'false');
                dot.classList.add(i === 0 ? 'bg-primary' : 'bg-gray-300');
                pagination.appendChild(dot);
            }

            dots = pagination.querySelectorAll('button[data-index]');
        }

        let currentIndex = 0;
        let autoSlideInterval = null;
        let touchStartX = 0;
        let touchEndX = 0;
        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        function updateSlideVisibility(index) {
            slides.forEach((slide, i) => {
                slide.setAttribute('aria-hidden', i === index ? 'false' : 'true');
            });
        }

        function goToSlide(index) {
            currentIndex = index;
            carousel.style.transform = `translateX(-${currentIndex * 100}%)`;

            Array.from(dots).forEach((dot, i) => {
                dot.classList.toggle('bg-primary', i === currentIndex);
                dot.classList.toggle('bg-gray-300', i !== currentIndex);
                dot.setAttribute('aria-selected', i === currentIndex ? 'true' : 'false');
            });

            updateSlideVisibility(currentIndex);
            loadSlideImage(slides[currentIndex]);
            preloadNextImage();
            resetAutoSlide();
        }

        function startAutoSlide() {
            if (prefersReducedMotion) {
                return;
            }

            autoSlideInterval = setInterval(() => {
                goToSlide((currentIndex + 1) % totalSlides);
            }, 5000);
        }

        function stopAutoSlide() {
            clearInterval(autoSlideInterval);
            autoSlideInterval = null;
        }

        function resetAutoSlide() {
            stopAutoSlide();
            startAutoSlide();
        }

        function preloadNextImage() {
            const nextIndex = (currentIndex + 1) % totalSlides;
            loadSlideImage(slides[nextIndex]);
        }

        function handleSwipe() {
            const swipeThreshold = 50;
            const diff = touchStartX - touchEndX;

            if (Math.abs(diff) <= swipeThreshold) {
                return;
            }

            if (diff > 0) {
                goToSlide((currentIndex + 1) % totalSlides);
            } else {
                goToSlide((currentIndex - 1 + totalSlides) % totalSlides);
            }
        }

        dots.forEach((dot) => {
            dot.addEventListener('click', (e) => {
                goToSlide(parseInt(e.currentTarget.dataset.index, 10));
            });
        });

        carousel.parentElement.addEventListener('mouseenter', stopAutoSlide);
        carousel.parentElement.addEventListener('mouseleave', startAutoSlide);

        carousel.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
            stopAutoSlide();
        }, { passive: true });

        carousel.addEventListener('touchend', (e) => {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
            startAutoSlide();
        }, { passive: true });

        document.addEventListener('keydown', (e) => {
            const isCarouselFocused = carousel.contains(document.activeElement) ||
                pagination.contains(document.activeElement);

            if (!isCarouselFocused) {
                return;
            }

            if (e.key === 'ArrowLeft') {
                e.preventDefault();
                goToSlide((currentIndex - 1 + totalSlides) % totalSlides);
            } else if (e.key === 'ArrowRight') {
                e.preventDefault();
                goToSlide((currentIndex + 1) % totalSlides);
            }
        });

        updateSlideVisibility(0);
        startAutoSlide();

        if ('requestIdleCallback' in window) {
            requestIdleCallback(() => preloadNextImage(), { timeout: 2000 });
        } else {
            setTimeout(preloadNextImage, 1500);
        }

        carousel.dataset.initialized = 'true';
        window.carouselInitialized = true;

        return true;
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            requestAnimationFrame(initCarousel);
        });
    } else {
        requestAnimationFrame(initCarousel);
    }

    if (typeof MutationObserver !== 'undefined' && !window.carouselInitialized) {
        const observer = new MutationObserver((mutations, obs) => {
            if (document.getElementById('carousel') && document.getElementById('pagination')) {
                requestAnimationFrame(initCarousel);
                obs.disconnect();
            }
        });

        observer.observe(document.documentElement, {
            childList: true,
            subtree: true
        });
    }
})();
