import "./Tooltip.less";

import React from "react";
import PropTypes from "prop-types";
import { classNames as cn } from "../../utils/index";

const Tooltip = props => {
  return (
    <div
      className={cn("Tooltip__wrapper", props.className, props.place, {
        active: props.active,
        disableHover: props.disableHover
      })}
    >
      {props.title && (
        <div className={cn("Tooltip", props.size)}>
          <div className="Tooltip__area" />
          {props.title}
        </div>
      )}
      {props.children}
    </div>
  );
};

Tooltip.defaultProps = {
  place: "bottom",
  size: "medium"
};

Tooltip.propTypes = {
  title: PropTypes.string,
  place: PropTypes.oneOf(["top", "right", "bottom", "left"]), // TODO: add Auto
  size: PropTypes.oneOf(["small", "medium", "large"])
};

export default Tooltip;
