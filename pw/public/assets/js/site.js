document.documentElement.classList.add('js');

window.addEventListener('DOMContentLoaded', () => {
  initHeroSlider();
});

function initHeroSlider() {
  const root = document.querySelector('[data-hero-slider]');
  if (!root) {
    return;
  }

  const slides = Array.from(root.querySelectorAll('.hero-slide'));
  const dots = Array.from(root.querySelectorAll('[data-hero-dot]'));
  const prevBtn = root.querySelector('[data-hero-prev]');
  const nextBtn = root.querySelector('[data-hero-next]');
  if (slides.length === 0) {
    return;
  }

  let index = 0;
  let timer = null;
  const autoplayMs = 6000;

  const show = (nextIndex) => {
    index = (nextIndex + slides.length) % slides.length;
    slides.forEach((slide, i) => {
      const active = i === index;
      slide.classList.toggle('is-active', active);
      if (active) {
        slide.removeAttribute('hidden');
        slide.setAttribute('aria-hidden', 'false');
      } else {
        slide.setAttribute('hidden', '');
        slide.setAttribute('aria-hidden', 'true');
      }
      const copy = slide.querySelector('.hero-copy');
      if (copy && active) {
        copy.style.opacity = '0';
        copy.style.transform = 'translateY(12px)';
        requestAnimationFrame(() => {
          copy.style.transition = 'opacity .55s ease, transform .55s ease';
          copy.style.opacity = '1';
          copy.style.transform = 'none';
        });
      }
    });
    dots.forEach((dot, i) => {
      const active = i === index;
      dot.classList.toggle('is-active', active);
      dot.setAttribute('aria-selected', active ? 'true' : 'false');
    });
  };

  const next = () => show(index + 1);
  const prev = () => show(index - 1);

  const stop = () => {
    if (timer) {
      clearInterval(timer);
      timer = null;
    }
  };

  const start = () => {
    stop();
    if (slides.length < 2) {
      return;
    }
    timer = setInterval(next, autoplayMs);
  };

  if (prevBtn) {
    prevBtn.addEventListener('click', () => {
      prev();
      start();
    });
  }
  if (nextBtn) {
    nextBtn.addEventListener('click', () => {
      next();
      start();
    });
  }
  dots.forEach((dot) => {
    dot.addEventListener('click', () => {
      show(Number(dot.getAttribute('data-hero-dot') || 0));
      start();
    });
  });

  root.addEventListener('mouseenter', stop);
  root.addEventListener('mouseleave', start);
  root.addEventListener('focusin', stop);
  root.addEventListener('focusout', start);

  document.addEventListener('keydown', (event) => {
    if (!root.contains(document.activeElement) && document.activeElement !== document.body) {
      return;
    }
    if (event.key === 'ArrowRight') {
      next();
      start();
    } else if (event.key === 'ArrowLeft') {
      prev();
      start();
    }
  });

  show(0);
  start();
}
