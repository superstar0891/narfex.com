import "./Release.less";
import React, { memo } from "react";
import ContentItem from "../../../../components/ContentItem/ContentItem";
import Lang from "../../../../../components/Lang/Lang";
import { Button, ButtonWrapper } from "../../../../../ui";
import { buyToken } from "../../../../../actions/landing/buttons";

export default memo(() => {
  return (
    <div className="LandingWrapper__block TokenRelease">
      <div className="LandingWrapper__content">
        <div>
          <ContentItem
            large
            image={require("src/asset/illustrations/segment.svg")}
          >
            <h1>
              <Lang name="landingToken_release_title" />
            </h1>
            <p>
              <Lang name="landingToken_release_subtitle" />
            </p>
            <ul>
              <li>
                <i>50%</i> <Lang name="landingToken_release_ico" />
              </li>
              <li>
                <i>10%</i> <Lang name="landingToken_release_team" />
              </li>
              <li>
                <i>15%</i> <Lang name="landingToken_release_bounty" />
              </li>
              <li>
                <i>25%</i> <Lang name="landingToken_release_other" />
              </li>
            </ul>
            <ButtonWrapper align="left">
              <Button onClick={buyToken} size="extra_large">
                <Lang name="global_buyToken" />
              </Button>
            </ButtonWrapper>
          </ContentItem>
        </div>
      </div>
    </div>
  );
});
