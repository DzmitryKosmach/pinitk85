// Карусель для главной страницы
(function () {
    console.log("=== main-carousel.js загружен через JsCopmress ===");

    // Защита от повторного выполнения
    if (window.carouselInitialized) {
        console.log("Карусель уже инициализирована, пропускаю...");
        return;
    }

    // Проверяем, есть ли уже элементы на странице
    function checkElements() {
        const carousel = document.getElementById("carousel");
        const pagination = document.getElementById("pagination");

        console.log("Проверяю элементы:");
        console.log("- carousel:", carousel);
        console.log("- pagination:", pagination);

        return { carousel, pagination };
    }

    // Функция инициализации карусели
    function initCarousel() {
        console.log("Инициализирую карусель...");

        const { carousel, pagination } = checkElements();

        if (!carousel || !pagination) {
            console.log("Элементы не найдены, жду загрузки DOM...");
            return false;
        }

        // Проверяем, не инициализирована ли уже карусель
        if (carousel.dataset.initialized === "true") {
            console.log("Карусель уже инициализирована в этом элементе");
            return true;
        }

        const slides = carousel.children;
        const totalSlides = slides.length;

        console.log("Найдено слайдов:", totalSlides);

        if (totalSlides === 0) {
            console.log("Слайды пустые, жду загрузки...");
            return false;
        }

        // Очищаем существующие точки пагинации
        pagination.innerHTML = "";

        // Создаём точки пагинации
        for (let i = 0; i < totalSlides; i++) {
            const dot = document.createElement("span");
            dot.classList.add(
                "w-2.5",
                "h-2.5",
                "rounded-full",
                "cursor-pointer",
                "transition-all"
            );
            dot.dataset.index = i;
            if (i === 0) {
                dot.classList.add("bg-blue-600");
            } else {
                dot.classList.add("bg-gray-300");
            }
            dot.addEventListener("click", (e) => {
                const index = parseInt(e.target.dataset.index);
                goToSlide(index);
            });
            pagination.appendChild(dot);
        }

        console.log("Точки пагинации созданы");

        let currentIndex = 0;
        const dots = pagination.children;

        // Функция переключения слайда
        function goToSlide(index) {
            currentIndex = index;
            carousel.style.transform = `translateX(-${currentIndex * 100}%)`;

            // Обновляем активную точку
            for (let i = 0; i < dots.length; i++) {
                dots[i].classList.toggle("bg-blue-600", i === currentIndex);
                dots[i].classList.toggle("bg-gray-300", i !== currentIndex);
            }

            resetAutoSlide();
        }

        // Автопрокрутка
        let autoSlideInterval;

        function startAutoSlide() {
            autoSlideInterval = setInterval(() => {
                currentIndex = (currentIndex + 1) % totalSlides;
                goToSlide(currentIndex);
            }, 5000);
        }

        function stopAutoSlide() {
            clearInterval(autoSlideInterval);
        }

        function resetAutoSlide() {
            stopAutoSlide();
            startAutoSlide();
        }

        // Запускаем
        startAutoSlide();

        // События мыши
        carousel.parentElement.addEventListener("mouseenter", stopAutoSlide);
        carousel.parentElement.addEventListener("mouseleave", startAutoSlide);

        // Помечаем карусель как инициализированную
        carousel.dataset.initialized = "true";
        window.carouselInitialized = true;

        console.log("Карусель инициализирована успешно!");
        return true;
    }

    // Пытаемся инициализировать сразу
    if (!initCarousel()) {
        // Если не получилось, ждем загрузки DOM
        document.addEventListener("DOMContentLoaded", () => {
            console.log("DOM загружен, пытаюсь инициализировать карусель...");
            setTimeout(initCarousel, 100); // Небольшая задержка для полной загрузки
        });

        // Дополнительная проверка через MutationObserver
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === "childList") {
                    if (
                        document.getElementById("carousel") &&
                        document.getElementById("pagination")
                    ) {
                        console.log(
                            "Элементы появились через MutationObserver"
                        );
                        observer.disconnect();
                        setTimeout(initCarousel, 100);
                    }
                }
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true,
        });
    }
})();
