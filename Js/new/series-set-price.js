/**
 * Рассчёт и отображение сумарной цены комплекта серии
 */
function seriesSetPrice() {
  //Test DDD
  const setBlock = document.getElementById('series-set');
  if (!setBlock) return;

  let total = 0;
  let totalOld = 0;

  // Проходим по всем строкам товаров
  document.querySelectorAll('#series-set .arproducts > div[id^="series-set-row-"]').forEach(row => {
    const idMatch = row.id.match(/series-set-row-(\d+)/);
    if (!idMatch) return;
    const itemId = idMatch[1];

    const amountInput = document.getElementById(`item2-${itemId}-amount`);
    const priceSpan = document.getElementById(`item2-${itemId}-price`);
    const priceOldEl = document.getElementById(`item2-${itemId}-price-old`);

    if (!amountInput || !priceSpan) return;

    const amount = parseInt(amountInput.value) || 0;

    // Парсим текущую цену
    const currentPriceText = priceSpan.textContent.trim();
    const currentPrice = parseFloat(currentPriceText.replace(/[^\d,.-]/g, '').replace(',', '.')) || 0;

    // Парсим старую цену (если есть)
    let oldPrice = currentPrice;
    if (priceOldEl) {
      const oldPriceText = priceOldEl.textContent.trim();
      oldPrice = parseFloat(oldPriceText.replace(/[^\d,.-]/g, '').replace(',', '.')) || 0;
    }

    total += currentPrice * amount;
    totalOld += oldPrice * amount;
  });

  // Обновляем итоговую цену
  const totalEl = document.getElementById('series-set-price');
  if (totalEl) {
    totalEl.textContent = total.toLocaleString('ru-RU') + ' ₽';
    totalEl.className = 'text-3xl font-bold text-black';
  }

  // Обновляем зачёркнутую цену
  const totalOldEl = document.getElementById('series-set-price-old');
  if (totalOldEl) {
    if (totalOld > total) {
      totalOldEl.style.display = '';
      totalOldEl.textContent = totalOld.toLocaleString('ru-RU') + ' ₽';
    } else {
      totalOldEl.style.display = 'none';
    }
  }
}

// Вызов при загрузке
document.addEventListener('DOMContentLoaded', seriesSetPrice);
