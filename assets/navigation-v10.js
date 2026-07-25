(() => {
  const button = document.querySelector('[data-gn-menu-button]');
  const nav = document.querySelector('[data-gn-public-nav]');
  if (!button || !nav) return;

  const close = () => {
    nav.classList.remove('is-open');
    button.setAttribute('aria-expanded', 'false');
  };

  button.addEventListener('click', () => {
    const opening = !nav.classList.contains('is-open');
    nav.classList.toggle('is-open', opening);
    button.setAttribute('aria-expanded', String(opening));
  });

  nav.addEventListener('click', event => {
    if (event.target.closest('a')) close();
  });

  window.addEventListener('resize', () => {
    if (window.innerWidth > 1480) close();
  });

  document.addEventListener('click', event => {
    if (!event.target.closest('.gn-account')) {
      document.querySelectorAll('.gn-account[open]').forEach(item => item.removeAttribute('open'));
    }
  });
})();
