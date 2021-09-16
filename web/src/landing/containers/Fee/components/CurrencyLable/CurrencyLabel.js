import "./CurrencyLabel.less";

import React from "react";
import * as actions from "src/actions";
import { CircleIcon } from "../../../../../ui";

export default ({ abbr }) => {
  const currency = actions.getCurrencyInfo(abbr);
  return (
    <div className="CurrencyLabel" title={currency.name}>
      <CircleIcon size="extra_small" currency={currency} />
      <div className="CurrencyLabel__abbr" style={{ color: currency.color }}>
        {currency.abbr}
      </div>
    </div>
  );
};
