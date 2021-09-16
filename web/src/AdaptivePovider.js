import React, { useState, useEffect, useCallback, useRef, memo } from "react";
import { PHONE } from "./index/constants/breakpoints";
import { setAdaptive as setAdaptiveStore } from "./actions";
import { throttle } from "./utils";
import { useScrollBarWidth } from "./hooks";

const isAdaptive = () => window.innerWidth <= PHONE;

export const AdaptiveContext = React.createContext(isAdaptive());

export default memo(({ children }) => {
  const [adaptive, setAdaptive] = useState();
  useScrollBarWidth();

  const handleResize = useCallback(() => {
    const adaptive = isAdaptive();
    setAdaptive(adaptive);
    setAdaptiveStore(adaptive); // TODO HACK;
  }, []);

  const handleResizeThrottled = useRef(throttle(handleResize, 300));

  useEffect(() => {
    const handleResizeThrottledCurrent = handleResizeThrottled.current;
    handleResizeThrottledCurrent(); // TODO HACK
    window.addEventListener("resize", handleResizeThrottledCurrent);
    return () =>
      window.removeEventListener("resize", handleResizeThrottledCurrent);
  }, [handleResizeThrottled]);

  return (
    <AdaptiveContext.Provider value={adaptive}>
      {children}
    </AdaptiveContext.Provider>
  );
});
