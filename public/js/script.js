const toggle = document.querySelector('.nav-toggle');
const menu = document.querySelector('.nav-menu');

if (toggle && menu) {
    toggle.addEventListener('click', () => menu.classList.toggle('open'));
}
