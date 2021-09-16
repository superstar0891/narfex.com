import "./Menu.less";

import React, { useState } from "react";
import SVG from "react-inlinesvg";
import { connect } from "react-redux";

import action from "../../../actions/admin";

import { classNames as cn } from "../../../utils/index";
import LoadingStatus from "../../../index/components/cabinet/LoadingStatus/LoadingStatus";

const Menu = props => {
  const [hiddenItems, setHiddenItems] = useState({});

  if (!props.menu) {
    return <LoadingStatus inline status="loading" />;
  }

  const toggleItem = itemName => {
    setHiddenItems({
      ...hiddenItems,
      [itemName]: !hiddenItems[itemName]
    });
  };

  return (
    <ul className="Menu">
      {props.menu.map((item, key) => (
        <li
          key={key}
          className={cn("Menu__item", { open: !hiddenItems[item.title] })}
        >
          <b onClick={() => toggleItem(item.title)}>
            {item.title}
            <div
              onClick={() => toggleItem(item.title)}
              className="Menu__toggle_icon"
            >
              <SVG src={require("../../../asset/24px/angle-up-small.svg")} />
            </div>
          </b>
          <ul>
            {item.sub_items.map(subItem => (
              <li onClick={() => action(subItem.params.action)}>
                {subItem.title}
              </li>
            ))}
          </ul>
        </li>
      ))}
    </ul>
  );
};

export default connect(state => ({
  menu: state.admin.menu
}))(Menu);
