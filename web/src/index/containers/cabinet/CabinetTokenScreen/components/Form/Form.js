import "./Form.less";

import React, { useState, useCallback } from "react";
import {
  Button,
  ButtonWrapper,
  CircleIcon,
  ContentBox,
  Input,
  NumberFormat,
  SwitchButtons
} from "../../../../../../ui";
import Lang from "../../../../../../components/Lang/Lang";
import { useDispatch, useSelector } from "react-redux";
import {
  currencySelector,
  tokenAmountSelector,
  tokenCurrencySelector,
  tokenCurrentPeriodSelector,
  tokenPromoCodeRewardPercentSelector,
  tokenPromoCodeSelector,
  tokenStatusSelector
} from "../../../../../../selectors";
import {
  tokenBuy,
  tokenSetAmount,
  tokenSetCurrency,
  tokenSetPromoCode
} from "../../../../../../actions/cabinet/token";

export default () => {
  const dispatch = useDispatch();
  const [touched, setTouched] = useState(false);
  const token = useSelector(currencySelector("fndr"));
  const { percent, bank } = useSelector(tokenCurrentPeriodSelector);
  const amount = useSelector(tokenAmountSelector);
  const buyStatus = useSelector(tokenStatusSelector("buy"));
  const promoCode = useSelector(tokenPromoCodeSelector);
  const PromoCodeRewardPercent = useSelector(
    tokenPromoCodeRewardPercentSelector
  );
  const tokenCurrency = useSelector(tokenCurrencySelector);
  const currentCurrency = useSelector(currencySelector(tokenCurrency));
  const price = 0.2;

  const handleChangeCurrency = useCallback(
    currency => {
      dispatch(tokenSetCurrency(currency));
    },
    [dispatch]
  );

  const handleChangeAmount = useCallback(
    amount => {
      dispatch(tokenSetAmount(amount));
    },
    [dispatch]
  );

  const handleChangePromoCode = useCallback(
    code => {
      dispatch(tokenSetPromoCode(code));
    },
    [dispatch]
  );

  if (!currentCurrency) {
    return "loading";
  }

  const handleBuy = useCallback(() => {
    if (
      amount >= 10 &&
      (!promoCode || (promoCode.length >= 6 && promoCode.length <= 10))
    ) {
      dispatch(tokenBuy());
    } else {
      setTouched(true);
    }
  }, [dispatch, amount, promoCode]);

  return (
    <ContentBox className="CabinetTokenScreen__form">
      <h1>
        <CircleIcon currency={token} />
        <Lang name="cabinet_token_title" />
      </h1>

      <div className="CabinetTokenScreen__block">
        <h4 className="CabinetTokenScreen__block__title">
          <Lang name="cabinet_token_enterAmount" />
        </h4>
        <div className="CabinetTokenScreen__block__row">
          <div className="CabinetTokenScreen__block__main">
            <Input
              type="number"
              error={
                touched &&
                (amount || 0) < 10 && (
                  <Lang
                    name="cabinet_token_minAmount"
                    params={{
                      amount: 10
                    }}
                  />
                )
              }
              onTextChange={handleChangeAmount}
              value={amount}
              indicator={token.abbr.toUpperCase()}
              description={
                <Lang
                  name="cabinet_token_bankDescription"
                  params={{
                    amount: <NumberFormat number={bank} currency={token.abbr} />
                  }}
                />
              }
            />
          </div>
          <div className="CabinetTokenScreen__block__extra">
            <div className="CabinetTokenScreen__block__extra__title">
              <Lang name="global_bonus" />{" "}
              <NumberFormat number={percent} percent />:
            </div>
            <div className="CabinetTokenScreen__block__extra__amount">
              <NumberFormat
                number={(amount / 100) * percent || 0}
                currency={token.abbr}
                symbol
              />
            </div>
          </div>
        </div>
      </div>

      <div className="CabinetTokenScreen__block">
        <h4 className="CabinetTokenScreen__block__title">
          <Lang name="cabinet_token_enterCurrency" />
        </h4>
        <div className="CabinetTokenScreen__block__row">
          <div className="CabinetTokenScreen__block__main">
            <SwitchButtons
              selected={currentCurrency.abbr}
              tabs={[
                { value: "btc", label: "Bitcoin" },
                { value: "eth", label: "Ethereum" }
              ]}
              onChange={handleChangeCurrency}
            />
            <div className="CabinetTokenScreen__block__main__description">
              <NumberFormat number={1} currency={token.abbr} /> ={" "}
              <NumberFormat number={price} currency="usd" />{" "}
              <NumberFormat
                brackets
                number={price / currentCurrency.to_usd}
                currency={currentCurrency.abbr}
              />
            </div>
          </div>
          <div className="CabinetTokenScreen__block__extra">
            <div className="CabinetTokenScreen__block__extra__title">
              <Lang name="cabinet_token_youWillGive" />:
            </div>
            <div className="CabinetTokenScreen__block__extra__amount">
              <NumberFormat
                number={(amount * price) / currentCurrency.to_usd || 0}
                currency={currentCurrency.abbr}
              />
            </div>
          </div>
        </div>
      </div>

      <div className="CabinetTokenScreen__block">
        <h4 className="CabinetTokenScreen__block__title">
          <Lang name="global_promoCode" />:
        </h4>
        <div className="CabinetTokenScreen__block__row">
          <div className="CabinetTokenScreen__block__main">
            {JSON.stringify()}
            <Input
              error={
                touched &&
                promoCode &&
                (promoCode.length > 10 || promoCode.length < 6) && (
                  <Lang name="global_incorrectPromoCode" />
                )
              }
              onTextChange={handleChangePromoCode}
              value={promoCode}
              placeholder="HMRE54"
            />
          </div>
          <div className="CabinetTokenScreen__block__extra">
            {promoCode.length > 5 && (
              <>
                <div className="CabinetTokenScreen__block__extra__title">
                  Бонус <NumberFormat number={PromoCodeRewardPercent} percent />
                  :
                </div>
                <div className="CabinetTokenScreen__block__extra__amount">
                  <NumberFormat
                    number={(amount * PromoCodeRewardPercent) / 100}
                    currency={token.abbr}
                    symbol
                  />
                </div>
              </>
            )}
          </div>
        </div>
      </div>
      <ButtonWrapper align="center">
        <Button state={buyStatus} onClick={handleBuy}>
          <Lang name="global_buyToken" />
        </Button>
      </ButtonWrapper>
    </ContentBox>
  );
};
