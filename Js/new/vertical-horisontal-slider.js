/**
 * Оптимизированный слайдер миниатюр
 * - Производительность: RAF-троттлинг, кэширование, passive listeners, батчинг стилей
 * - Функционал: вертикаль/горизонталь, свайп, snap, синхронизация высоты, клик по краям
 */
(function initThumbnailSlider() {
    'use strict';

    // 🔥 Инъекция стилей (один раз, без layout thrashing)
    const STYLES = `
    .slider-track::-webkit-scrollbar { display: none; }
    .slider-track {
      scrollbar-width: none;
      -ms-overflow-style: none;
      opacity: 0;
      transition: opacity 0.2s ease;
      will-change: scroll-position;
      scroll-snap-type: y mandatory;
    }
    .slider-track.visible { opacity: 1; }
    .slider-track.smooth { scroll-behavior: smooth; }
    .slider-track.auto { scroll-behavior: auto; }
  `;
    document.head.appendChild(Object.assign(document.createElement('style'), { textContent: STYLES }));

    document.addEventListener('DOMContentLoaded', () => {
        const container = document.querySelector('.slider-thumbnails-container > .relative');
        if (!container) return;

        const track = container.querySelector('.slider-track');
        const btnPrev = container.querySelector('.slider-prev');
        const btnNext = container.querySelector('.slider-next');
        const mainImage = document.getElementById('main-product-image');
        if (!track || !btnPrev || !btnNext) return;

        // 🎯 Кэш для минимизации DOM-чтений
        const cache = {
            slides: [],
            slideSize: 0,
            gap: 8,
            direction: null,
            isDesktop: window.innerWidth >= 1024,
            containerSize: 0,
            visibleIndices: [],
            scrollLocked: false,
            // 🔥 Кэш для getComputedStyle
            computedStyle: null
        };

        // ─── Вспомогательные функции (с кэшированием) ───
        const getComputedStyle = () => {
            if (!cache.computedStyle) cache.computedStyle = window.getComputedStyle(track);
            return cache.computedStyle;
        };

        const getDirection = () => {
            if (cache.direction) return cache.direction;
            cache.direction = getComputedStyle().flexDirection.includes('column') ? 'vertical' : 'horizontal';
            return cache.direction;
        };

        const invalidateDirectionCache = () => {
            cache.direction = null;
            cache.computedStyle = null;
        };

        const getSlideSize = () => {
            if (cache.slideSize > 0) return cache.slideSize;
            const first = track.querySelector('img');
            if (!first) return 0;
            cache.slideSize = getDirection() === 'vertical' ? first.offsetHeight : first.offsetWidth;
            return cache.slideSize;
        };

        const getGap = () => {
            const dir = getDirection();
            const styles = getComputedStyle();
            return parseInt(styles[dir === 'vertical' ? 'rowGap' : 'columnGap'], 10) || 8;
        };

        const getCurrentScroll = () => getDirection() === 'vertical' ? track.scrollTop : track.scrollLeft;

        const getMaxScroll = () => {
            const dir = getDirection();
            return dir === 'vertical'
                ? track.scrollHeight - track.clientHeight
                : track.scrollWidth - track.clientWidth;
        };

        const setScroll = (v) => {
            if (getDirection() === 'vertical') track.scrollTop = v;
            else track.scrollLeft = v;
        };

        // ─── Кэширование видимых индексов ───
        function updateVisibleIndicesCache() {
            const dir = getDirection();
            cache.containerSize = dir === 'vertical' ? track.clientHeight : track.clientWidth;

            if (!cache.slides.length) { cache.visibleIndices = []; return; }

            const scrollPos = getCurrentScroll();
            const cSize = cache.containerSize;
            const sSize = getSlideSize();

            if (sSize <= 0) { cache.visibleIndices = []; return; }

            cache.visibleIndices = [];
            // 🔥 Один цикл вместо нескольких проходов
            for (let i = 0; i < cache.slides.length; i++) {
                const s = cache.slides[i];
                const start = dir === 'vertical' ? s.offsetTop : s.offsetLeft;
                if (start < scrollPos + cSize && start + sSize > scrollPos) {
                    cache.visibleIndices.push(i);
                }
            }
        }

        // ─── Обновление кнопок (RAF-троттлинг) ───
        let buttonsUpdatePending = false;
        function updateButtons() {
            if (buttonsUpdatePending) return;
            buttonsUpdatePending = true;

            requestAnimationFrame(() => {
                const max = getMaxScroll();
                const current = getCurrentScroll();
                const atStart = current <= 1;
                const atEnd = current >= max - 1;

                // 🔥 Батчим изменения атрибутов и классов
                btnPrev.disabled = atStart;
                btnNext.disabled = atEnd;
                btnPrev.classList.toggle('opacity-50', atStart);
                btnNext.classList.toggle('opacity-50', atEnd);

                updateVisibleIndicesCache();
                buttonsUpdatePending = false;
            });
        }

        // ─── Логика клика по миниатюре ───
        function isClickedSlideEdge(slide) {
            const idx = cache.slides.indexOf(slide);
            if (idx === -1) return false;
            if (cache.visibleIndices.length >= cache.slides.length) return false;
            if (cache.visibleIndices.length < 3) return false;
            if (!cache.visibleIndices.includes(idx)) return false;

            const edgeCount = 2;
            const visible = cache.visibleIndices;
            // 🔥 Прямая проверка вместо slice + includes
            for (let i = 0; i < edgeCount; i++) {
                if (visible[i] === idx || visible[visible.length - 1 - i] === idx) return true;
            }
            return false;
        }

        function handleSlideClick(e, slide) {
            e.stopPropagation();

            if (isClickedSlideEdge(slide)) {
                e.preventDefault();
                if (cache.containerSize > 0) {
                    const slidePos = getDirection() === 'vertical' ? slide.offsetTop : slide.offsetLeft;
                    const center = getCurrentScroll() + cache.containerSize / 2;
                    scrollBySlide(slidePos < center ? 'prev' : 'next');
                } else {
                    scrollBySlide('next');
                }
                return;
            }

            if (typeof window.changeMainImage === 'function') {
                window.changeMainImage(slide);
            }
        }

        // ─── Прокрутка и Snap (оптимизировано) ───
        function snapToNearestSlide() {
            const step = getSlideSize() + getGap();
            if (step <= 0) return;

            const current = getCurrentScroll();
            const target = Math.max(0, Math.min(Math.round(current / step) * step, getMaxScroll()));

            // 🔥 Меняем классы вместо inline-стилей
            track.classList.remove('auto');
            track.classList.add('smooth');
            setScroll(target);

            // 🔥 RAF вместо setTimeout для синхронизации с рендером
            requestAnimationFrame(() => {
                setTimeout(updateButtons, 250); // небольшая задержка после анимации
            });
        }

        function scrollBySlide(direction) {
            if (cache.scrollLocked) return;
            cache.scrollLocked = true;

            const step = getSlideSize() + getGap();
            const current = getCurrentScroll();
            const max = getMaxScroll();
            const target = direction === 'prev'
                ? Math.max(0, current - step)
                : Math.min(max, current + step);

            track.classList.remove('auto');
            track.classList.add('smooth');
            setScroll(target);

            // 🔥 Разблокировка после завершения анимации
            setTimeout(() => {
                cache.scrollLocked = false;
                updateButtons();
            }, 300);
        }

        // ─── Клик по пустой зоне трека ───
        function handleTrackClick(e) {
            if (e.target.tagName === 'IMG') return;

            const rect = track.getBoundingClientRect();
            const clickPos = getDirection() === 'vertical'
                ? e.clientY - rect.top
                : e.clientX - rect.left;
            const trackSize = getDirection() === 'vertical' ? rect.height : rect.width;
            const edgeZone = trackSize * 0.3;
            const current = getCurrentScroll();
            const max = getMaxScroll();

            if (clickPos < edgeZone && current > 1) scrollBySlide('prev');
            else if (clickPos > trackSize - edgeZone && current < max - 1) scrollBySlide('next');
        }

        // ─── Синхронизация высоты (Desktop) ───
        function syncHeightWithMainImage() {
            if (!cache.isDesktop || !mainImage) return;
            const h = mainImage.getBoundingClientRect().height;
            if (h <= 0) return;
            // 🔥 Один cssText вместо 5 отдельных присваиваний
            container.style.cssText = `height:${h}px;max-height:${h}px;width:80px;min-width:80px;max-width:80px;`;
        }

        // ─── Подгонка размера трека ───
        function fitTrackToSlides() {
            if (!cache.slides.length) return false;
            const dir = getDirection();
            const sSize = getSlideSize();
            const gap = getGap();
            if (!sSize) return false;

            cache.containerSize = dir === 'vertical' ? container.clientHeight : container.clientWidth;
            if (cache.containerSize <= 0) return false;

            // 🔥 Оптимизированный расчёт количества слайдов
            const step = sSize + gap;
            const count = Math.max(1, Math.floor((cache.containerSize + gap) / step));

            if (count >= cache.slides.length) {
                track.style.overflow = 'hidden';
                return false;
            }

            const newSize = count * sSize + (count - 1) * gap;
            // 🔥 Батчим изменения стилей
            Object.assign(track.style, dir === 'vertical'
                ? { height: `${newSize}px`, maxHeight: `${newSize}px` }
                : { width: `${newSize}px`, maxWidth: `${newSize}px` });
            track.style.overflow = '';
            return true;
        }

        // ─── Сброс при смене режима ───
        function resetDimensions() {
            // 🔥 Один цикл для сброса стилей
            const props = ['width','maxWidth','height','maxHeight','overflow'];
            for (const p of props) {
                track.style[p] = '';
                container.style[p] = '';
            }
            container.style.cssText = '';

            if (cache.isDesktop) syncHeightWithMainImage();
            else setScroll(0);

            // 🔥 Сброс кэша
            cache.slideSize = 0;
            cache.containerSize = 0;
            invalidateDirectionCache();
            cache.visibleIndices = [];
        }

        // ─── Resize (RAF-дебаунс) ───
        let resizeRaf = null;
        function onResize() {
            if (resizeRaf) return;
            resizeRaf = requestAnimationFrame(() => {
                const newDesktop = window.innerWidth >= 1024;
                if (newDesktop !== cache.isDesktop) {
                    cache.isDesktop = newDesktop;
                    invalidateDirectionCache();
                    resetDimensions();
                } else if (cache.isDesktop) {
                    syncHeightWithMainImage();
                }
                cache.slideSize = 0;
                fitTrackToSlides();
                updateButtons();
                resizeRaf = null;
            });
        }

        // ─── Инициализация слайдов ───
        function init() {
            cache.slides = Array.from(track.querySelectorAll('img'));

            if (!cache.slides.length) {
                track.classList.add('visible');
                return;
            }

            let loaded = 0;
            const total = cache.slides.length;

            const finalize = () => {
                requestAnimationFrame(() => {
                    // 🔥 Двойной reflow для гарантии актуальных размеров
                    void track.offsetWidth;
                    cache.slideSize = 0;
                    fitTrackToSlides();

                    void track.offsetHeight;
                    updateVisibleIndicesCache();
                    updateButtons();

                    track.classList.add('visible');
                });
            };

            const onReady = () => {
                if (++loaded >= total) finalize();
            };

            for (const slide of cache.slides) {
                slide.style.cursor = 'pointer';
                slide.addEventListener('click', e => handleSlideClick(e, slide));

                // 🔥 Надёжная проверка загрузки
                if (slide.complete && slide.naturalWidth > 0 && slide.naturalHeight > 0) {
                    onReady();
                } else {
                    slide.addEventListener('load', onReady, { once: true });
                    slide.addEventListener('error', onReady, { once: true });
                }
            }
        }

        // ─── Привязка событий ───
        btnPrev.addEventListener('click', e => { e.preventDefault(); scrollBySlide('prev'); });
        btnNext.addEventListener('click', e => { e.preventDefault(); scrollBySlide('next'); });
        track.addEventListener('click', handleTrackClick);
        track.addEventListener('scroll', updateButtons, { passive: true });

        track.addEventListener('touchstart', () => {
            track.classList.remove('smooth');
            track.classList.add('auto');
        }, { passive: true });

        track.addEventListener('touchend', () => {
            track.classList.remove('auto');
            track.classList.add('smooth');
            snapToNearestSlide();
        }, { passive: true });

        window.addEventListener('resize', onResize, { passive: true });
        window.addEventListener('load', () => {
            cache.slideSize = 0;
            fitTrackToSlides();
            updateButtons();
        });

        // Стартовая настройка
        if (cache.isDesktop) syncHeightWithMainImage();
        else resetDimensions();

        init();
        updateButtons();
    });
})();
