import { useEffect } from "react";
import { throttle2 } from "src/utils";

export default onScroll => {
  function handleDocumentScroll() {
    const { scrollTop } = document.documentElement || document.body;
    onScroll(scrollTop);
  }

  const handleDocumentScrollThrottled = throttle2(handleDocumentScroll, 300);

  useEffect(() => {
    onScroll(0);
    window.addEventListener("scroll", handleDocumentScrollThrottled);
    return () =>
      window.removeEventListener("scroll", handleDocumentScrollThrottled);
    // eslint-disable-next-line
  }, []);
};
