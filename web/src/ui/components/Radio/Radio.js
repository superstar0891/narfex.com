// styles
import "./Radio.less";
// external
import React from "react";
import PropTypes from "prop-types";
// internal
import { classNames } from "../../utils";

function Radio(props) {
  const className = classNames({
    Radio: true,
    selected: props.selected,
    disabled: props.disabled
  });

  return (
    <div
      className={className}
      onClick={() => props.onChange && props.onChange(props.value)}
    >
      <div className="Radio__indicator"></div>
      <div className="Radio__label">{props.children}</div>
    </div>
  );
}

Radio.propTypes = {
  selected: PropTypes.bool,
  onChange: PropTypes.func,
  disabled: PropTypes.bool
};

export default React.memo(Radio);
