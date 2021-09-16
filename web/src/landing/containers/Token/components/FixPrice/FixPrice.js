import "./FixPrice.less";
import React, { memo } from "react";
import ContentItem from "../../../../components/ContentItem/ContentItem";
import Lang from "../../../../../components/Lang/Lang";
import { Button, ButtonWrapper } from "../../../../../ui";
import { buyToken } from "../../../../../actions/landing/buttons";

export default memo(() => {
  return (
    <div className="LandingWrapper__block TokenFixPrice">
      <div className="LandingWrapper__content TokenFixPrice__content">
        <div>
          <ContentItem
            accent
            image={require("src/asset/illustrations/token.svg")}
          >
            <o>
              <Lang name="landingToken_fixPrice_subtitle" />
            </o>
            <h1>
              <Lang name="landingToken_fixPrice_title" />
            </h1>
            <ButtonWrapper align="center">
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
