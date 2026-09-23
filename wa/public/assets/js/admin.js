document.addEventListener('DOMContentLoaded', () => {
  const toggle = document.getElementById('navToggle');
  const nav = document.getElementById('megaNav');
  if (toggle && nav) {
    toggle.addEventListener('click', () => nav.classList.toggle('open'));
  }
  document.querySelectorAll('.has-mega > .nav-link').forEach((btn) => {
    btn.addEventListener('click', (e) => {
      if (window.matchMedia('(max-width: 900px)').matches) {
        e.preventDefault();
        btn.parentElement.classList.toggle('open');
      }
    });
  });
  document.querySelectorAll('[data-confirm]').forEach((el) => {
    el.addEventListener('click', (e) => {
      const msg = el.getAttribute('data-confirm') || 'Are you sure?';
      if (!window.confirm(msg)) {
        e.preventDefault();
      }
    });
  });
});
