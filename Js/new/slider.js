function calculateVisibleCards() {
    const width = window.innerWidth;
    if (width < 640) return 2;
    if (width < 768) return 3;
    if (width < 1024) return 4;
    if (width < 1280) return 5;
    if (width < 1536) return 6;
    return 7;
}

function initSliders() {
    const sliders = document.querySelectorAll('.slider-container');

    sliders.forEach(container => {
        const prev = container.querySelector('.slider-prev');
        const next = container.querySelector('.slider-next');
        const slider = container.querySelector('.slider');
        const track = container.querySelector('.slider-track');
        const cards = track.querySelectorAll('.card');

        let currentSlide = 0;
        let visibleCards = 0;
        let cardWidth = 0;

        function updateLayout() {
            visibleCards = calculateVisibleCards();
            const sliderWidth = slider.offsetWidth;
            cardWidth = (sliderWidth - (visibleCards - 1) * 16) / visibleCards;

            cards.forEach(card => {
                card.style.width = `${cardWidth}px`;
            });

            slideTo(currentSlide);
        }

        function slideTo(index) {
            const maxIndex = cards.length - visibleCards;
            currentSlide = Math.max(0, Math.min(index, maxIndex));
            const offset = currentSlide * (cardWidth + 16);
            track.style.transform = `translateX(-${offset}px)`;
        }

        prev.addEventListener('click', () => slideTo(currentSlide - 1));
        next.addEventListener('click', () => slideTo(currentSlide + 1));
        window.addEventListener('resize', updateLayout);

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

            if (delta > threshold) slideTo(currentSlide - 1);
            else if (delta < -threshold) slideTo(currentSlide + 1);
        });

        updateLayout();
    });
}

window.addEventListener('load', initSliders);
