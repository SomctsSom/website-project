document.documentElement.classList.add('js');

window.addEventListener('DOMContentLoaded', () => {
  initHeroSlider();
  initTestimonialsSlider();
  initSiteNav();
  initScrollHeader();
});

function initScrollHeader() {
  const topbar = document.querySelector('.site-topbar');
  const header = document.querySelector('.site-header');
  if (!topbar || !header) {
    return;
  }

  const threshold = 48;
  let ticking = false;

  const update = () => {
    ticking = false;
    const scrolled = window.scrollY > threshold;
    document.body.classList.toggle('is-scrolled', scrolled);
  };

  const onScroll = () => {
    if (ticking) {
      return;
    }
    ticking = true;
    window.requestAnimationFrame(update);
  };

  update();
  window.addEventListener('scroll', onScroll, { passive: true });
}

function initSiteNav() {
  const root = document.querySelector('[data-site-nav]');
  if (!root) {
    return;
  }

  const parents = Array.from(root.querySelectorAll('.nav-item.has-children'));

  const closeAll = (except = null) => {
    parents.forEach((item) => {
      if (except && item === except) {
        return;
      }
      item.classList.remove('is-open');
      const link = item.querySelector(':scope > .nav-link');
      if (link) {
        link.setAttribute('aria-expanded', 'false');
      }
    });
  };

  parents.forEach((item) => {
    const link = item.querySelector(':scope > .nav-link');
    if (!link) {
      return;
    }

    link.addEventListener('click', (event) => {
      // Click parent with children: toggle submenu (professional click pattern)
      event.preventDefault();
      const willOpen = !item.classList.contains('is-open');
      closeAll();
      if (willOpen) {
        item.classList.add('is-open');
        link.setAttribute('aria-expanded', 'true');
      }
    });
  });

  document.addEventListener('click', (event) => {
    if (!(event.target instanceof Element) || root.contains(event.target)) {
      return;
    }
    closeAll();
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      closeAll();
    }
  });
}

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

function initTestimonialsSlider() {
  const root = document.querySelector('[data-testimonials-slider]');
  if (!root) {
    return;
  }

  const slides = Array.from(root.querySelectorAll('[data-testimonial-slide]'));
  const dots = Array.from(root.querySelectorAll('[data-testimonial-dot]'));
  const prevBtn = root.querySelector('[data-testimonial-prev]');
  const nextBtn = root.querySelector('[data-testimonial-next]');
  if (slides.length === 0) {
    return;
  }

  let index = 0;
  let timer = null;
  const autoplayMs = 5500;

  const show = (nextIndex) => {
    index = (nextIndex + slides.length) % slides.length;
    slides.forEach((slide, i) => {
      const active = i === index;
      slide.classList.toggle('is-active', active);
      if (active) {
        slide.removeAttribute('hidden');
        slide.setAttribute('aria-hidden', 'false');
        slide.style.opacity = '0';
        slide.style.transform = 'translateX(18px)';
        requestAnimationFrame(() => {
          slide.style.transition = 'opacity .5s ease, transform .5s ease';
          slide.style.opacity = '1';
          slide.style.transform = 'none';
        });
      } else {
        slide.setAttribute('hidden', '');
        slide.setAttribute('aria-hidden', 'true');
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
      show(Number(dot.getAttribute('data-testimonial-dot') || 0));
      start();
    });
  });

  root.addEventListener('mouseenter', stop);
  root.addEventListener('mouseleave', start);
  root.addEventListener('focusin', stop);
  root.addEventListener('focusout', start);

  show(0);
  start();
}
