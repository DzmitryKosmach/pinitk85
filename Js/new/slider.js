function calculateVisibleCards(container) {
  const width = window.innerWidth;
  const slider = container.querySelector(".slider");
  const sliderRect = slider ? slider.getBoundingClientRect() : null;
  const sliderWidth = sliderRect ? sliderRect.width : 0;

  if (sliderWidth <= width / 2) {
    let baseVisible;
    if (width < 640) baseVisible = 1;
    else if (width < 1024) baseVisible = 2;
    else baseVisible = 2;

    return baseVisible;
  }

  let maxVisible = 7;
  if (container.classList.contains("slider-4")) maxVisible = 4;
  if (container.classList.contains("slider-3")) maxVisible = 3;
  if (container.classList.contains("slider-7")) maxVisible = 7;

  let baseVisible;

  if (maxVisible === 4) {
    if (width < 480) baseVisible = 1;
    else if (width < 768) baseVisible = 2;
    else if (width < 1024) baseVisible = 3;
    else baseVisible = 4;
  } else if (maxVisible === 3) {
    if (width < 480) baseVisible = 1;
    else if (width < 768) baseVisible = 2;
    else if (width < 1024) baseVisible = 2;
    else baseVisible = 3;
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
    const originalCards = track ? Array.from(track.querySelectorAll(".card")) : [];

    if (!prev || !next || !slider || !track || originalCards.length === 0) {
      console.warn("Пропущен слайдер: не хватает элементов", container);
      return;
    }

    // Клонируем карточки для бесконечного эффекта
    const firstCloneSet = originalCards.map(card => card.cloneNode(true));
    const lastCloneSet = originalCards.map(card => card.cloneNode(true));

    // Добавляем клонированные карточки
    firstCloneSet.forEach(card => {
      card.classList.add('cloned-card');
      track.appendChild(card);
    });

    lastCloneSet.forEach(card => {
      card.classList.add('cloned-card');
      track.insertBefore(card, originalCards[0]);
    });

    // Получаем все карточки после клонирования
    const allCards = Array.from(track.querySelectorAll(".card"));
    const gap = 16;
    let currentSlide = originalCards.length; // Начинаем с оригинальных карточек
    let visibleCards = 0;
    let cardWidth = 0;
    let isAnimating = false;

    // Вычисляем общую ширину всех карточек
    function calculateTotalWidth() {
      return allCards.length * (cardWidth + gap) - gap;
    }

    function updateLayout() {
      visibleCards = calculateVisibleCards(container);
      const sliderWidth = slider.offsetWidth;
      cardWidth = (sliderWidth - (visibleCards - 1) * gap) / visibleCards;

      allCards.forEach((card) => {
        card.style.width = `${cardWidth}px`;
      });

      // Устанавливаем ширину трека
      track.style.width = `${calculateTotalWidth()}px`;

      // Перемещаемся к начальной позиции (первым оригинальным карточкам)
      slideTo(currentSlide, false);
    }

    function slideTo(index, animate = true) {
      if (isAnimating) return;

      isAnimating = true;
      currentSlide = index;

      // Рассчитываем смещение
      let offset = currentSlide * (cardWidth + gap);

      // Применяем анимацию
      track.style.transition = animate ? 'transform 0.3s ease' : 'none';
      track.style.transform = `translateX(-${offset}px)`;

      // После анимации проверяем, нужно ли бесшовно перейти к другой части
      if (animate) {
        setTimeout(() => {
          checkBoundaries();
          isAnimating = false;
        }, 300);
      } else {
        isAnimating = false;
      }
    }

    function checkBoundaries() {
      // Если мы в начале (на клонированных карточках в начале)
      if (currentSlide <= 0) {
        // Бесшовно переходим к оригинальным карточкам в конце
        currentSlide = originalCards.length;
        track.style.transition = 'none';
        track.style.transform = `translateX(-${currentSlide * (cardWidth + gap)}px)`;

        // Принудительный рефлоу для сброса анимации
        void track.offsetWidth;
      }
      // Если мы в конце (на клонированных карточках в конце)
      else if (currentSlide >= originalCards.length * 2 - visibleCards) {
        // Бесшовно переходим к оригинальным карточкам в начале
        currentSlide = originalCards.length - visibleCards;
        track.style.transition = 'none';
        track.style.transform = `translateX(-${currentSlide * (cardWidth + gap)}px)`;

        // Принудительный рефлоу для сброса анимации
        void track.offsetWidth;
      }
    }

    // Клик по стрелкам
    prev.addEventListener("click", (e) => {
      e.preventDefault();
      if (isAnimating) return;
      slideTo(currentSlide - 1);
    });

    next.addEventListener("click", (e) => {
      e.preventDefault();
      if (isAnimating) return;
      slideTo(currentSlide + 1);
    });

    // Свайп
    let touchStartX = 0;
    let touchEndX = 0;
    let isSwiping = false;

    track.addEventListener("touchstart", (e) => {
      touchStartX = e.changedTouches[0].screenX;
      isSwiping = true;
    });

    track.addEventListener("touchmove", (e) => {
      if (!isSwiping) return;

      touchEndX = e.changedTouches[0].screenX;
      const delta = touchEndX - touchStartX;

      // Плавное перемещение при свайпе
      if (Math.abs(delta) > 10) {
        track.style.transition = 'none';
        const baseOffset = currentSlide * (cardWidth + gap);
        track.style.transform = `translateX(-${baseOffset - delta}px)`;
      }
    });

    track.addEventListener("touchend", (e) => {
      if (!isSwiping) return;
      isSwiping = false;

      touchEndX = e.changedTouches[0].screenX;
      const delta = touchEndX - touchStartX;
      const threshold = 50;

      if (Math.abs(delta) > threshold) {
        if (delta > 0) {
          slideTo(currentSlide - 1);
        } else {
          slideTo(currentSlide + 1);
        }
      } else {
        // Возвращаемся к текущей позиции
        slideTo(currentSlide);
      }
    });

    // Ресайз окна
    let resizeTimeout;
    function handleResize() {
      clearTimeout(resizeTimeout);
      resizeTimeout = setTimeout(() => {
        updateLayout();
      }, 100);
    }

    window.addEventListener("resize", handleResize);

    // Инициализация
    updateLayout();
  });
}

// Запуск после полной загрузки DOM
document.addEventListener("DOMContentLoaded", initSliders);
