import "./Allocation.less";
import React, { memo } from "react";
import ContentItem from "../../../../components/ContentItem/ContentItem";
import Lang from "../../../../../components/Lang/Lang";
import { Button, ButtonWrapper } from "../../../../../ui";
import { buyToken } from "../../../../../actions/landing/buttons";

export default memo(() => {
  return (
    <div className="LandingWrapper__block TokenAllocation">
      <div className="LandingWrapper__content">
        <div>
          <ContentItem
            large
            image={require("src/asset/illustrations/report.svg")}
          >
            <h1>
              <Lang name="landingToken_allocation_title" />
            </h1>
            <p>
              <Lang name="landingToken_allocation_subtitle" />
            </p>
            <ul>
              <li>
                <i>5%</i> <Lang name="landingToken_allocation_item1" />
              </li>
              <li>
                <i>10%</i> <Lang name="landingToken_allocation_item2" />
              </li>
              <li>
                <i>35%</i> <Lang name="landingToken_allocation_item3" />
              </li>
              <li>
                <i>25%</i> <Lang name="landingToken_allocation_item4" />
              </li>
              <li>
                <i>25%</i> <Lang name="landingToken_allocation_item5" />
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
