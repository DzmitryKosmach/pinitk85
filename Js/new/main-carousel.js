// Карусель для главной страницы
(function () {

    // Защита от повторного выполнения
    if (window.carouselInitialized) {
        console.log("Карусель уже инициализирована, пропускаю...");
        return;
    }

    // Проверяем, есть ли уже элементы на странице
    function checkElements() {
        const carousel = document.getElementById("carousel");
        const pagination = document.getElementById("pagination");

        return { carousel, pagination };
    }

    // Функция инициализации карусели
    function initCarousel() {

        const { carousel, pagination } = checkElements();

        if (!carousel || !pagination) {
            console.log("Элементы не найдены, жду загрузки DOM...");
            return false;
        }

        // Проверяем, не инициализирована ли уже карусель
        if (carousel.dataset.initialized === "true") {
            return true;
        }

        const slides = carousel.children;
        const totalSlides = slides.length;


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
        console.log("Инициализация не удалась, жду загрузки DOM...");

        // Если не получилось, ждем загрузки DOM
        document.addEventListener("DOMContentLoaded", () => {
            console.log("DOM загружен, пытаюсь инициализировать карусель...");
            setTimeout(initCarousel, 100); // Небольшая задержка для полной загрузки
        });

        // Дополнительная проверка через MutationObserver
        if (typeof MutationObserver !== 'undefined' && document.body) {
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

            try {
                observer.observe(document.body, {
                    childList: true,
                    subtree: true,
                });
            } catch (e) {
                console.warn("Не удалось создать MutationObserver:", e);
            }
        }
    } else {
        console.log("Карусель инициализирована сразу!");
    }
})();
