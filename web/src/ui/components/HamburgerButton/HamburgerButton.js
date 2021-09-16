import "./HamburgerButton.less";

import React from "react";
import { classNames as cn } from "src/utils";
import SVG from "react-inlinesvg";

export default props => {
  return (
    <div
      onClick={props.onClick}
      className={cn("HamburgerButton", props.className, {
        active: props.active
      })}
    >
      <div className="HamburgerButton__button">
        <SVG src={require("src/asset/24px/hamburger_button.svg")} />
      </div>
      <div className="HamburgerButton__button">
        <SVG src={require("src/asset/24px/hamburger_button_close.svg")} />
      </div>
    </div>
  );
};
