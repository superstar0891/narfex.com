import "./Clipboard.less";

import React from "react";
import { classNames as cn } from "../../utils";
import { ReactComponent as CopyIcon } from "src/asset/24px/copy.svg";

export default props => {
  return (
    <div
      title={props.title}
      onClick={() => props.onClick(props.text)}
      className={cn("Clipboard", props.className)}
    >
      {props.displayText || props.text}
      {!props.skipIcon && (
        <div className="Clipboard__icon">
          <CopyIcon />
        </div>
      )}
    </div>
  );
};
