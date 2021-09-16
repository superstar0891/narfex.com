import "./TokenData.less";

import React, { useEffect, useState } from "react";
import { getLang } from "src/utils";
import { Button, NumberFormat, ButtonWrapper, OnScroll } from "src/ui";
import { tokenSoldAmountGet } from "src/actions/cabinet/wallets";

export default props => {
  const [amount, setAmount] = useState(0);

  useEffect(() => {
    tokenSoldAmountGet().then(r => {
      setAmount(r.amount);
    });
  }, []);

  const hardCap = 5000000;
  const softCap = 800000;

  const amountPercent = Math.min((amount / hardCap) * 100, 100) + "%";

  return (
    <OnScroll className="SiteTokenScreen__TokenDataWrapper">
      <div className="anchor" id="TokenData" />
      <div className="SiteTokenScreen__TokenData">
        <h2>{getLang("token_narfexTokenDataTitle")}</h2>
        <div className="SiteTokenScreen__TokenData__layout">
          <div className="SiteTokenScreen__TokenData__content">
            <p>{getLang("token_narfexTokenDataText1")}</p>
            <p>{getLang("token_narfexTokenDataText2")}</p>
          </div>
          <div className="SiteTokenScreen__TokenData__scaleWrapper">
            <div className="SiteTokenScreen__TokenData__scale">
              <div
                title={amount + " USD"}
                style={{ height: amountPercent, width: amountPercent }}
                className="SiteTokenScreen__TokenData__scale__value"
              />
            </div>
            <div className="SiteTokenScreen__TokenData__scaleLabels">
              <div className="SiteTokenScreen__TokenData__scaleLabel">
                <small>{getLang("token_narfexTokenDataSoftCap")}</small>
                <strong>
                  <NumberFormat number={softCap} currency="usd" />
                </strong>
              </div>
              <div className="SiteTokenScreen__TokenData__scaleLabel">
                <small>{getLang("token_narfexTokenDataHardCap")}</small>
                <strong>
                  <NumberFormat number={hardCap} currency="usd" />
                </strong>
              </div>
            </div>
          </div>
        </div>
        <ButtonWrapper>
          <Button onClick={props.onBuy}>{getLang("token_buyToken")}</Button>
        </ButtonWrapper>
      </div>
    </OnScroll>
  );
};
