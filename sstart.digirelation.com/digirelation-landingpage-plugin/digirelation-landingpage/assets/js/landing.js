(function () {
  function initReveal() {
    const items = document.querySelectorAll('.digi-lp .reveal');
    if (!('IntersectionObserver' in window)) {
      items.forEach(el => el.classList.add('in'));
      return;
    }
    const io = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('in');
          io.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12 });
    items.forEach((el, index) => {
      el.style.transitionDelay = (index % 3) * 0.08 + 's';
      io.observe(el);
    });
  }

  // Delegation remains reliable when WP Rocket delays or reorders the script.
  document.addEventListener('click', event => {
    const toggle = event.target.closest('#menuToggle');
    if (toggle) {
      const links = document.getElementById('navLinks');
      if (links) links.classList.toggle('mobile-open');
      return;
    }

    const menuLink = event.target.closest('#navLinks a');
    if (menuLink) {
      const links = document.getElementById('navLinks');
      if (links) links.classList.remove('mobile-open');
    }

    const button = event.target.closest('.digi-lp .faq-q');
    if (!button) return;
    const item = button.closest('.faq-item');
    const answer = item ? item.querySelector('.faq-a') : null;
    if (!item || !answer) return;
    const open = item.classList.toggle('open');
    button.setAttribute('aria-expanded', open ? 'true' : 'false');
    answer.style.maxHeight = open ? answer.scrollHeight + 'px' : '';
  });

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initReveal, { once: true });
  } else {
    initReveal();
  }
}());
