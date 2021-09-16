import "./SwapFormAdaptive.less";

import { classNames as cn } from "../../../../../../utils";
import React, { useCallback, useEffect } from "react";
import {
  Button,
  CircleIcon,
  ContentBox,
  Input,
  NumberFormat
} from "../../../../../../ui";
import Lang from "../../../../../../components/Lang/Lang";
import { useDispatch, useSelector } from "react-redux";
import {
  walletBalanceSelector,
  walletBalancesSelector,
  walletStatusesSelector,
  walletSwapSelector,
  walletWalletsSelector
} from "../../../../../../selectors";
import {
  walletSwapSetAmount,
  walletSwapSetCurrency,
  walletSwapSetFocus,
  walletSwapStartRatePooling,
  walletSwapStopRatePooling,
  walletSwapSwitch
} from "../../../../../../actions/cabinet/wallet";
import { isFiat } from "../../../../../../utils";
import { getCurrencyInfo, openStateModal } from "../../../../../../actions";
import SVG from "react-inlinesvg";

const Select = ({ value, options, onChange, title, disabled }) => (
  <div className={cn("SwapFormAdaptive__controlPanel__select", { disabled })}>
    <CircleIcon size="extra_small" currency={getCurrencyInfo(value)} />
    <div className="SwapFormAdaptive__controlPanel__select__label">{title}</div>
    <select onChange={e => onChange(e.target.value)} value={value}>
      {options
        .map(o => {
          const currency = getCurrencyInfo(o.currency);
          return currency.can_exchange ? (
            <option value={currency.abbr}>{currency.name}</option>
          ) : (
            false
          );
        })
        .filter(Boolean)}
    </select>
  </div>
);

export default () => {
  const status = useSelector(walletStatusesSelector);
  const swap = useSelector(walletSwapSelector);
  const dispatch = useDispatch();
  const amount = swap.focus === "from" ? swap.fromAmount : swap.toAmount;
  const currency = swap.focus === "from" ? swap.fromCurrency : swap.toCurrency;
  const toCrypto = isFiat(swap.fromCurrency);
  const wallets = useSelector(walletWalletsSelector);
  const balances = useSelector(walletBalancesSelector);

  const fromBalance = useSelector(walletBalanceSelector(swap.fromCurrency));

  const handleChangeAmount = useCallback(
    amount => {
      dispatch(walletSwapSetAmount(swap.focus, amount));
    },
    [dispatch, swap.focus]
  );

  const handleToggleFocus = useCallback(
    amount => {
      dispatch(walletSwapSetFocus(swap.focus === "from" ? "to" : "from"));
    },
    [dispatch, swap.focus]
  );

  useEffect(() => {
    dispatch(walletSwapStartRatePooling());

    return () => {
      dispatch(walletSwapStopRatePooling());
    };
  }, [dispatch]);

  const realRate = isFiat(currency) ? swap.rate : 1 / swap.rate;

  const availableAmount =
    fromBalance?.currency === currency
      ? fromBalance?.amount || 0
      : realRate * (fromBalance?.amount || 0);

  const handleClickMaxAmount = useCallback(() => {
    dispatch(walletSwapSetAmount(swap.focus, availableAmount));
  }, [dispatch, availableAmount, swap.focus]);

  return (
    <ContentBox className="SwapFormAdaptive">
      <div className="SwapFormAdaptive__amountLabel">
        <Lang name="global_amount" />
      </div>

      <Input
        disabled={!!status.rate}
        value={amount}
        onTextChange={handleChangeAmount}
        indicator={
          <Button onClick={handleToggleFocus} size="small" type="secondary">
            {currency.toUpperCase()}
          </Button>
        }
      />

      <div
        onClick={handleClickMaxAmount}
        className="SwapFormAdaptive__maxAmountButton"
      >
        <Lang name="global_available" />
        {": "}
        <NumberFormat number={availableAmount} currency={currency} />
      </div>

      <div className="SwapFormAdaptive__controlPanel">
        <Select
          title={<Lang name="cabinet_fiatWalletGive" />}
          disabled={!!status.rate}
          value={swap.fromCurrency}
          onChange={currency => {
            dispatch(walletSwapSetFocus("from"));
            dispatch(walletSwapSetCurrency("from", currency));
          }}
          options={toCrypto ? balances : wallets}
        />
        <Select
          title={<Lang name="cabinet_fiatWalletGet" />}
          disabled={!!status.rate}
          value={swap.toCurrency}
          onChange={currency => {
            dispatch(walletSwapSetFocus("to"));
            dispatch(walletSwapSetCurrency("to", currency));
          }}
          options={toCrypto ? wallets : balances}
        />
        <div
          onClick={() => {
            !status.rate && dispatch(walletSwapSwitch());
          }}
          className="SwapFormAdaptive__controlPanel__swapButton"
        >
          <Button
            state={status.rate}
            type="secondary"
            className="SwapFormAdaptive__controlPanel__swapButton__circle"
          >
            <SVG src={require("src/asset/24px/switch.svg")} />
          </Button>
        </div>
      </div>

      <Button
        state={status.swap}
        disabled={!!status.rate}
        className="SwapFormAdaptive__submitButton"
        onClick={() => {
          openStateModal("swap_confirm");
        }}
      >
        <Lang name="cabinet_fiatMarketExchangeActionButton" />
      </Button>
    </ContentBox>
  );
};
