import "./List.less";
import React from "react";

import { classNames as cn } from "../../utils/index";

export default props => (
  <div className="List">
    {props.items &&
      props.items.map(item => (
        <div className={cn("List__item", { margin: item.margin })}>
          <div className="List__item__label">{item.label}</div>
          <div className="List__item__value">{item.value}</div>
        </div>
      ))}
  </div>
);
