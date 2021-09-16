import "./Balances.less";

import React from "react";
import { Collapse, NumberFormat } from "../../../../../../ui";
import { ReactComponent as WithdrawIcon } from "src/asset/16px/withdraw.svg";
import { getCurrencyInfo, openStateModal } from "../../../../../../actions";
import { useSelector } from "react-redux";
import { partnersBalancesSelector } from "../../../../../../selectors";
import Lang from "../../../../../../components/Lang/Lang";

export default () => {
  const balances = useSelector(partnersBalancesSelector);

  const handleClickBalance = id => {
    openStateModal("partners_withdraw_balance", {
      balanceId: id
    });
  };

  return (
    <Collapse
      className="PartnersBalances"
      skipCollapseOnDesktop
      title={<Lang name="cabinet_partners_YourPartnerBalance" />}
    >
      <div className="PartnersBalances__content">
        <div className="PartnersBalances__labels">
          <span className="PartnersBalances__label">
            <Lang name="global_cryptocurrency" />
          </span>
          <span className="PartnersBalances__label">
            <Lang name="global_profit" />
          </span>
        </div>

        <ul className="PartnersBalances__list">
          {balances.map(balance => (
            <li
              key={balance.id}
              onClick={() => handleClickBalance(balance.id)}
              className="PartnersBalances__balance"
            >
              <div className="PartnersBalances__balance__name">
                {getCurrencyInfo(balance.currency).name}
              </div>
              <div className="PartnersBalances__balance__amount">
                <NumberFormat
                  number={balance.amount}
                  hiddenCurrency
                  currency={balance.currency}
                />
              </div>
              <div className="PartnersBalances__balance__action">
                <WithdrawIcon />
              </div>
            </li>
          ))}
        </ul>
      </div>
    </Collapse>
  );
};
