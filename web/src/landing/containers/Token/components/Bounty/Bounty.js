import "./Bounty.less";
import React, { memo } from "react";
import ContentItem from "../../../../components/ContentItem/ContentItem";
import Lang from "../../../../../components/Lang/Lang";

export default memo(() => {
  return (
    <div className="LandingWrapper__block TokenBounty">
      <div className="LandingWrapper__content TokenBounty__content">
        <div>
          <ContentItem
            accent
            image={require("src/asset/illustrations/token-p2p.svg")}
          >
            <h1>
              <Lang name="landingToken_bounty_title" />
            </h1>
            <p>
              <Lang name="landingToken_bounty_description" />
            </p>
          </ContentItem>
          <ContentItem
            image={require("src/asset/illustrations/connecting-teams.svg")}
          >
            <h3>
              <Lang name="landingToken_bountyPartners_title" />
            </h3>
            <p>
              <Lang name="landingToken_bountyPartners_description" />
            </p>
          </ContentItem>
          <ContentItem
            flip
            image={require("src/asset/illustrations/friendship.svg")}
          >
            <h3>
              <Lang name="landingToken_bountyReferrals_title" />
            </h3>
            <p>
              <Lang name="landingToken_bountyReferrals_description" />
            </p>
          </ContentItem>
          <ContentItem
            image={require("src/asset/illustrations/mobile-marketing.svg")}
          >
            <h3>
              <Lang name="landingToken_bountyAdvisers_title" />
            </h3>
            <p>
              <Lang name="landingToken_bountyAdvisers_description" />
            </p>
          </ContentItem>
        </div>
      </div>
    </div>
  );
});
