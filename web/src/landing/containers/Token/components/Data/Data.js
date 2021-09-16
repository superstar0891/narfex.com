import "./Data.less";
import React, { memo, useEffect, useState } from "react";
import ContentItem from "../../../../components/ContentItem/ContentItem";
import Lang from "../../../../../components/Lang/Lang";
import { Button, ButtonWrapper, NumberFormat } from "../../../../../ui";
import { tokenSoldAmountGet } from "../../../../../actions/cabinet/wallets";
import { useAdaptive } from "../../../../../hooks";
import { buyToken } from "../../../../../actions/landing/buttons";

export default memo(() => {
  const [amount, setAmount] = useState(0);
  const adaptive = useAdaptive();

  useEffect(() => {
    tokenSoldAmountGet().then(({ amount }) => {
      setAmount(amount);
    });
  }, []);

  const hardCap = 18000000;
  const percent = (amount / hardCap) * 100;

  return (
    <div className="LandingWrapper__block TokenData">
      <div className="LandingWrapper__content">
        <div>
          {adaptive && (
            <h2>
              <Lang name="landingToken_data_title" />
            </h2>
          )}
          <ContentItem
            content={
              <div className="TokenData__scaleBlock">
                <div className="TokenData__scaleWrapper">
                  <div className="TokenData__scale">
                    <div
                      style={{ [adaptive ? "width" : "height"]: percent + "%" }}
                      className="TokenData__scale__value"
                      title={amount}
                    />
                  </div>
                  <div className="TokenData__numbers">
                    <div className="TokenData__numbers__number">
                      <h4>
                        <NumberFormat number={hardCap} currency="usd" />
                      </h4>
                      <small>Hard Cap</small>
                    </div>
                    <div className="TokenData__numbers__number">
                      <h4>
                        <NumberFormat number={600000} currency="usd" />
                      </h4>
                      <small>Soft Cap</small>
                    </div>
                  </div>
                </div>
              </div>
            }
          >
            {!adaptive && (
              <h3>
                <Lang name="landingToken_data_title" />
              </h3>
            )}
            <p>
              <Lang name="landingToken_data_text" />
            </p>
            <p>
              <Lang name="landingToken_data_text2" />
            </p>
            <ButtonWrapper>
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
