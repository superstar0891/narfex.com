// styles
import "./Button.less";
// external
import React, { memo } from "react";
import PropTypes from "prop-types";
// internal
import { classNames } from "../../utils";
import SVG from "react-inlinesvg";

const ButtonWrapper = props => (
  <div className={classNames("ButtonWrapper", props.className, props.align)}>
    {props.children}
  </div>
);

const Button = memo(props => {
  const className = classNames(
    "Button",
    props.className,
    props.type,
    props.size,
    props.newClass,
    props.state,
    {
      disabled: props.disabled || props.state === "disabled",
      forCabinet: props.forCabinet,
      smallPadding: props.smallPadding
    }
  );

  const fillStyle = {};

  if (props.type === "normal") {
    fillStyle.color = "white";
  }

  return (
    <button
      className={className}
      onClick={e => props.onClick && props.onClick(e)}
      style={{ ...fillStyle, ...props.style }}
      type={props.btnType}
      title={props.title}
    >
      {props.state === "loading" && (
        <div className="Button__loader">
          <SVG src={require(`../../asset/spinner.svg`)} />
        </div>
      )}
      <div className="Button__cont">
        {props.beforeContent}
        <div
          className="Button__label"
          style={props.fontSize ? { fontSize: props.fontSize } : {}}
        >
          {props.children}
        </div>
        {props.afterContent}
      </div>
    </button>
  );
});

Button.defaultProps = {
  type: "default",
  size: "large",
  btnType: "button",
  currency: {}
};

Button.propTypes = {
  size: PropTypes.oneOf([
    "middle",
    "small",
    "large",
    "extra_large",
    "ultra_small"
  ]),
  type: PropTypes.oneOf([
    "normal",
    "secondary",
    "negative",
    "sell",
    "buy",
    "danger",
    "success",
    "primary"
  ]),
  currency: PropTypes.object,
  className: PropTypes.string,
  btnType: PropTypes.string,
  disabled: PropTypes.bool,
  onClick: PropTypes.func,
  style: PropTypes.object,
  beforeContent: PropTypes.node,
  afterContent: PropTypes.node,
  smallPadding: PropTypes.bool,
  title: PropTypes.string,
  state: PropTypes.oneOf(["default", "loading", "disabled", ""])
};

export default React.memo(Button);

ButtonWrapper.propTypes = {
  align: PropTypes.oneOf(["left", "center", "right", "fill"])
};

export { ButtonWrapper };
