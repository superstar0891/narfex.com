import React, { useState } from "react";
import * as utils from "../../../../utils";
import { MarkDown } from "src/ui/index";
import { classNames as cn } from "../../../../utils";
import "../Notice/Notice.less";
import { useSelector } from "react-redux";
import { currentLangSelector } from "../../../../selectors";

export default () => {
  const currentLang = useSelector(currentLangSelector);
  const [crop, setCrop] = useState(true);
  const notice = utils.getLang("site_footer_notice", true, currentLang);

  return (
    <div className="LandingWrapper__block">
      <div className="FreePik">Illustration by Stories by Freepik</div>
      <div
        onClick={() => setCrop(false)}
        className={cn("FooterNotice", { active: crop })}
      >
        <MarkDown content={notice} />
      </div>
    </div>
  );
};
