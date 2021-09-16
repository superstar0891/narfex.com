// styles
import "./Switch.less";
// external
import React from "react";
import PropTypes from "prop-types";
// internal
import { classNames } from "../../utils";

function Switch(props) {
  const className = classNames({
    Switch: true,
    on: props.on,
    disabled: props.disabled
  });

  const handleChange = e => {
    props.onChange && props.onChange(!props.on, e);
  };

  const label = props.children || props.label;

  return (
    <div className={className} onClick={handleChange}>
      <div className="Switch__control">
        <div className="Switch__indicator" />
      </div>
      {label && <div className="Switch__label">{label}</div>}
    </div>
  );
}

Switch.propTypes = {
  on: PropTypes.bool,
  onChange: PropTypes.func,
  disabled: PropTypes.bool
};

export default React.memo(Switch);
