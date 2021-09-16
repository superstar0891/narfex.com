import "./CommonHeader.less";

import React from "react";
import { ContentBox, NumberFormat } from "../../../../../../ui";
import PieChart from "react-minimal-pie-chart";
import { useSelector } from "react-redux";
import {
  walletAllBalancesSelector,
  walletWalletsSelector
} from "../../../../../../selectors";
import { getCurrencyInfo } from "../../../../../../actions";
import { useAdaptive } from "src/hooks";
import EmptyBalance from "../EmptyBalance/EmptyBalance";
import Lang from "../../../../../../components/Lang/Lang";

export default () => {
  const adaptive = useAdaptive();
  // const balances = useSelector(walletAllBalancesSelector); // TODO
  const balances = useSelector(walletWalletsSelector);

  const total = balances
    .map(b => b.to_usd * b.amount)
    .reduce((a, b) => a + b, 0);

  if (!total) {
    return <EmptyBalance />;
  }

  const List = () => (
    <ul className="CommonHeader__currencyList">
      {balances.map(b => (
        <li key={b.id}>
          <div
            className="CommonHeader__currencyList__coin"
            style={{
              background: getCurrencyInfo(b.currency).background
            }}
          />
          <div className="CommonHeader__currencyList__percent">
            <NumberFormat
              number={((b.amount * b.to_usd) / total) * 100}
              percent
            />
          </div>
          <div className="CommonHeader__currencyList__currency">
            {b.currency}
          </div>
        </li>
      ))}
    </ul>
  );

  const Chart = () => (
    <div className="CommonHeader__pie">
      <PieChart
        lineWidth={50}
        paddingAngle={1}
        data={balances.map(b => {
          const currency = getCurrencyInfo(b.currency);
          return {
            color: currency.color,
            currency: currency.abbr,
            value: b.to_usd * b.amount
          };
        })}
      />
    </div>
  );

  return (
    <ContentBox className="CommonHeader">
      <div className="CommonHeader__content">
        <div className="CommonHeader__label">
          <Lang name="cabinet_walletBalance_name" />
        </div>
        <div className="CommonHeader__amount">
          <NumberFormat roughly number={total} currency="usd" />
        </div>
        {!adaptive && <List />}
      </div>

      {adaptive ? (
        <div className="CommonHeader__adaptiveBlock">
          <Chart />
          <List />
        </div>
      ) : (
        <Chart />
      )}
    </ContentBox>
  );
};
