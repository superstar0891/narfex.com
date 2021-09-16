import React, { useState } from "react";
import SVG from "react-inlinesvg";

function MobileDropdown({
  title,
  subItems,
  onChange,
  onNavigate,
  lastItemText,
  onLastItemClick
}) {
  const [isOpen, toggle] = useState(false);
  const icon = isOpen
    ? require("../asset/less.svg")
    : require("../asset/more.svg");

  const handleLinkClick = value => {
    onChange(value);
    toggle(false);
  };

  const handleLastItemClick = () => {
    toggle(false);
    onLastItemClick();
  };

  return (
    <div className="SiteHeader__mobileDropdown">
      <div className="SiteHeader__menu__item" onClick={() => toggle(!isOpen)}>
        <SVG src={icon} />
        {title}
      </div>

      {isOpen ? (
        <div className="SiteHeader__mobileDropdown__items">
          {subItems.map((item, i) => {
            if (item.route) {
              return (
                <span
                  key={item.title}
                  className="SiteHeader__mobileDropdown__link"
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
                  className="SiteHeader__mobileDropdown__link"
                  onClick={() => handleLinkClick(item.value)}
                >
                  {item.title}
                </p>
              );
            } else {
              return (
                <div key={i} className="SiteHeader__mobileDropdown__link">
                  {item.title}
                </div>
              );
            }
          })}

          {lastItemText && (
            <span
              className="SiteHeader__mobileDropdown__link"
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

export default React.memo(MobileDropdown);
