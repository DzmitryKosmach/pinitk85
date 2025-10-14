// Добавляем стили для скрытия скроллбара
(function hideScrollbar() {
  const style = document.createElement("style");
  style.textContent = `
      .slider-track::-webkit-scrollbar { display: none; }
      .slider-track { scrollbar-width: none; -ms-overflow-style: none; }
    `;
  document.head.appendChild(style);
})();

document.addEventListener("DOMContentLoaded", () => {
  const container = document.querySelector(".slider-thumbnails-container > .relative");
  const track = container.querySelector(".slider-track");
  const btnPrev = container.querySelector(".slider-prev");
  const btnNext = container.querySelector(".slider-next");
  const mainImage = document.getElementById('main-product-image');

  let isSwiping = false;
  let touchStart = { x: 0, y: 0 };

  // === НОВАЯ: обновлённая updateThumbnailSliderHeight (только контейнер!) ===
  function updateThumbnailSliderHeight() {
    if (!container || !mainImage || window.innerWidth < 1024) return;

    const imageHeight = mainImage.getBoundingClientRect().height;
    if (imageHeight <= 0) return;

    // Управляем ТОЛЬКО контейнером
    container.style.height = imageHeight + 'px';
    container.style.maxHeight = imageHeight + 'px';
    container.style.width = '80px';
    container.style.minWidth = '80px';
    container.style.maxWidth = '80px';
  }

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

  // Устанавливаем размер ТРЕКА, чтобы вмещал целое число слайдов
  function fitToFullSlides() {
    const direction = getScrollDirection();
    const cardSize = getCardSize();
    if (!cardSize) return;

    const containerSize = direction === "vertical"
      ? container.clientHeight
      : container.clientWidth;

    const visibleCount = Math.floor(containerSize / cardSize);

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

    const newSize = visibleCount * cardSize - (parseInt(
      window.getComputedStyle(track)[direction === "vertical" ? "rowGap" : "columnGap"]
    ) || 8);

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

  function updateButtons() {
    const direction = getScrollDirection();
    const max =
      direction === "vertical"
        ? track.scrollHeight - track.clientHeight
        : track.scrollWidth - track.clientWidth;
    const current =
      direction === "vertical" ? track.scrollTop : track.scrollLeft;

    btnPrev.disabled = current === 0;
    btnNext.disabled = current >= max - 1;

    btnPrev.classList.toggle("opacity-50", current === 0);
    btnNext.classList.toggle("opacity-50", current >= max - 1);
  }

  // === НОВАЯ ФУНКЦИЯ: определяет, является ли слайд крайним ===
  function isSlideAtStart(slideRect, trackRect) {
    const direction = getScrollDirection();
    if (direction === "vertical") {
      return slideRect.top <= trackRect.top + 1; // +1 — погрешность
    } else {
      return slideRect.left <= trackRect.left + 1;
    }
  }

  function isSlideAtEnd(slideRect, trackRect) {
    const direction = getScrollDirection();
    if (direction === "vertical") {
      return slideRect.bottom >= trackRect.bottom - 1;
    } else {
      return slideRect.right >= trackRect.right - 1;
    }
  }

  // === НОВАЯ ФУНКЦИЯ: обработчик клика по слайду ===
  function handleSlideClick(e) {
    const slide = e.target.closest("img"); // или другой селектор, если не img
    if (!slide) return;

    const trackRect = track.getBoundingClientRect();
    const slideRect = slide.getBoundingClientRect();

    if (isSlideAtStart(slideRect, trackRect)) {
      scrollPrev();
    } else if (isSlideAtEnd(slideRect, trackRect)) {
      scrollNext();
    }
  }

  // Инициализация
  function init() {
    requestAnimationFrame(() => {
      fitToFullSlides();
      updateButtons();

      // Добавляем обработчик клика на все слайды
      const slides = track.querySelectorAll("img"); // или нужный селектор
      slides.forEach((slide) => {
        slide.style.cursor = "pointer"; // опционально: визуальный фидбек
        slide.addEventListener("click", handleSlideClick);
      });
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
