import "./Item.less";

import React from "react";
import Wrapper from "../Wrapper/Wrapper";
import Block from "../Block/Block";
import Group from "../Group/Group";
import Table from "../Table/Table";
import Button from "../../../ui/components/Button/Button";
import Input from "../Input/Input";
import Select from "../Select/Select";
import DropDown from "../Dropdown/Dropdown";
import action from "../../../actions/admin/index";
import List from "../List/List";
import Title from "../Title/Title";
import Tabs from "../Tabs/Tabs";
import Paging from "../Paging/Paging";
import PagingItem from "../Paging/PagingItem";
import Filter from "../../../ui/components/Filter/Filter";
import Json from "../Json/Json";
import Text from "../Text/Text";
import Chackbox from "../Chackbox/Chackbox";
import Wysiwyg from "../Wysiwyg/Wysiwyg";
import Time from "../Time/Time";
import Clipboard from "../Clipboard/Clipboard";
import Image from "../Image/Image";
import Message from "../../../ui/components/Message/Message";
import ActionSheet from "../../../ui/components/ActionSheet/ActionSheet";
import NumberFormat from "../../../ui/components/NumberFormat/NumberFormat";
import { connect } from "react-redux";

const Item = props => {
  const { item } = props;

  let Component = null;

  if (!item) {
    return null;
  }

  if (["string", "number"].includes(typeof item)) {
    return <p>{item}</p>;
  }

  if (Array.isArray(item)) {
    return item.map((item, key) => <Item key={key} item={item} />);
  }

  const handleClick =
    item.params && item.params.action
      ? () => {
          action(item.params.action);
        }
      : null;

  switch (item.type) {
    case "wrapper":
      Component = Wrapper;
      break;
    case "block":
      Component = Block;
      break;
    case "list":
      Component = List;
      break;
    case "title":
      Component = Title;
      break;
    case "list_item":
      Component = props => <div {...props} />;
      break;
    case "group":
      Component = Group;
      break;
    case "table":
      Component = Table;
      break;
    case "image":
      Component = Image;
      break;
    case "paging":
      Component = Paging;
      break;
    case "paging_item":
      Component = PagingItem;
      break;
    case "drop_down":
      Component = DropDown;
      break;
    case "table_filter":
      Component = props => <Filter {...props} onCancel={handleClick} />;
      break;
    case "action_sheet":
      Component = () => (
        <ActionSheet
          position="left"
          items={item.items.map(item => ({
            title: item.title,
            type: item.action_type,
            onClick: () => action(item.params.action)
          }))}
        />
      );
      break;
    case "number_format":
      Component = props => (
        <NumberFormat
          {...props}
          type={props.display_type}
          fractionDigits={props.fraction_digits}
          skipTitle={props.skip_title}
          hiddenCurrency={props.hidden_currency}
        />
      );
      break;
    case "button":
      Component = () => (
        <Button
          onClick={handleClick}
          children={item.title}
          type={item.button_type}
          size={item.size}
        />
      );
      break;
    case "input":
      Component = Input;
      break;
    case "select":
      Component = Select;
      break;
    case "tabs":
      Component = Tabs;
      break;
    case "json":
      Component = Json;
      break;
    case "text":
      Component = Text;
      break;
    case "checkbox":
      Component = Chackbox;
      break;
    case "wysiwyg":
      Component = Wysiwyg;
      break;
    case "time":
      Component = Time;
      break;
    case "clipboard":
      Component = Clipboard;
      break;
    default:
      Component = props => (
        <Message type="error">Error item type [{props.type}]</Message>
      );
      break;
  }

  return (
    <Component {...props} {...item}>
      {item.items &&
        item.items.map((item, key) => {
          return <Item key={key} item={item} />;
        })}
    </Component>
  );
};

export default connect(state => ({
  layout: state.admin.layout,
  pending: state.admin.pending
}))(Item);
