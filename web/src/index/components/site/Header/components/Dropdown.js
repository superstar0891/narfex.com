import React, { useState } from "react";
import SVG from "react-inlinesvg";

function Dropdown({
  title,
  subItems,
  onChange,
  onNavigate,
  lastItemText,
  onLastItemClick,
  className
}) {
  const [isOpen, toggle] = useState(false);

  const handleLinkClick = value => {
    onChange && onChange(value);
    toggle(false);
  };

  const handleLastItemClick = () => {
    toggle(false);
    onLastItemClick();
  };

  return (
    <div className={"SiteHeader__dropdown " + className}>
      <div
        className="SiteHeader__menu__item"
        onClick={() => toggle(true)}
        onMouseEnter={() => toggle(true)}
        onMouseLeave={() => toggle(false)}
      >
        {title}
        <SVG src={require("../../../../../asset/menu_arrow.svg")} />
      </div>

      {isOpen ? (
        <div
          className="SiteHeader__dropdown__items"
          onMouseEnter={() => toggle(true)}
          onMouseLeave={() => toggle(false)}
        >
          {subItems.map((item, i) => {
            if (item.route) {
              return (
                <span
                  key={item.title}
                  className="SiteHeader__dropdown__link"
                  onClick={() => {
                    item.route.includes("http")
                      ? window.open(item.route)
                      : onNavigate(item.route);
                  }}
                >
                  {item.title}
                </span>
              );
            } else if (typeof item.title === "string") {
              return (
                <p
                  key={item.title}
                  className="SiteHeader__dropdown__link"
                  onClick={() => handleLinkClick(item.value)}
                >
                  {item.title}
                </p>
              );
            } else {
              return (
                <div key={i} className="SiteHeader__dropdown__link">
                  {item.title}
                </div>
              );
            }
          })}

          {lastItemText && (
            <span
              className="SiteHeader__dropdown__link"
              onClick={handleLastItemClick}
            >
              {lastItemText}
            </span>
          )}
        </div>
      ) : null}
    </div>
  );
}

export default React.memo(Dropdown);
