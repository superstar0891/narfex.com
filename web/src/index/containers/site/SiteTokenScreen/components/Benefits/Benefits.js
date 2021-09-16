import "./Benefits.less";
import React from "react";
import { getLang } from "src/utils";
import { OnScroll } from "src/ui";
import SVG from "react-inlinesvg";

export default props => {
  return (
    <OnScroll className="SiteTokenScreen__Benefits">
      <div className="anchor" id="Benefits" />
      <div className="SiteTokenScreen__Benefits__content">
        <h2>{getLang("token_Benefits")}</h2>
        <p>{getLang("token_Benefits_p1")}</p>
        <p>{getLang("token_Benefits_p2")}</p>
        <p>{getLang("token_Benefits_p3")}</p>
      </div>
      <OnScroll className="SiteTokenScreen__Benefits__list">
        <div className="SiteTokenScreen__Benefits__list__item">
          <SVG src={require("src/asset/120/launch.svg")} />
          <p>{getLang("token_Benefits_item1")}</p>
        </div>
        <div className="SiteTokenScreen__Benefits__list__item">
          <SVG src={require("src/asset/120/product.svg")} />
          <p>{getLang("token_Benefits_item2")}</p>
        </div>
        <div className="SiteTokenScreen__Benefits__list__item">
          <SVG src={require("src/asset/120/trade.svg")} />
          <p>{getLang("token_Benefits_item3")}</p>
        </div>
        <div className="SiteTokenScreen__Benefits__list__item">
          <SVG src={require("src/asset/120/percent.svg")} />
          <p>{getLang("token_Benefits_item4")}</p>
        </div>
      </OnScroll>
    </OnScroll>
  );
};
