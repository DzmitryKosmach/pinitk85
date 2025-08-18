function calculateVisibleCards(container) {
    const width = window.innerWidth;
    const slider = container.querySelector(".slider");
    const sliderRect = slider ? slider.getBoundingClientRect() : null;
    const sliderWidth = sliderRect ? sliderRect.width : 0;

    // Условие: если ширина слайдера <= половины ширины экрана → maxVisible = 2
    if (sliderWidth <= width / 2) {
        // Принудительно ограничиваем до 2
        let baseVisible;
        if (width < 640) baseVisible = 1;
        else if (width < 1024) baseVisible = 2;
        else baseVisible = 2;

        return baseVisible; // всегда ≤2
    }

    // Иначе — стандартная логика по классу
    let maxVisible = 7;
    if (container.classList.contains("slider-4")) maxVisible = 4;
    if (container.classList.contains("slider-7")) maxVisible = 7;

    let baseVisible;

    if (maxVisible === 4) {
        if (width < 480) baseVisible = 1;
        else if (width < 768) baseVisible = 2;
        else if (width < 1024) baseVisible = 3;
        else baseVisible = 4;
    } else {
        if (width < 640) baseVisible = 2;
        else if (width < 768) baseVisible = 3;
        else if (width < 1024) baseVisible = 4;
        else if (width < 1280) baseVisible = 5;
        else if (width < 1536) baseVisible = 6;
        else baseVisible = 7;
    }

    return Math.min(baseVisible, maxVisible);
}

function initSliders() {
    const sliders = document.querySelectorAll(".slider-container");

    sliders.forEach((container) => {
        const prev = container.querySelector(".slider-prev");
        const next = container.querySelector(".slider-next");
        const slider = container.querySelector(".slider");
        const track = container.querySelector(".slider-track");
        const cards = track ? track.querySelectorAll(".card") : [];

        // Проверка на наличие элементов
        if (!prev || !next || !slider || !track || cards.length === 0) {
            console.warn("Пропущен слайдер: не хватает элементов", container);
            return;
        }

        let currentSlide = 0;
        let visibleCards = 0;
        let cardWidth = 0;
        const gap = 16; // Соответствует gap-4

        function updateLayout() {
            visibleCards = calculateVisibleCards(container); // Передаём контейнер
            const sliderWidth = slider.offsetWidth;
            cardWidth = (sliderWidth - (visibleCards - 1) * gap) / visibleCards;

            cards.forEach((card) => {
                card.style.width = `${cardWidth}px`;
            });

            slideTo(currentSlide);
        }

        function slideTo(index) {
            const maxIndex = cards.length - visibleCards;
            currentSlide = Math.max(0, Math.min(index, maxIndex));
            const offset = currentSlide * (cardWidth + gap);
            track.style.transform = `translateX(-${offset}px)`;

            // Опционально: отключаем стрелки
            prev.disabled = currentSlide === 0;
            next.disabled = currentSlide >= maxIndex;

            prev.classList.toggle("opacity-50", currentSlide === 0);
            next.classList.toggle("opacity-50", currentSlide >= maxIndex);
        }

        // Клик по стрелкам
        prev.addEventListener("click", (e) => {
            e.preventDefault();
            slideTo(currentSlide - 1);
        });

        next.addEventListener("click", (e) => {
            e.preventDefault();
            slideTo(currentSlide + 1);
        });

        // Свайп
        let touchStartX = 0;
        let touchEndX = 0;

        track.addEventListener("touchstart", (e) => {
            touchStartX = e.changedTouches[0].screenX;
        });

        track.addEventListener("touchend", (e) => {
            touchEndX = e.changedTouches[0].screenX;
            const delta = touchEndX - touchStartX;
            const threshold = 50;

            if (Math.abs(delta) > threshold) {
                if (delta > 0) {
                    slideTo(currentSlide - 1);
                } else {
                    slideTo(currentSlide + 1);
                }
            }
        });

        // Ресайз окна
        function handleResize() {
            updateLayout();
        }

        window.addEventListener("resize", handleResize);

        // Инициализация
        updateLayout();
    });
}

// Запуск после полной загрузки DOM
document.addEventListener("DOMContentLoaded", initSliders);
