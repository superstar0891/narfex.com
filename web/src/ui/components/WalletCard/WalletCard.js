import "./WalletCard.less";
import React, { memo } from "react";
import { classNames as cn } from "../../utils";
import NumberFormat from "../NumberFormat/NumberFormat";
import PropTypes from "prop-types";

const WalletCard = memo(
  ({ currency, title, symbol, balance, reject, balanceUsd, status }) => {
    return (
      <div
        className={cn("WalletCard", status, {
          minus: balance <= 0,
          reject
        })}
      >
        {title && <div className="WalletCard__title">{title}</div>}
        {!isNaN(balance) && (
          <div className="WalletCard__balance">
            <NumberFormat
              symbol={symbol}
              currency={currency}
              number={balance}
            />
          </div>
        )}
        {balanceUsd > 0 && (
          <div className="WalletCard__balanceUsd">
            <NumberFormat number={balanceUsd} currency={"usd"} />
          </div>
        )}
      </div>
    );
  }
);

WalletCard.propTypes = {
  balance: PropTypes.number,
  balanceUsd: PropTypes.number,
  currency: PropTypes.string,
  title: PropTypes.string,
  symbol: PropTypes.bool,
  reject: PropTypes.bool
};

export default WalletCard;
