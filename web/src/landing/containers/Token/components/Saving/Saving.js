import "./Saving.less";
import React, { memo } from "react";
import ContentItem from "../../../../components/ContentItem/ContentItem";
import Lang from "../../../../../components/Lang/Lang";

export default memo(() => {
  return (
    <div className="LandingWrapper__block TokenSaving">
      <div className="LandingWrapper__content">
        <div>
          <ContentItem
            accent
            image={require("src/asset/illustrations/safe.svg")}
          >
            <h1>
              <Lang name="landingToken_saving_title" />
            </h1>
            <h3>
              <Lang name="landingToken_saving_title" />
            </h3>
            <p>
              <Lang name="landingToken_saving_description" />
            </p>
          </ContentItem>
        </div>
      </div>
    </div>
  );
});
