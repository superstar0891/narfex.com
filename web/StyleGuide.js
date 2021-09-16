let theme = 'dark';

const toggleTheme = () => {
  document.body.classList.remove(['theme', theme].join('-'));
  theme = theme === 'light' ? 'dark' : 'light';
  document.body.classList.add(['theme', theme].join('-'));
};

toggleTheme();

document.addEventListener("keydown", function(e) {
  if (e.code === 'ShiftRight') {
    toggleTheme();
  }
}, false);
