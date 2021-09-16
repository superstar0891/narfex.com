import "./Tabs.less";

import React from "react";
import { connect } from "react-redux";
import Item from "../Item/Item";

import action from "../../../actions/admin/index";
import { classNames as cn } from "../../../utils/";
const Tabs = props => {
  let content = null;
  return (
    <div className="Tabs__wrapper">
      <div className="Tabs">
        {props.items.map(item => {
          if (!!item.items) {
            content = item.items;
          }
          return (
            <div
              onClick={() => action(item.params.action)}
              className={cn("Tabs__item", { active: !!item.items })}
            >
              {item.title}
            </div>
          );
        })}
      </div>
      <div className="Tabs__content">{content && <Item item={content} />}</div>
    </div>
  );
};

export default connect(state => ({
  // state: state.admin
}))(Tabs);
