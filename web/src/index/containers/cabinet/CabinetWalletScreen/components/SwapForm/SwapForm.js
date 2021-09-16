import "./SwapForm.less";
import React, { useEffect, useRef } from "react";
import { useSelector, useDispatch } from "react-redux";
import {
  ContentBox,
  Dropdown,
  CircleIcon,
  Input,
  Button,
  NumberFormat
} from "src/ui";
import Lang from "src/components/Lang/Lang";
import { classNames as cn } from "../../../../../../utils";
import {
  walletBalanceSelector,
  walletBalancesSelector,
  walletStatusesSelector,
  walletSwapSelector,
  walletWalletsSelector
} from "src/selectors";
import { getCurrencyInfo, openStateModal } from "src/actions";
import SVG from "react-inlinesvg";
import {
  walletSetStatus,
  walletSwapSetAmount,
  walletSwapSetCurrency,
  walletSwapSetFocus,
  walletSwapStartRatePooling,
  walletSwapStopRatePooling,
  walletSwapSwitch
} from "src/actions/cabinet/wallet";
import { isFiat } from "../../../../../../utils";

const Form = ({
  onChangeAmount,
  currency,
  secondaryCurrency,
  amount,
  options,
  rate,
  autoFocus,
  onFocus,
  onCurrencyChange,
  disabled,
  currentBalance,
  title
}) => {
  const realRate = isFiat(secondaryCurrency) ? rate : 1 / rate;
  const inputRef = useRef(null);

  return (
    <div className="SwapForm__form">
      <div className="SwapForm__form__label">{title}</div>
      <div className="SwapForm__form__control">
        <Dropdown
          disabled={disabled}
          value={currency}
          options={options
            .map(b => {
              const currency = getCurrencyInfo(b.currency);
              return currency.can_exchange
                ? {
                    prefix: (
                      <CircleIcon size="ultra_small" currency={currency} />
                    ),
                    value: b.currency,
                    title: currency.name
                  }
                : false;
            })
            .filter(Boolean)}
          onChange={({ value }) => onCurrencyChange(value)}
        />
        <div className="SwapForm__form__control__meta">
          <NumberFormat number={1} currency={currency} />
          {" ≈ "}
          <NumberFormat
            skipRoughly
            number={realRate}
            currency={secondaryCurrency}
          />
        </div>
      </div>
      <div className="SwapForm__form__control">
        <Input
          type="number"
          ref={inputRef}
          disabled={disabled}
          onFocus={onFocus}
          autoFocus={autoFocus}
          value={amount}
          onTextChange={onChangeAmount}
        />
        {!!currentBalance && (
          <div
            onClick={() => onChangeAmount(currentBalance)}
            className="SwapForm__form__control__meta active"
          >
            <NumberFormat number={currentBalance} currency={currency} />
          </div>
        )}
      </div>
    </div>
  );
};

export default () => {
  const status = useSelector(walletStatusesSelector);
  const swap = useSelector(walletSwapSelector);
  const wallets = useSelector(walletWalletsSelector);
  const balances = useSelector(walletBalancesSelector);
  const currentBalance = useSelector(walletBalanceSelector(swap.fromCurrency));
  const dispatch = useDispatch();
  const toCrypto = isFiat(swap.fromCurrency);
  const disabled = status.rate === "loading" || status.swap === "loading";

  useEffect(() => {
    dispatch(walletSetStatus("rate", "loading"));
    dispatch(walletSwapStartRatePooling());

    return () => {
      dispatch(walletSwapStopRatePooling());
    };
  }, [dispatch]);

  const swapFromAmount = useRef(swap.fromAmount);
  const currentBalanceAmount = useRef(currentBalance?.amount);

  useEffect(() => {
    if (!swapFromAmount.current && !isNaN(currentBalanceAmount.current)) {
      dispatch(
        walletSwapSetAmount("from", currentBalanceAmount.current || 1000000)
      );
    }
  }, [dispatch, swapFromAmount, currentBalanceAmount]);

  return (
    <ContentBox className="SwapForm">
      <div className="SwapForm__formWrapper">
        <Form
          title={<Lang name="cabinet_fiatWalletGive" />}
          disabled={disabled}
          options={toCrypto ? balances : wallets}
          amount={swap.fromAmount}
          autoFocus={swap.focus === "from"}
          onFocus={() => {
            dispatch(walletSwapSetFocus("from"));
          }}
          currentBalance={currentBalance?.amount}
          currency={swap.fromCurrency}
          secondaryCurrency={swap.toCurrency}
          rate={swap.rate}
          onCurrencyChange={currency =>
            dispatch(walletSwapSetCurrency("from", currency))
          }
          onChangeAmount={amount =>
            dispatch(walletSwapSetAmount("from", amount))
          }
        />
        <div className="SwapForm__separator">
          <div
            className={cn("SwapForm__switchButton", status.rate)}
            onClick={() => {
              dispatch(walletSwapSwitch());
            }}
          >
            <SVG src={require("src/asset/24px/switch.svg")} />
          </div>
        </div>
        <Form
          title={<Lang name="cabinet_fiatWalletGet" />}
          disabled={disabled}
          options={toCrypto ? wallets : balances}
          amount={swap.toAmount}
          autoFocus={swap.focus === "to"}
          onFocus={() => {
            dispatch(walletSwapSetFocus("to"));
          }}
          currency={swap.toCurrency}
          secondaryCurrency={swap.fromCurrency}
          rate={swap.rate}
          onCurrencyChange={currency =>
            dispatch(walletSwapSetCurrency("to", currency))
          }
          onChangeAmount={amount => dispatch(walletSwapSetAmount("to", amount))}
        />
      </div>
      <div className="SwapForm__submitWrapper">
        <Button
          state={status.swap}
          disabled={status.rate === "loading"}
          onClick={() => {
            openStateModal("swap_confirm");
          }}
        >
          <Lang name="cabinet_fiatMarketExchangeActionButton" />
        </Button>
      </div>
    </ContentBox>
  );
};
