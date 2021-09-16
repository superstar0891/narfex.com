export function open(url) {
  const width = 700;
  const height = 600;
  const { screen } = window;
  const left = screen.width / 2 - width / 2;
  const top = screen.height / 2 - height / 2;

  return new Promise((resolve, reject) => {
    const ref = window.open(
      url,
      "_blank",
      [
        ["toolbar", "yes"],
        ["scrollbars", "yes"],
        ["resizable", "yes"],
        ["top", top],
        ["left", left],
        ["width", width],
        ["height", height]
      ]
        .map(i => i.join("="))
        .join(",")
    );

    if (!ref) {
      window.location.href = url;
    }

    const interval = setInterval(() => {
      try {
        if (ref.closed) {
          clearInterval(interval);
          reject();
        }
      } catch (e) {}

      try {
        if (ref.window.location.origin === window.location.origin) {
          if (ref.window.location.pathname.split("/").pop() === "success") {
            ref.close();
            clearInterval(interval);
            resolve();
          } else {
            ref.close();
            clearInterval(interval);
            reject();
          }
        }
      } catch (e) {}
    }, 500);
  });
}

window.openMerchant = open;
