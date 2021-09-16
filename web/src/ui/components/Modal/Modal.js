/* eslint-disable */
// styles
import "./Modal.less";
// external
import React, { useCallback, useEffect, useRef } from "react";
import PropTypes from "prop-types";
// internal
import { classNames } from "../../utils";
import SVG from "react-inlinesvg";
import { useAdaptive } from "src/hooks";

function Modal(props) {
  const node = useRef();
  const { onClose } = props;
  const adaptive = useAdaptive();
  const className = classNames(props.className, {
    adaptive,
    Modal: true,
    Modal__noSpacing: props.noSpacing,
    Modal__grayBackground: props.grayBackground
  });

  const handlePressEsc = e => {
    if (e.keyCode === 27) {
      props.onClose && props.onClose(e);
    }
  };

  useEffect(() => {
    document.addEventListener("keydown", handlePressEsc, false);

    return () => {
      document.removeEventListener("keydown", handlePressEsc, false);
    };
  }, []);

  const handleClose = useCallback(
    e => {
      e.preventDefault();
      onClose && onClose(e);
    },
    [onClose]
  );

  if (props.isOpen) {
    return (
      <div className={className}>
        <div className="Modal__back" onClick={handleClose} />
        <div
          className="Modal__box"
          onClick={e => e.stopPropagation()}
          ref={node}
          style={{ width: props.width }}
        >
          {!props.skipClose && (
            <div className="Modal__box__close" onClick={handleClose}>
              <SVG
                src={
                  adaptive
                    ? require("src/asset/24px/angle-left.svg")
                    : require("src/asset/24px/close-large.svg")
                }
              />
            </div>
          )}
          {props.children}
        </div>
      </div>
    );
  }

  return null;
}

Modal.defaultProps = {
  isOpen: true,
  skipClose: false
};

Modal.propTypes = {
  isOpen: PropTypes.bool,
  noSpacing: PropTypes.bool,
  onClose: PropTypes.func,
  width: PropTypes.number,
  skipClose: PropTypes.bool,
  grayBackground: PropTypes.bool
};

export function ModalHeader({ children }) {
  return <div className="Modal__header">{children}</div>;
}

export default React.memo(Modal);
