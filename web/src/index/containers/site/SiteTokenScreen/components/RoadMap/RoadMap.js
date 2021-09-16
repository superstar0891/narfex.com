import "./RoadMap.less";
import React from "react";
import { getLang, dateFormat, classNames as cn } from "src/utils";
import { NumberFormat, OnScroll } from "src/ui";

export default props => {
  const currentTime = Date.now();
  return (
    <OnScroll className="SiteTokenScreen__RoadMap">
      <div className="anchor" id="RoadMap" />
      <h2>{getLang("token_roadMapTitle")}</h2>
      <h3>{getLang("token_roadMapSubTitle")}</h3>

      <div className="SiteTokenScreen__RoadMap__list">
        {props.items.map((item, key) => (
          <div
            key={key}
            className={cn("SiteTokenScreen__RoadMap__list__item", {
              active: currentTime > item.time
            })}
          >
            <div>
              <small>{dateFormat(item.time, "DD MMM YYYY")}</small>
              <strong>{item.title}</strong>
              {item.price && (
                <span className="price">
                  $<NumberFormat accurate number={item.price} currency="usd" />
                </span>
              )}
            </div>
          </div>
        ))}
      </div>
    </OnScroll>
  );
};
