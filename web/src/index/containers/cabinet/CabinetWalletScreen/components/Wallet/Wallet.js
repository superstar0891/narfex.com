import React from "react";

import { useSelector } from "react-redux";
import { currencySelector } from "../../../../../../selectors";
import { classNames as cn } from "../../../../../../utils";
import { CircleIcon, NumberFormat } from "../../../../../../ui";

export default ({ currency, onClick, title, icon, amount, active }) => {
  const currencyInfo = useSelector(currencySelector(currency));

  return (
    <div
      onClick={onClick}
      className={cn("WalletList__item", { primary: !!title, active })}
    >
      <div className="WalletList__item__icon">
        {currencyInfo ? (
          <CircleIcon size="small" currency={currencyInfo} />
        ) : (
          <CircleIcon size={title ? "medium" : "small"} icon={icon} />
        )}
      </div>
      <div className="WalletList__item__content">
        {currencyInfo && (
          <div className="WalletList__item__name">{currencyInfo.name}</div>
        )}
        {title && <div className="WalletList__item__title">{title}</div>}
        <div className="WalletList__item__amount">
          {!isNaN(amount) && (
            <NumberFormat number={amount} currency={currency} />
          )}
        </div>
      </div>
    </div>
  );
};
