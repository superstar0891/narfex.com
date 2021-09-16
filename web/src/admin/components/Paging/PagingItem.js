import React from "react";
import action from "../../../actions/admin";

export default props => (
  <div
    className="Paging__item"
    onClick={() => {
      props.params.action && action(props.params.action);
    }}
  >
    {props.text}
  </div>
);
