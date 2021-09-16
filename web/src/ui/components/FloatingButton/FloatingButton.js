import "./FloatingButton.less";
import React, { useState } from "react";
import SVG from "react-inlinesvg";
import * as utils from "../../utils/index";

export default function FloatingButton(props) {
  const [opened, open] = useState(false);
  return (
    <div>
      <div
        className={utils.classNames({
          FloatingButton__back: props.wrapper,
          opened,
          static: props.static
        })}
        onClick={() => open(false)}
      />
      <div
        className={utils.classNames("FloatingButton", {
          opened,
          static: props.static
        })}
      >
        <div className="FloatingButton__menu" onClick={() => open(false)}>
          {opened && props.children}
        </div>
        <div
          className="FloatingButton__button"
          onClick={() => {
            open(!opened);
          }}
        >
          <SVG className="FloatingButton__button__icon" src={props.icon} />
          <SVG
            className="FloatingButton__button__close"
            src={require("../../asset/close-large.svg")}
          />
        </div>
      </div>
    </div>
  );
}

export function FloatingButtonItem(props) {
  return (
    <div
      onClick={e => {
        props.onClick && props.onClick(e);
      }}
      className="FloatingButton__menu__item"
    >
      <span>{props.children}</span>
      <SVG src={props.icon} />
    </div>
  );
}
