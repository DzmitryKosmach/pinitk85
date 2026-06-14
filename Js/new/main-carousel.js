// Карусель для главной страницы
(function () {
    // Защита от повторного выполнения
    if (window.carouselInitialized) {
        return;
    }

    function initCarousel() {
        const carousel = document.getElementById("carousel");
        const pagination = document.getElementById("pagination");

        if (!carousel || !pagination || carousel.dataset.initialized === "true") {
            return false;
        }

        // ✅ Получаем <picture> элементы как слайды
        const slides = carousel.querySelectorAll("picture");
        const totalSlides = slides.length;

        if (totalSlides === 0) {
            return false;
        }

        // Добавляем ARIA-атрибуты для доступности
        carousel.setAttribute("role", "region");
        carousel.setAttribute("aria-roledescription", "carousel");
        carousel.setAttribute("aria-label", "Галерея изображений");

        // Создаём точки пагинации
        pagination.innerHTML = "";
        pagination.setAttribute("role", "tablist");
        pagination.setAttribute("aria-label", "Навигация по слайдам");

        for (let i = 0; i < totalSlides; i++) {
            const dot = document.createElement("button");
            dot.classList.add(
                "w-2.5", "h-2.5", "rounded-full", "cursor-pointer",
                "transition-all", "border-0", "p-0"
            );
            dot.dataset.index = i;
            dot.setAttribute("role", "tab");
            dot.setAttribute("aria-label", `Перейти к слайду ${i + 1}`);
            dot.setAttribute("aria-selected", i === 0 ? "true" : "false");

            if (i === 0) {
                dot.classList.add("bg-primary");
            } else {
                dot.classList.add("bg-gray-300");
            }

            dot.addEventListener("click", (e) => {
                goToSlide(parseInt(e.currentTarget.dataset.index));
            });

            pagination.appendChild(dot);
        }

        // ✅ Добавляем ARIA к <picture> элементам
        slides.forEach((slide, index) => {
            slide.setAttribute("role", "tabpanel");
            slide.setAttribute("aria-roledescription", "slide");
            slide.setAttribute("aria-label", `Слайд ${index + 1} из ${totalSlides}`);
        });

        let currentIndex = 0;
        const dots = pagination.children;
        let autoSlideInterval;
        let touchStartX = 0;
        let touchEndX = 0;

        function goToSlide(index) {
            currentIndex = index;
            carousel.style.transform = `translateX(-${currentIndex * 100}%)`;

            // Обновляем точки
            Array.from(dots).forEach((dot, i) => {
                dot.classList.toggle("bg-primary", i === currentIndex);
                dot.classList.toggle("bg-gray-300", i !== currentIndex);
                dot.setAttribute("aria-selected", i === currentIndex ? "true" : "false");
            });

            resetAutoSlide();
        }

        // Автопрокрутка
        function startAutoSlide() {
            autoSlideInterval = setInterval(() => {
                goToSlide((currentIndex + 1) % totalSlides);
            }, 5000);
        }

        function stopAutoSlide() {
            clearInterval(autoSlideInterval);
        }

        function resetAutoSlide() {
            stopAutoSlide();
            startAutoSlide();
        }

        startAutoSlide();

        // Пауза при наведении мыши
        carousel.parentElement.addEventListener("mouseenter", stopAutoSlide);
        carousel.parentElement.addEventListener("mouseleave", startAutoSlide);

        // Поддержка свайпов на мобильных
        carousel.addEventListener("touchstart", (e) => {
            touchStartX = e.changedTouches[0].screenX;
            stopAutoSlide();
        }, { passive: true });

        carousel.addEventListener("touchend", (e) => {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
            startAutoSlide();
        }, { passive: true });

        function handleSwipe() {
            const swipeThreshold = 50;
            const diff = touchStartX - touchEndX;

            if (Math.abs(diff) > swipeThreshold) {
                if (diff > 0) {
                    // Свайп влево - следующий слайд
                    goToSlide((currentIndex + 1) % totalSlides);
                } else {
                    // Свайп вправо - предыдущий слайд
                    goToSlide((currentIndex - 1 + totalSlides) % totalSlides);
                }
            }
        }

        // Клавиатурная навигация
        document.addEventListener("keydown", (e) => {
            const isCarouselFocused = carousel.contains(document.activeElement) ||
                pagination.contains(document.activeElement);

            if (!isCarouselFocused) return;

            if (e.key === "ArrowLeft") {
                e.preventDefault();
                goToSlide((currentIndex - 1 + totalSlides) % totalSlides);
            } else if (e.key === "ArrowRight") {
                e.preventDefault();
                goToSlide((currentIndex + 1) % totalSlides);
            }
        });

        // ✅ Предзагрузка следующего изображения из <picture>
        function preloadNextImage() {
            const nextIndex = (currentIndex + 1) % totalSlides;
            const nextPicture = slides[nextIndex];
            const nextImg = nextPicture.querySelector("img");

            if (nextImg && !nextImg.complete) {
                const link = document.createElement("link");
                link.rel = "prefetch";
                link.href = nextImg.src;
                document.head.appendChild(link);
            }
        }

        // Запускаем предзагрузку при смене слайда
        const originalGoToSlide = goToSlide;
        goToSlide = function(index) {
            originalGoToSlide(index);
            preloadNextImage();
        };

        // Инициализация предзагрузки первого следующего изображения
        preloadNextImage();

        // Помечаем как инициализированную
        carousel.dataset.initialized = "true";
        window.carouselInitialized = true;

        console.log("Карусель инициализирована успешно!");
        return true;
    }

    // Инициализация
    if (!initCarousel()) {
        document.addEventListener("DOMContentLoaded", () => {
            setTimeout(initCarousel, 100);
        });

        // Более эффективный MutationObserver
        if (typeof MutationObserver !== 'undefined') {
            const observer = new MutationObserver((mutations, obs) => {
                if (document.getElementById("carousel") && document.getElementById("pagination")) {
                    setTimeout(initCarousel, 100);
                    obs.disconnect();
                }
            });

            observer.observe(document.documentElement, {
                childList: true,
                subtree: true
            });
        }
    }
})();
