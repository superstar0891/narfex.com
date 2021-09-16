import "./HistoryItem.less";
import PropTypes from "prop-types";

import React, { memo } from "react";
import { CircleIcon, Status } from "../../index";
import { classNames as cn } from "../../utils";

const HistoryItem = memo(
  ({
    icon,
    label,
    header,
    headerSecondary,
    smallText,
    smallTextSecondary,
    status,
    unread,
    type = "default",
    onClick
  }) => {
    return (
      <div onClick={onClick} className={cn("HistoryItem", type, { unread })}>
        <div className="HistoryItem__left">
          <CircleIcon icon={icon} type={type} />
        </div>
        <div className="HistoryItem__content">
          {label && <div className="HistoryItem__content__label">{label}</div>}
          {(header || headerSecondary) && (
            <div className="HistoryItem__content__main">
              {header && (
                <div className="HistoryItem__content__header">{header}</div>
              )}
              {headerSecondary && (
                <div
                  className={cn("HistoryItem__content__headerSecondary", {
                    center: !smallText
                  })}
                >
                  {headerSecondary}
                </div>
              )}
            </div>
          )}
          {status && (
            <div className="HistoryItem__content__status">
              <Status status={status} />
            </div>
          )}
          {(smallText || smallTextSecondary) && (
            <div className="HistoryItem__content__bottom">
              {smallText && (
                <div className="HistoryItem__content__smallText">
                  {smallText}
                </div>
              )}
              {smallTextSecondary && (
                <div className="HistoryItem__content__smallTextSecondary">
                  {smallTextSecondary}
                </div>
              )}
            </div>
          )}
        </div>
      </div>
    );
  }
);

HistoryItem.propTypes = {
  unread: PropTypes.bool,
  icon: PropTypes.node,
  label: PropTypes.node,
  header: PropTypes.node,
  headerSecondary: PropTypes.node,
  smallText: PropTypes.node,
  smallTextSecondary: PropTypes.node,
  status: "string",
  type: "string"
};

export default HistoryItem;
