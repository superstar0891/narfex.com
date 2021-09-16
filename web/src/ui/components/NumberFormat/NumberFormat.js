import "./NumberFormat.less";

import React from "react";
import { useSelector } from "react-redux";
import PropTypes from "prop-types";
import { classNames } from "../../utils";

import { noExponents } from "../../utils/index";
import bn from "big.js";
import { currencySelector, marketCurrencySelector } from "../../../selectors";

const NumberFormat = ({
  number,
  symbol,
  fractionDigits,
  color,
  skipTitle,
  accurate,
  currency,
  hiddenCurrency,
  prefix,
  type,
  percent,
  indicator,
  brackets,
  onClick,
  roughly,
  skipRoughly,
  market = false
}) => {
  const currencyInfo = useSelector(currencySelector(currency));
  const marketCurrencyInfo = useSelector(marketCurrencySelector(currency));
  if (isNaN(parseFloat(number)) || Math.abs(number) === Infinity) return null;

  if (!fractionDigits) {
    if (percent) {
      fractionDigits = 2;
    } else {
      fractionDigits = currencyInfo ? currencyInfo.maximum_fraction_digits : 2;

      if (market && marketCurrencyInfo) {
        fractionDigits = marketCurrencyInfo.decimals;
      }
    }
  }

  const coefficient = parseInt(1 + "0".repeat(fractionDigits));
  let displayNumber =
    Math.floor(
      bn(number)
        .mul(coefficient)
        .toExponential()
    ) / coefficient;

  displayNumber = displayNumber.toLocaleString("ru", {
    maximumFractionDigits: fractionDigits,
    minimumFractionDigits: accurate ? fractionDigits : undefined
  });

  if (currency && !percent) {
    displayNumber += " " + (!hiddenCurrency ? currency.toUpperCase() : ""); // nbsp
  }

  if (type === "auto") {
    type = number > 0 ? "up" : "down";
  }

  if (type === "auto") {
    type = number > 0 ? "up" : "down";
  }

  if (percent) {
    displayNumber = displayNumber + "%";
  }

  if (indicator && type) {
    displayNumber += " " + (type === "up" ? "↑" : "↓");
  }

  if (brackets) {
    displayNumber = `(${displayNumber})`;
  }

  if (!skipRoughly && number > 0 && number < 1e-8) {
    displayNumber = `~${displayNumber}`;
  }

  if (color && (!type || type === "auto")) {
    type = number >= 0 ? "up" : "down";
  }

  if (symbol && number > 0) {
    displayNumber = "+" + displayNumber;
  }

  if (prefix) {
    displayNumber = prefix + displayNumber;
  }

  if (roughly) {
    displayNumber = "≈ " + displayNumber;
  }

  displayNumber = displayNumber.replace(",", ".");

  return (
    <span
      onClick={onClick}
      className={classNames("Number", {
        [type]: type
      })}
      title={!skipTitle ? noExponents(number) : undefined}
    >
      {displayNumber}
    </span>
  );
};

NumberFormat.defaultProps = {
  fractionDigits: null,
  roughly: false,
  percent: false,
  indicator: false,
  brackets: false,
  skipRoughly: false,
  color: false,
  currency: "",
  prefix: "",
  type: null,
  hiddenCurrency: false
};

NumberFormat.propTypes = {
  number: PropTypes.number,
  fractionDigits: PropTypes.number,
  skipTitle: PropTypes.bool,
  color: PropTypes.bool,
  percent: PropTypes.bool,
  prefix: PropTypes.string,
  indicator: PropTypes.bool,
  brackets: PropTypes.bool,
  accurate: PropTypes.bool,
  hiddenCurrency: PropTypes.bool,
  symbol: PropTypes.bool,
  roughly: PropTypes.bool,
  skipRoughly: PropTypes.bool,
  type: PropTypes.oneOf([null, "reject", "auto", "sell", "buy", "down", "up"]),
  currency: PropTypes.string
};

export default React.memo(NumberFormat);
