import React from "react";
import List from "../../../ui/components/List/List";
import Item from "../Item/Item";

export default props => {
  const items = props.items.map(item => ({
    ...item,
    value: item.items.length ? <Item item={item} /> : "-"
  }));

  return <List {...props} items={items} />;
};
