import "./Usability.less";

import React from "react";
import { OnScroll } from "../../../../../../ui";
import SVG from "react-inlinesvg";
import * as actions from "../../../../../../actions";
import Lang from "../../../../../../components/Lang/Lang";

export default () => {
  return (
    <OnScroll className="SiteTokenScreen__UsabilityWrapper">
      <div className="anchor" id="Usability" />
      <div className="SiteTokenScreen__Usability">
        <h2>
          <Lang name="landing_usability_title" />
        </h2>
        <ul>
          <li>
            <SVG src={require("src/asset/120/fee.svg")} />
            <h4>
              <Lang name="landing_usability_feeTitle" />
            </h4>
            <p>
              <Lang name="landing_usability_feeText" />
            </p>
          </li>
          <li>
            <SVG src={require("src/asset/120/invest.svg")} />
            <h4>
              <Lang name="landing_usability_flexibleTitle" />
            </h4>
            <p>
              <Lang name="landing_usability_flexibleText" />
            </p>
            <span
              className="link"
              onClick={() => {
                // actions.openStateModal("static_content", {
                //   type: "nrfx_flexible_saving"
                // });
              }}
            >
              <Lang name="landing_usability_flexibleLink" />
            </span>
          </li>
          <li>
            <SVG src={require("src/asset/120/pay.svg")} />
            <h4>
              <Lang name="landing_usability_cashbackTitle" />
            </h4>
            <p>
              <Lang name="landing_usability_cashbackText" />
            </p>
            <span
              className="link"
              onClick={() => {
                // actions.openStateModal("static_content", {
                //   type: "nrfx_cashback"
                // });
              }}
            >
              <Lang name="landing_usability_cashbackLink" />
            </span>
          </li>
        </ul>
      </div>
    </OnScroll>
  );
};
