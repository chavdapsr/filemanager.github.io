// Ripple effect for buttons
document.querySelectorAll('button').forEach(btn => {
  btn.addEventListener('click', function(e) {
    const ripple = document.createElement('span');
    ripple.className = 'ripple';
    this.appendChild(ripple);
    const rect = this.getBoundingClientRect();
    ripple.style.left = `${e.clientX - rect.left}px`;
    ripple.style.top = `${e.clientY - rect.top}px`;
    setTimeout(() => ripple.remove(), 600);
  });
});

button {
  position: relative;
  overflow: hidden;
}
.ripple {
  position: absolute;
  border-radius: 50%;
  background: rgba(0,0,0,0.2);
  transform: scale(0);
  animation: ripple 0.6s linear;
  pointer-events: none;
  width: 100px;
  height: 100px;
}
@keyframes ripple {
  to {
    transform: scale(2.5);
    opacity: 0;
  }
}

// Animated menu toggle
const menuBtn = document.getElementById('menu-btn');
const menu = document.getElementById('side-menu');
menuBtn.addEventListener('click', () => {
  menu.classList.toggle('open');
});

#side-menu {
  transition: transform 0.4s cubic-bezier(.68,-0.55,.27,1.55);
  transform: translateX(-100%);
}
#side-menu.open {
  transform: translateX(0);
}

.icon {
  transition: transform 0.2s;
}
.icon:hover {
  transform: scale(1.2) rotate(-10deg);
}

// Fade-in cards on scroll
const cards = document.querySelectorAll('.card');
const observer = new IntersectionObserver(entries => {
  entries.forEach(entry => {
    if (entry.isIntersecting) entry.target.classList.add('visible');
  });
});
cards.forEach(card => observer.observe(card));

.card {
  opacity: 0;
  transform: translateY(30px);
  transition: opacity 0.5s, transform 0.5s;
}
.card.visible {
  opacity: 1;
  transform: none;
}

.icon-btn:active {
  transform: scale(0.95);
  filter: brightness(0.9);
}
