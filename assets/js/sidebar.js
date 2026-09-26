document.addEventListener('DOMContentLoaded', () => {
  const mobileBtn = document.querySelector('.mobile-menu-btn');
  const sidebar   = document.querySelector('.sidebar');
  const overlay   = document.getElementById('sidebarOverlay');

  if (mobileBtn && sidebar && overlay) {
    mobileBtn.addEventListener('click', () => {
      sidebar.classList.add('active');
      overlay.classList.add('active');
    });

    overlay.addEventListener('click', () => {
      sidebar.classList.remove('active');
      overlay.classList.remove('active');
    });
  }
});
