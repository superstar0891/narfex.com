import "./Filter.less";

import React from "react";
import SVG from "react-inlinesvg";

export default props => (
  <div className="Filter">
    <div className="Filter__name">{props.name}</div>
    <div className="Filter__value">
      {typeof props.value === "object" ? props.value.join("; ") : props.value}
    </div>
    <div className="Filter__cancel" onClick={props.onCancel}>
      <SVG src={require("../../../asset/24px/close-xs.svg")} />
    </div>
  </div>
);
