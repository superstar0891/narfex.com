import "./Collapse.less";

import React, { memo, useState } from "react";
import PropTypes from "prop-types";
import { ReactComponent as ArrowIcon } from "../../../asset/24px/angle-down-small.svg";

import ContentBox from "../ContentBox/ContentBox";
import { classNames as cn } from "../../utils/index";
import { useAdaptive } from "src/hooks";

const Collapse = memo(props => {
  const adaptive = useAdaptive();
  const [isOpenState, toggle] = useState(props.isOpenDefault);
  const skipCollapse = !adaptive && props.skipCollapseOnDesktop;

  const handleToggle = () => {
    if (props.isOpen === undefined) {
      toggle(!isOpenState);
    } else if (props.onChange) {
      props.onChange(!props.isOpen);
    }
  };

  const isOpen =
    skipCollapse || (props.isOpen !== undefined ? props.isOpen : isOpenState);

  return (
    <ContentBox
      className={cn("Collapse", props.className, {
        active: !skipCollapse,
        isOpen
      })}
    >
      <div className="Collapse__header" onClick={handleToggle}>
        <div className="Collapse__title">{props.title}</div>
        {!!props.controls && (
          <div
            onClick={e => {
              e.stopPropagation();
            }}
            className="Collapse__controls"
          >
            {props.controls}
          </div>
        )}
        {!skipCollapse && (
          <div className="Collapse__arrow">
            <ArrowIcon />
          </div>
        )}
      </div>
      {isOpen && <div className="Collapse__content">{props.children}</div>}
    </ContentBox>
  );
});

Collapse.propTypes = {
  className: PropTypes.string,
  isOpen: PropTypes.bool,
  isOpenDefault: PropTypes.bool,
  title: PropTypes.node,
  skipCollapseOnDesktop: PropTypes.bool,
  controls: PropTypes.arrayOf(PropTypes.element)
};

export default Collapse;
