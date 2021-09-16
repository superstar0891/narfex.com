import "./Spoiler.less";

import React, { useState, useEffect, useRef } from "react";
import { classNames as cn } from "../../utils";

export default props => {
  const [height, setHeight] = useState(null);
  const [opened, setOpened] = useState(true);
  const [helper, setHelper] = useState(true);

  const ref = useRef();
  useEffect(() => {
    setHelper(true);
  }, [props.opened]);

  useEffect(() => {
    if (helper) {
      const { clientHeight } = ref.current;
      setHeight(clientHeight);
      setHelper(false);
    } else {
      setOpened(props.opened);
      setTimeout(() => {
        setHeight(null);
        console.log("animation End");
      }, 300);
    }
    // eslint-disable-next-line
  }, [helper]);

  return (
    <div
      ref={ref}
      className={cn("Spoiler", props.className, { opened: opened, helper })}
      style={{ height: height === null || helper ? "auto" : height + "px" }}
    >
      {props.children}
    </div>
  );
};
