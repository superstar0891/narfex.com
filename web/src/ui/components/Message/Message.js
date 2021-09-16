// styles
import "./Message.less";
// external
import React from "react";
import PropTypes from "prop-types";
// internal
import { classNames as cn } from "../../utils";

export default function Message(props) {
  const isAlert = props.alert;

  return (
    <div
      className={cn("Message", {
        [props.type]: !!props.type,
        alert: isAlert
      })}
    >
      {props.title && <div className="Message__title">{props.title}</div>}
      <div className="Message__content">
        {isAlert && <div className="Message__icon" />}
        <div className="Message__label">{props.children}</div>
        {isAlert && <div className="Message__close" onClick={props.onHide} />}
      </div>
    </div>
  );
}

Message.propTypes = {
  type: PropTypes.oneOf(["error", "warning", "success"]),
  alert: PropTypes.bool,
  title: PropTypes.node,
  onHide: PropTypes.func
};
