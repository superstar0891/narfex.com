import "./Functions.less";
import React, { memo } from "react";
import ContentItem from "../../../../components/ContentItem/ContentItem";
import Lang from "../../../../../components/Lang/Lang";

export default memo(() => {
  return (
    <div className="LandingWrapper__block TokenFunctions">
      <div className="LandingWrapper__content">
        <h2>
          <Lang name="landingToken_functions_title" />
        </h2>
        <div>
          <ContentItem image={require("src/asset/illustrations/wallet.svg")}>
            <h3>
              <Lang name="landingToken_functions_cashback_title" />
            </h3>
            <p>
              <Lang name="landingToken_functions_cashback_description" />
            </p>
          </ContentItem>
          <ContentItem
            flip
            image={require("src/asset/illustrations/savings.svg")}
          >
            <h3>
              <Lang name="landingToken_functions_staking_title" />
            </h3>
            <p>
              <Lang name="landingToken_functions_staking_description" />
            </p>
          </ContentItem>
          <ContentItem image={require("src/asset/illustrations/analytics.svg")}>
            <h3>
              <Lang name="landingToken_functions_fee_title" />
            </h3>
            <p>
              <Lang name="landingToken_functions_fee_description" />
            </p>
          </ContentItem>
          <ContentItem
            flip
            image={require("src/asset/illustrations/market-launch.svg")}
          >
            <h3>
              <Lang name="landingToken_functions_launchpad_title" />
            </h3>
            <p>
              <Lang name="landingToken_functions_launchpad_description" />
            </p>
          </ContentItem>
        </div>
      </div>
    </div>
  );
});
