import "./InfoSection.less";

import React from "react";

import InfoCard from "../InfoCard/InfoCard";
import * as utils from "../../../../utils/index";

function InfoSection({ firstInfo, secondInfo }) {
  return (
    <div className="InfoSection__infocards">
      <InfoCard
        horizontal
        title={firstInfo.title}
        caption={firstInfo.caption}
        icon={firstInfo.icon}
        className="InfoSection__infocard"
        btn={
          <a href={`/${firstInfo.route}`} className="InfoSection__link">
            {utils.getLang("site_readMore")}
          </a>
        }
      />

      <div className="InfoSection__divider"></div>

      <InfoCard
        horizontal
        title={secondInfo.title}
        caption={secondInfo.caption}
        icon={secondInfo.icon}
        className="InfoSection__infocard"
        btn={
          <a href={`/${secondInfo.route}`} className="InfoSection__link">
            {utils.getLang("site_readMore")}
          </a>
        }
      />
    </div>
  );
}

export default React.memo(InfoSection);
