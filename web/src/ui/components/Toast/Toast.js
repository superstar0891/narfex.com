// styles
import "./Toast.less";
// external
import React from "react";
import SVG from "react-inlinesvg";
import PropTypes from "prop-types";
// internal
import { classNames } from "../../utils";

export default function Toast(props) {
  return (
    <div
      onMouseOver={props.onMouseOver}
      onMouseLeave={props.onMouseLeave}
      className={classNames("Toast", props.type, { hidden: props.hidden })}
    >
      <div className="Toast__icon">
        <SVG
          src={
            props.type === "success"
              ? require("../../asset/check_24.svg")
              : require("../../asset/warning.svg")
          }
        />
      </div>
      <div>{props.message}</div>
      <div onClick={props.onClose} className="Toast__close">
        <SVG src={require("../../asset/close.svg")} />
      </div>
    </div>
  );
}
Toast.propTypes = {
  type: PropTypes.string,
  hidden: PropTypes.bool,
  message: PropTypes.string,
  onClose: PropTypes.func
};
