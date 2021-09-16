import "./SwapINdicator.less";

import React from "react";
import { useSelector } from "react-redux";
import { CircleIcon } from "../../../../../../ui";
import { currencySelector } from "../../../../../../selectors";
import { ReactComponent as ArrowRightIcon } from "src/asset/24px/arrow-right.svg";

export default ({ from = "usd", to = "btc" }) => {
  const fromCurrency = useSelector(currencySelector(from));
  const toCurrency = useSelector(currencySelector(to));

  return (
    <div className="SwapIndicator">
      <CircleIcon currency={fromCurrency} />
      <ArrowRightIcon />
      <CircleIcon currency={toCurrency} />
    </div>
  );
};
