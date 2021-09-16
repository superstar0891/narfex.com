import React, { useRef, useState } from "react";
import useDocumentScroll from "src/hooks/useDocumentScroll";
import { classNames as cn } from "src/utils";

export default props => {
  const wrapper = useRef(null);
  const [isVisible, setVisible] = useState();

  useDocumentScroll(() => {
    const rect = wrapper.current.getBoundingClientRect();
    if (!isVisible && rect.top < window.innerHeight) {
      setVisible(true);
    } else {
      // setVisible(false);
    }
  });

  return (
    <div
      className={cn(props.className, { isVisible, notVisible: !isVisible })}
      ref={wrapper}
    >
      {props.children}
    </div>
  );
};
