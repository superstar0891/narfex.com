import "./WalletBalance.less";
import React, { useState } from "react";
import { connect } from "react-redux";
import SVG from "react-inlinesvg";
import PieChart from "react-minimal-pie-chart";

import { formatNumber, classNames } from "../../../../utils/index";
import * as utils from "../../../../utils/index";
import * as UI from "../../../../ui/index";
import { getCurrencyInfo } from "../../../../actions";

const getWalletsBalance = (wallets, isInFiat) => {
  let walletsAmount = 0;
  let balancePieStyle = "";
  let walletsBalanceInUSD = 0;
  let walletsBalanceInAlign = 0;
  let walletsCurrencies = [];

  wallets.forEach(item => {
    walletsAmount += item.amount;
    walletsBalanceInUSD += item.amount * item.to_usd;
    walletsBalanceInAlign += item.align;
  });

  if (walletsAmount === 0) {
    return null;
  }

  wallets.forEach((wallet, i) => {
    if (wallet.amount !== 0) {
      const { color } = getCurrencyInfo(wallet.currency);
      walletsCurrencies.push({
        color,
        currency: wallet.currency,
        value: ((wallet.amount * wallet.to_usd) / walletsBalanceInUSD) * 100
      });
    }
  });

  return {
    walletsBalanceInUSD,
    walletsBalanceInAlign,
    walletsCurrencies,
    balancePieStyle
  };
};

function WalletBalance({ wallets, adaptive, title, isFiat, emptyPlaceholder }) {
  const [isInFiat, setIsInFiat] = useState(true);
  const walletsBalance = getWalletsBalance(wallets, isInFiat);

  const balanceHeader = <h3 className="WalletBalance__header">{title}</h3>;

  return (
    <UI.ContentBox className="WalletBalance">
      {walletsBalance ? (
        <>
          {adaptive && balanceHeader}
          {adaptive && (
            <div>
              <div
                className="WalletBalance__pie__balance"
                onClick={() => setIsInFiat(!isInFiat)}
              >
                <h3 style={!isInFiat ? { fontSize: 20 } : {}}>
                  <span>
                    {isInFiat
                      ? formatNumber(walletsBalance.walletsBalanceInUSD) + "$"
                      : walletsBalance.walletsBalanceInAlign.toFixed(3) +
                        " BTC"}
                  </span>
                </h3>
              </div>
            </div>
          )}
          <div className="WalletBalance__adaptiveHelper">
            <div className="WalletBalance__list">
              {!adaptive && balanceHeader}
              <ul>
                {walletsBalance.walletsCurrencies.map(wallet => {
                  const { background } = getCurrencyInfo(wallet.currency);
                  return (
                    <div
                      key={wallet.currency}
                      className="WalletBalance__list__item"
                    >
                      <span
                        className="WalletBalance__list__item_dot"
                        style={{ background: background }}
                      />
                      <li key={wallet.currency}>
                        <span>{utils.formatDouble(wallet.value, 2)}%</span>
                        {wallet.currency.toUpperCase()}
                      </li>
                    </div>
                  );
                })}
              </ul>
            </div>

            <div className="WalletBalance__pie">
              {adaptive ? (
                <PieChart
                  data={walletsBalance.walletsCurrencies}
                  paddingAngle={1}
                />
              ) : (
                <PieChart
                  lineWidth={20}
                  paddingAngle={1}
                  data={walletsBalance.walletsCurrencies}
                />
              )}

              {!adaptive && (
                <div className="WalletBalance__pie__balance">
                  <h3 style={!isInFiat ? { fontSize: 20 } : {}}>
                    {isInFiat
                      ? formatNumber(walletsBalance.walletsBalanceInUSD) + "$"
                      : walletsBalance.walletsBalanceInAlign.toFixed(3) +
                        " BTC"}
                  </h3>
                  <div>
                    <p
                      className={classNames({ active: isInFiat })}
                      onClick={() => setIsInFiat(true)}
                    >
                      USD
                    </p>
                    <p
                      className={classNames({ active: !isInFiat })}
                      onClick={() => setIsInFiat(false)}
                    >
                      BTC
                    </p>
                  </div>
                </div>
              )}
            </div>
          </div>
        </>
      ) : (
        <div className="Empty_box">
          <SVG src={require("../../../../asset/cabinet/wallet_colorful.svg")} />
          <h3>
            {emptyPlaceholder ||
              utils.getLang("cabinet_walletBalance_statistics_placeholder")}
          </h3>
        </div>
      )}
    </UI.ContentBox>
  );
}

export default connect(state => ({
  currentLang: state.default.currentLang
}))(WalletBalance);
