import "./MobileAppBanner.less";

import React from "react";
import * as utils from "../../../../utils/index";
import COMPANY from "../../../constants/company";

export default function MobileAppBanner() {
  return (
    <div className="MobileAppBanner">
      <div className="MobileAppBanner__bg" />
      <div className="MobileAppBanner__cont">
        <div className="MobileAppBanner__text">
          <div className="MobileAppBanner__title">
            {utils.getLang("site__mobileAppBannerTitle")}
          </div>
          <div className="MobileAppBanner__caption">
            {utils.getLang("site__mobileAppBannerSubTitle")}
          </div>
        </div>
        <div className="MobileAppBanner__buttons">
          <a
            href={COMPANY.apps.ios}
            className="MobileAppBanner__button ios"
            target="_blank"
            rel="noopener noreferrer"
          >
            iOS app
          </a>

          <a
            href={COMPANY.apps.android}
            className="MobileAppBanner__button android"
            target="_blank"
            rel="noopener noreferrer"
          >
            Android app
          </a>
        </div>
      </div>
    </div>
  );
}
