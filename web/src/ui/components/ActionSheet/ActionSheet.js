import "./ActionSheet.less";

import React from "react";
import { ReactComponent as MenuMoreIcon } from "src/asset/24px/menu-more.svg";

import { classNames as cn } from "../../utils/index";
import ContentBox from "../ContentBox/ContentBox";

export default class ActionSheet extends React.Component {
  state = {
    visible: false
  };

  toggle = visible => {
    this.setState({ visible });
    if (visible) {
      document.addEventListener("click", this.__handleClick, false);
      document.addEventListener("keydown", this.__handleClickEsc, false);
    } else {
      document.removeEventListener("click", this.__handleClick, false);
      document.removeEventListener("keydown", this.__handleClickEsc, false);
    }
  };

  __handleClick = () => {
    this.toggle(false);
  };

  __handleClickEsc = e => {
    if (e.keyCode === 27) {
      this.toggle(false);
    }
  };

  render() {
    const { props, state } = this;

    return (
      <div
        onMouseLeave={props.mouseOver ? () => this.toggle(false) : () => {}}
        className={cn("ActionsSheet", props.position, {
          visible: state.visible,
          disabled: !props.items.length
        })}
      >
        <div
          onClick={() => this.toggle(true)}
          onMouseOver={props.mouseOver ? () => this.toggle(true) : () => {}}
        >
          {props.children || (
            <div className="ActionsSheet__list__icon">
              <MenuMoreIcon />
            </div>
          )}
        </div>
        <ContentBox className="ActionsSheet__list">
          {props.items.map((item, key) => (
            <div
              key={key}
              className={cn("ActionsSheet__item", item.type)}
              onClick={e => {
                item.onClick();
                this.toggle(false);
              }}
            >
              <div className="ActionsSheet__item__title">{item.title}</div>
              {item.subContent && (
                <div className="ActionsSheet__item__sub">{item.subContent}</div>
              )}
            </div>
          ))}
        </ContentBox>
      </div>
    );
  }
}
