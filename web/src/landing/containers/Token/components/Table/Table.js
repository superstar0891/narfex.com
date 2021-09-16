import "./Table.less";
import React, { memo } from "react";
import Lang from "../../../../../components/Lang/Lang";
import { Button, ButtonWrapper } from "../../../../../ui";
import { buyToken } from "../../../../../actions/landing/buttons";

export default memo(() => {
  return (
    <div className="LandingWrapper__block TokenTable">
      <div className="LandingWrapper__content TokenTable__content">
        <div>
          <h1>
            <Lang name="landingToken_hold_title" />
          </h1>
          <p>
            <Lang name="landingToken_hold_subtitle" />
          </p>

          <div className="TokenTable__wrapper">
            <table className="TokenTable__table">
              <tr>
                <th>
                  <Lang name="landingToken_hold_youToken" />
                </th>
                <th>
                  <Lang name="landingToken_hold_interest" />
                </th>
              </tr>
              <tr>
                <td>100–1 000</td>
                <td>2%</td>
              </tr>
              <tr>
                <td>1001–3000</td>
                <td>2.2%</td>
              </tr>
              <tr>
                <td>3001–5000</td>
                <td>2.4%</td>
              </tr>
              <tr>
                <td>5001–7000</td>
                <td>2.6%</td>
              </tr>
              <tr>
                <td>7001–10000</td>
                <td>2.8%</td>
              </tr>
              <tr>
                <td>10001+</td>
                <td>3%</td>
              </tr>
            </table>

            <small>
              <Lang name="landingToken_hold_description" />
            </small>

            <ButtonWrapper align="center">
              <Button onClick={buyToken} size="extra_large">
                <Lang name="global_buyToken" />
              </Button>
            </ButtonWrapper>
          </div>
        </div>
      </div>
    </div>
  );
});
