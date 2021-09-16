import "./Label.less";

import React from "react";
import { classNames as cn, ucfirst } from "src/utils";

export default props => {
  return (
    <span
      onClick={props.onClick}
      className={cn("Label", props.type && props.type.toLowerCase(), {
        haveAction: !!props.onClick
      })}
    >
      {props.title || ucfirst(props.type)}
    </span>
  );
};
