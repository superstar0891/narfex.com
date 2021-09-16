import "./StartEarning.less";
import React, { memo } from "react";
import ContentItem from "../../../../components/ContentItem/ContentItem";
import Lang from "../../../../../components/Lang/Lang";

export default memo(() => {
  return (
    <div className="LandingWrapper__block TokenStartEarning">
      <div className="LandingWrapper__content">
        <div>
          <ContentItem
            large
            image={require("src/asset/illustrations/businessman-token.svg")}
          >
            <h3>
              <Lang name="landingToken_startEarning_title" />
            </h3>
            <p>
              <Lang name="landingToken_startEarning_description" />
            </p>
          </ContentItem>
        </div>
      </div>
    </div>
  );
});
