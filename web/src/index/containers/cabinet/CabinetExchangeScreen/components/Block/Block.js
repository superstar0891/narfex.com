import "./Block.less";

import React, { useState } from "react";

import { classNames as cn } from "../../../../../../utils";
import * as storage from "../../../../../../services/storage";
import * as UI from "../../../../../../ui/index";

export default function Block(props) {
  let {
    title,
    tabs,
    selectedTab,
    onTabChange,
    children,
    controls,
    skipCollapse,
    skipPadding,
    name
  } = props;

  const storageKey = name + "_collapsed";

  const [collapsed, setCollapsed] = useState(!!storage.getItem(storageKey));

  const __setCollapsed = stage => {
    setCollapsed(stage);
    stage ? storage.setItem(storageKey, true) : storage.removeItem(storageKey);
  };

  const classNames = cn(
    {
      Exchange__block: true,
      skip_padding: !!skipPadding
    },
    props.className
  );

  if (tabs) {
    title = tabs.map(({ tag, label }) => {
      const className = cn({
        Exchange__block__tab: true,
        active: tag === selectedTab
      });
      return (
        <div
          className={className}
          key={tag}
          onClick={() => onTabChange && onTabChange(tag)}
        >
          <div className="Exchange__block__tab__label">{label}</div>
        </div>
      );
    });

    title = <div className="Exchange__block__tabs">{title}</div>;
  }

  const Wrapper = skipCollapse
    ? UI.ContentBox
    : props => <UI.Collapse {...props} isOpen={!collapsed} />;

  return (
    <Wrapper
      title={title}
      onChange={() => __setCollapsed(!collapsed)}
      controls={controls}
      className={classNames}
    >
      {skipCollapse && (
        <div className="Exchange__block__head">
          <div
            className="Exchange__block__title"
            onClick={e => {
              if (!e.target.classList.contains("Exchange__block__tab")) {
                __setCollapsed(!collapsed);
              }
            }}
          >
            {title}
          </div>
          <div className="Exchange__block__head__controls">{controls}</div>
        </div>
      )}
      <div className={cn("Exchange__block__content")}>{children}</div>
    </Wrapper>
  );
}
