import "./Wrapper.less";
import React from "react";

export default props => {
  return (
    <div className="Wrapper">
      <div className="Wrapper__title">{props.title}</div>
      <div>{props.children}</div>
    </div>
  );
};
