// Добавляем стили для скрытия скроллбара
(function hideScrollbarAndHideTrack() {
  const style = document.createElement("style");
  style.textContent = `
    .slider-track::-webkit-scrollbar { display: none; }
    .slider-track {
      scrollbar-width: none;
      -ms-overflow-style: none;
      opacity: 0;
      transition: opacity 0.2s ease;
    }
    .slider-track.visible {
      opacity: 1;
    }
  `;
  document.head.appendChild(style);
})();

document.addEventListener("DOMContentLoaded", () => {
  const container = document.querySelector(
    ".slider-thumbnails-container > .relative"
  );
  if (!container) return;

  const track = container.querySelector(".slider-track");
  const btnPrev = container.querySelector(".slider-prev");
  const btnNext = container.querySelector(".slider-next");
  const mainImage = document.getElementById("main-product-image");

  if (!track || !btnPrev || !btnNext) return;

  let isSwiping = false;
  let touchStart = { x: 0, y: 0 };

  function updateThumbnailSliderHeight() {
    if (!container || !mainImage || window.innerWidth < 1024) return;
    const imageHeight = mainImage.getBoundingClientRect().height;
    if (imageHeight <= 0) return;

    container.style.height = imageHeight + "px";
    container.style.maxHeight = imageHeight + "px";
    container.style.width = "80px";
    container.style.minWidth = "80px";
    container.style.maxWidth = "80px";
  }

  function getScrollDirection() {
    const computed = window.getComputedStyle(track);
    return computed.flexDirection.includes("column")
      ? "vertical"
      : "horizontal";
  }

  // ✅ Только размер слайда (без gap)
  function getSlideSize() {
    const firstCard = track.querySelector("img");
    if (!firstCard) return 0;
    return getScrollDirection() === "vertical"
      ? firstCard.offsetHeight
      : firstCard.offsetWidth;
  }

  // ✅ Только gap
  function getGap() {
    const direction = getScrollDirection();
    const styles = window.getComputedStyle(track);
    return (
      parseInt(styles[direction === "vertical" ? "rowGap" : "columnGap"]) || 8
    );
  }

  // Обновлённая функция под новые вспомогательные
  function fitToFullSlides() {
    const direction = getScrollDirection();
    const slideSize = getSlideSize();
    const gap = getGap();

    if (!slideSize || slideSize <= 0) return false;

    // ✅ Используем РЕАЛЬНЫЙ размер контейнера слайдера
    const containerSize =
      direction === "vertical" ? container.clientHeight : container.clientWidth;

    if (containerSize <= 0) return false;

    // Считаем максимальное количество целых слайдов
    let visibleCount = 1;
    while (
      visibleCount * slideSize + (visibleCount - 1) * gap <=
      containerSize
      ) {
      visibleCount++;
    }
    visibleCount = Math.max(1, visibleCount - 1);

    if (visibleCount >= track.children.length) {
      track.style.overflow = "hidden";
      return false;
    }

    // Устанавливаем точный размер под N слайдов
    const newSize = visibleCount * slideSize + (visibleCount - 1) * gap;
    if (direction === "vertical") {
      track.style.height = `${newSize}px`;
      track.style.maxHeight = `${newSize}px`;
    } else {
      void track.offsetWidth;
      track.style.width = `${newSize}px`;
      track.style.maxWidth = `${newSize}px`;
    }
    track.style.overflow = "";
    return true;
  }

  // ✅ Вспомогательная: выравнивание до ближайшего слайда
  function snapToNearestSlide() {
    const direction = getScrollDirection();
    const slides = Array.from(track.querySelectorAll("img"));
    if (slides.length === 0) return;

    const slideSize = getSlideSize();
    const gap = getGap();

    // Текущая позиция прокрутки
    const currentScroll =
      direction === "vertical" ? track.scrollTop : track.scrollLeft;

    // Вычисляем, какой слайд ближе всего к началу видимой области
    // Позиция начала i-го слайда = i * (slideSize + gap)
    let bestIndex = 0;
    let minDiff = Infinity;

    for (let i = 0; i < slides.length; i++) {
      const slideStart = i * (slideSize + gap);
      const diff = Math.abs(slideStart - currentScroll);
      if (diff < minDiff) {
        minDiff = diff;
        bestIndex = i;
      }
    }

    // Точная позиция прокрутки для этого слайда
    const targetScroll = bestIndex * (slideSize + gap);

    // Применяем
    if (direction === "vertical") {
      track.scrollTop = targetScroll;
    } else {
      track.scrollLeft = targetScroll;
    }
  }

  // ✅ Обновлённые функции прокрутки
  function scrollPrev() {
    const slideSize = getSlideSize();
    const gap = getGap();
    const direction = getScrollDirection();
    const scrollAmount = slideSize + gap;

    if (direction === "vertical") {
      track.scrollTop = Math.max(0, track.scrollTop - scrollAmount);
    } else {
      track.scrollLeft = Math.max(0, track.scrollLeft - scrollAmount);
    }

    setTimeout(snapToNearestSlide, 500);
  }

  function scrollNext() {
    const slideSize = getSlideSize();
    const gap = getGap();
    const direction = getScrollDirection();
    const scrollAmount = slideSize + gap;

    if (direction === "vertical") {
      track.scrollTop += scrollAmount;
    } else {
      track.scrollLeft += scrollAmount;
    }

    setTimeout(snapToNearestSlide, 500);
  }

  function updateButtons() {
    const direction = getScrollDirection();
    const max =
      direction === "vertical"
        ? track.scrollHeight - track.clientHeight
        : track.scrollWidth - track.clientWidth;
    const current =
      direction === "vertical" ? track.scrollTop : track.scrollLeft;

    const atStart = current <= 1;
    const atEnd = current >= max - 1;

    btnPrev.disabled = atStart;
    btnNext.disabled = atEnd;
    btnPrev.classList.toggle("opacity-50", atStart);
    btnNext.classList.toggle("opacity-50", atEnd);
  }

  function isSlideAtStart(slide) {
    const direction = getScrollDirection();
    if (direction === "vertical") {
      return slide.offsetTop <= track.scrollTop + 1;
    } else {
      return slide.offsetLeft <= track.scrollLeft + 1;
    }
  }

  function isSlideAtEnd(slide) {
    const direction = getScrollDirection();
    if (direction === "vertical") {
      return (
        slide.offsetTop + slide.offsetHeight >=
        track.scrollTop + track.clientHeight - 1
      );
    } else {
      return (
        slide.offsetLeft + slide.offsetWidth >=
        track.scrollLeft + track.clientWidth - 1
      );
    }
  }

  function handleSlideClick(e) {
    // Получаем позицию клика ОТНОСИТЕЛЬНО ВИДИМОЙ ОБЛАСТИ ТРЕКА
    const trackRect = track.getBoundingClientRect();
    const clickInTrack = e.clientX - trackRect.left;

    const direction = getScrollDirection();
    if (direction === "vertical") {
      // Вертикальный режим — пока не трогаем
      const clickY = e.clientY - trackRect.top;
      const atTop = clickY < track.clientHeight * 0.3;
      const atBottom = clickY > track.clientHeight * 0.7;

      if (atTop && track.scrollTop > 1) {
        scrollPrev();
      } else if (
        atBottom &&
        track.scrollTop < track.scrollHeight - track.clientHeight - 1
      ) {
        scrollNext();
      }
      return;
    }

    // Горизонтальный режим
    const atLeftEdge = clickInTrack < track.clientWidth * 0.3;
    const atRightEdge = clickInTrack > track.clientWidth * 0.7;

    if (atLeftEdge && track.scrollLeft > 1) {
      scrollPrev();
    } else if (
      atRightEdge &&
      track.scrollLeft < track.scrollWidth - track.clientWidth - 1
    ) {
      scrollNext();
    }
  }

  function init() {
    // Сразу скрываем трек, если ещё не скрыт
    track.classList.remove("visible");

    const slides = track.querySelectorAll("img");
    if (slides.length === 0) {
      track.classList.add("visible");
      return;
    }

    let loadedCount = 0;
    const total = slides.length;

    const finalize = () => {
      requestAnimationFrame(() => {
        // Принудительный reflow
        void track.offsetWidth;

        // Устанавливаем точный размер под N слайдов
        const success = fitToFullSlides();
        updateButtons();

        // Показываем трек
        track.classList.add("visible");
      });
    };

    const checkAllLoaded = () => {
      loadedCount++;
      if (loadedCount >= total) {
        finalize();
      }
    };

    slides.forEach((slide) => {
      slide.style.cursor = "pointer";
      slide.addEventListener("click", handleSlideClick);

      if (slide.complete && slide.naturalHeight !== 0) {
        checkAllLoaded();
      } else {
        slide.addEventListener("load", checkAllLoaded);
        slide.addEventListener("error", checkAllLoaded);
      }
    });
  }

  btnPrev.addEventListener("click", (e) => {
    e.preventDefault();
    scrollPrev();
  });

  btnNext.addEventListener("click", (e) => {
    e.preventDefault();
    scrollNext();
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
      snapToNearestSlide(); // выравниваем после свайпа
    }, 50);
  });

  track.addEventListener("scroll", updateButtons);
  let resizeTimer;
  window.addEventListener("resize", () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => {
      // 👇 Обновляем ВЫСОТУ КОНТЕЙНЕРА под новую высоту главного изображения
      if (window.innerWidth >= 1024) {
        updateThumbnailSliderHeight(); // ← это сбросит container.style.height
      }

      // Принудительный reflow
      void container.offsetWidth;

      // Теперь пересчитываем слайды
      fitToFullSlides();
      updateButtons();
    }, 100);
  });

  window.addEventListener("load", () => {
    fitToFullSlides();
    updateButtons();
  });

  if (window.innerWidth >= 1024) {
    updateThumbnailSliderHeight();
  }
  init();
});
