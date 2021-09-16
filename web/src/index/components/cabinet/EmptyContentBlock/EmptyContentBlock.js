import "./EmptyContentBlock.less";

import React from "react";
import PropTypes from "prop-types";
import * as UI from "../../../../ui";

import * as utils from "../../../../utils";
import SVG from "react-inlinesvg";

export default function EmptyContentBlock({
  icon,
  message,
  button,
  skipContentClass,
  height,
  adaptive
}) {
  let style = {};
  if (height > 0) {
    style.height = height;
    style.minHeight = height;
  }

  const Wrapper = skipContentClass
    ? props => <div {...props} />
    : UI.ContentBox;

  return (
    <Wrapper
      className={utils.classNames({
        EmptyContentBlock: true
      })}
      style={style}
    >
      <div className="EmptyContentBlock__content__icon">
        <SVG src={icon} />
      </div>
      <div className="EmptyContentBlock__content__message">{message}</div>
      {button && (
        <div className="EmptyContentBlock__call_to_action">
          <UI.Button
            onClick={button.onClick}
            size={button.size || (!adaptive ? "large" : "small")}
            style={adaptive ? { marginTop: 16 } : {}}
          >
            {button.text}
          </UI.Button>
        </div>
      )}
    </Wrapper>
  );
}

EmptyContentBlock.propTypes = {
  icon: PropTypes.string,
  message: PropTypes.string,
  button: PropTypes.shape({
    text: PropTypes.string.isRequired,
    onClick: PropTypes.func
  }),
  skipContentClass: PropTypes.bool,
  height: PropTypes.number
};
