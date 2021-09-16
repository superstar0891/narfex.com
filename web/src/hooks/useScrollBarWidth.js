import { useState, useEffect } from "react";

export default () => {
  const [scrollbarWidth, setScrollbarWidth] = useState(0);

  useEffect(() => {
    const outer = document.createElement("div");
    outer.style.visibility = "hidden";
    outer.style.overflow = "scroll";
    outer.style.msOverflowStyle = "scrollbar";
    document.body.appendChild(outer);

    const inner = document.createElement("div");
    outer.appendChild(inner);

    const scrollWidth = outer.offsetWidth - inner.offsetWidth;
    document.documentElement.style.setProperty(
      "--scroll-width",
      scrollWidth + "px"
    );
    setScrollbarWidth(scrollWidth);

    outer.parentNode.removeChild(outer);
  }, []);

  return scrollbarWidth;
};
