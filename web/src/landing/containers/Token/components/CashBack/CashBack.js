import "./CashBack.less";
import React, { memo } from "react";
import ContentItem from "../../../../components/ContentItem/ContentItem";
import Lang from "../../../../../components/Lang/Lang";
import { Button, ButtonWrapper } from "../../../../../ui";
import { buyToken } from "../../../../../actions/landing/buttons";

export default memo(() => {
  return (
    <div className="LandingWrapper__block TokenCashBack">
      <div className="LandingWrapper__content TokenCashBack__content">
        <div>
          <ContentItem
            accent
            image={require("src/asset/illustrations/wallet.svg")}
          >
            <h1>
              <Lang name="landingToken_cashback_title" />
            </h1>
            <p>
              <Lang name="landingToken_cashback_description" />
            </p>
          </ContentItem>
          <ContentItem
            large
            image={require("src/asset/illustrations/successful-purchase.svg")}
          >
            <h1>
              <Lang name="landingToken_shopping_title" />
            </h1>
            <p>
              <Lang name="landingToken_shopping_description" />
            </p>
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
