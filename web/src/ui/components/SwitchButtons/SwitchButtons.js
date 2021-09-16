// styles
import "./SwitchButtons.less";
// external
import React from "react";
import PropTypes from "prop-types";
// internal
import * as UI from "../../index";
import * as utils from "../../utils";

const SwitchButtons = props => {
  return (
    <div className={utils.classNames("SwitchButtons", props.className)}>
      {props.tabs.map(tab => (
        <UI.Button
          className={tab.className}
          title={tab.icon && tab.label}
          key={tab.value}
          size="ultra_small"
          disabled={tab.disabled}
          rounded={props.rounded}
          type={tab.value !== props.selected ? "secondary" : "normal"}
          onClick={() => props.onChange(tab.value)}
        >
          {tab.icon || tab.label}
        </UI.Button>
      ))}
    </div>
  );
};

SwitchButtons.propTypes = {
  tabs: PropTypes.arrayOf(
    PropTypes.shape({
      value: PropTypes.any,
      label: PropTypes.string
    })
  ).isRequired,
  selected: PropTypes.any,
  currency: PropTypes.string,
  className: PropTypes.string,
  rounded: PropTypes.bool,
  onChange: PropTypes.func.isRequired
};

export default SwitchButtons;
