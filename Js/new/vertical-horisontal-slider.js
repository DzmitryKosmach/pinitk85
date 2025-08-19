document.addEventListener("DOMContentLoaded", () => {
    const container = document.querySelector(".slider-thumbnails-container");
    const track = container.querySelector(".slider-track");
    const btnPrev = container.querySelector(".slider-prev");
    const btnNext = container.querySelector(".slider-next");

    let isSwiping = false;
    let touchStart = { x: 0, y: 0 };

    // Определяем направление
    function getScrollDirection() {
        const computed = window.getComputedStyle(track);
        return computed.flexDirection.includes("column") ? "vertical" : "horizontal";
    }

    // Получаем размер одной карточки + gap
    function getCardSize() {
        const firstCard = track.querySelector("img");
        if (!firstCard) return 0;

        const direction = getScrollDirection();
        const styles = window.getComputedStyle(track);
        const gap = parseInt(styles[direction === "vertical" ? "rowGap" : "columnGap"]) || 8;
        const size = direction === "vertical"
            ? firstCard.offsetHeight + gap
            : firstCard.offsetWidth + gap;

        return size;
    }

    // Устанавливаем размер контейнера, чтобы вмещал целое число слайдов
    function fitToFullSlides() {
        const direction = getScrollDirection();
        const cardSize = getCardSize();
        if (!cardSize) return;

        const trackStyles = window.getComputedStyle(track);
        const containerSize = direction === "vertical"
            ? track.parentElement.clientHeight
            : track.parentElement.clientWidth;

        // Сколько слайдов помещается по размеру?
        const visibleCount = Math.floor(containerSize / cardSize);

        // Если слайдов мало — отключаем скролл
        if (visibleCount >= track.children.length) {
            if (direction === "vertical") {
                track.style.height = "auto";
                track.style.maxHeight = "none";
            } else {
                track.style.width = "auto";
                track.style.maxWidth = "none";
            }
            track.style.overflow = "hidden";
            return false;
        }

        // Устанавливаем высоту/ширину, чтобы вмещалось ровно `visibleCount` слайдов
        const newSize = visibleCount * cardSize - gap; // минус последний gap
        if (direction === "vertical") {
            track.style.height = `${newSize}px`;
            track.style.maxHeight = `${newSize}px`;
        } else {
            track.style.width = `${newSize}px`;
            track.style.maxWidth = `${newSize}px`;
        }

        return true;
    }

    function scrollPrev() {
        const size = getCardSize();
        const direction = getScrollDirection();
        const scrollAmount = size;

        if (direction === "vertical") {
            track.scrollTop = Math.max(0, track.scrollTop - scrollAmount);
        } else {
            track.scrollLeft = Math.max(0, track.scrollLeft - scrollAmount);
        }
    }

    function scrollNext() {
        const size = getCardSize();
        const direction = getScrollDirection();
        const scrollAmount = size;

        if (direction === "vertical") {
            track.scrollTop += scrollAmount;
        } else {
            track.scrollLeft += scrollAmount;
        }
    }

    // Обновляем состояние кнопок
    function updateButtons() {
        const direction = getScrollDirection();
        const max = direction === "vertical"
            ? track.scrollHeight - track.clientHeight
            : track.scrollWidth - track.clientWidth;
        const current = direction === "vertical" ? track.scrollTop : track.scrollLeft;

        btnPrev.disabled = current === 0;
        btnNext.disabled = current >= max - 1;

        btnPrev.classList.toggle("opacity-50", current === 0);
        btnNext.classList.toggle("opacity-50", current >= max - 1);
    }

    // Инициализация
    function init() {
        // Сначала рендер, потом размеры
        requestAnimationFrame(() => {
            fitToFullSlides();
            updateButtons();
        });
    }

    // Кнопки
    btnPrev.addEventListener("click", (e) => {
        e.preventDefault();
        scrollPrev();
        setTimeout(updateButtons, 100);
    });

    btnNext.addEventListener("click", (e) => {
        e.preventDefault();
        scrollNext();
        setTimeout(updateButtons, 100);
    });

    // Свайп
    track.addEventListener("touchstart", (e) => {
        isSwiping = true;
        touchStart.x = e.touches[0].clientX;
        touchStart.y = e.touches[0].clientY;
        track.style.scrollBehavior = "auto";
    });

    track.addEventListener("touchmove", (e) => {
        if (!isSwiping) return;
        const touch = e.touches[0];
        const dx = touchStart.x - touch.clientX;
        const dy = touchStart.y - touch.clientY;

        if (getScrollDirection() === "vertical") {
            track.scrollTop += dy;
        } else {
            track.scrollLeft += dx;
        }

        touchStart.x = touch.clientX;
        touchStart.y = touch.clientY;
    });

    track.addEventListener("touchend", () => {
        isSwiping = false;
        setTimeout(() => {
            track.style.scrollBehavior = "smooth";
        }, 50);
    });

    // События
    track.addEventListener("scroll", updateButtons);
    window.addEventListener("resize", () => {
        fitToFullSlides();
        updateButtons();
    });

    init();
});
