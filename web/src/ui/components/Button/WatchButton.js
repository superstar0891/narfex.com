import "./Button.less";

import React from "react";
import PropTypes from "prop-types";

function WatchButton(props) {
  return (
    <div
      className="WatchButton"
      onClick={() => props.onClick && props.onClick()}
      style={props.style}
    >
      <div className="WatchButton__icon" />
      <div className="WatchButton__label">{props.children}</div>
    </div>
  );
}

WatchButton.propTypes = {
  onClick: PropTypes.func,
  style: PropTypes.object
};

export default React.memo(WatchButton);
