import React from "react";
import Highlight from "react-highlight.js";
import { classNames as cn } from "../../utils";

import "./Code.less";

const Code = props => {
  if (props.simple) {
    return (
      <span className={cn("Code", "Code--simple", props.className)}>
        {props.children}
      </span>
    );
  }
  return (
    <Highlight className={cn("Code", props.className)} language={props.lang}>
      {props.children}
    </Highlight>
  );
};

export default React.memo(Code);
