export function setItem(key, value) {
  try {
    localStorage.setItem(key, value);
  } catch (error) {}
}

export function getItem(key) {
  try {
    const item = localStorage.getItem(key);
    return item === "false" ? false : item;
  } catch (error) {
    return null;
  }
}

export function removeItem(key) {
  try {
    localStorage.removeItem(key);
  } catch (error) {}
}

export function clear() {
  try {
    localStorage.clear();
  } catch (error) {}
}

export function removeItemsByKey(key) {
  try {
    Object.keys(localStorage).forEach(item => {
      if (item.includes(key)) {
        localStorage.removeItem(item);
      }
    });
  } catch (error) {}
}
