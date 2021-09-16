import "./CircleIcon.less";
import "../Skeleton/Skeleton.less";

import React, { memo } from "react";
import { classNames as cn } from "../../utils";

export default memo(
  ({
    currency,
    icon,
    className,
    size = "medium",
    skeleton = false,
    type = "default"
  }) => {
    if (skeleton) {
      return (
        <div className={cn("CircleIcon", size, className, { skeleton })} />
      );
    }
    return (
      <div
        style={
          currency
            ? {
                background: currency.background
              }
            : null
        }
        className={cn("CircleIcon", size, type, className, { type, currency })}
      >
        {icon ||
          (currency && currency.icon && (
            <div
              className="CircleIcon__icon"
              style={{
                backgroundImage: `url(${currency.icon}`
              }}
            />
          ))}
      </div>
    );
  }
);
