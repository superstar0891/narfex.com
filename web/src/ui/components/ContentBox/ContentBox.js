import "./ContentBox.less";
import React from "react";
import { classNames as cn } from "../../utils/index";

export default props => (
  <div {...props} title={null} className={cn("ContentBox", props.className)}>
    <div className="ContentBox__content">{props.children}</div>
  </div>
);
