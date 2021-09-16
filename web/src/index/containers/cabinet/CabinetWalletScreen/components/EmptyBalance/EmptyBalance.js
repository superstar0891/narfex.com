import "./EmptyBalance.less";

import React, { useCallback } from "react";
import { useDispatch, useSelector } from "react-redux";
import { useRouter } from "react-router5";
import {
  Button,
  ButtonWrapper,
  CircleIcon,
  ContentBox
} from "../../../../../../ui";
import { getCurrencyInfo, openModal } from "../../../../../../actions";
import Lang from "../../../../../../components/Lang/Lang";
import * as utils from "src/utils/index";
import * as pages from "../../../../../constants/pages";
import {
  walletSwapSetCurrency,
  walletSwapSwitch
} from "../../../../../../actions/cabinet/wallet";
import { walletSwapSelector } from "../../../../../../selectors";
import SVG from "react-inlinesvg";
import * as actions from "../../../../../../actions";

export default ({ currency }) => {
  const router = useRouter();
  const dispatch = useDispatch();
  const currencyInfo = currency ? getCurrencyInfo(currency) : null;
  const abbr = currencyInfo?.abbr?.toUpperCase();
  const isFiat = utils.isFiat(currency);
  const canExchange = currencyInfo?.can_exchange;

  const walletSwap = useSelector(walletSwapSelector);

  const handleSwap = useCallback(() => {
    if (utils.isFiat(walletSwap.toCurrency)) {
      dispatch(walletSwapSwitch());
    }
    dispatch(walletSwapSetCurrency("to", currency));
    router.navigate(pages.WALLET_SWAP);
  }, [walletSwap.toCurrency, dispatch, router, currency]);

  if (!currency) {
    return (
      <ContentBox className="WalletEmptyBalance">
        <div className="WalletEmptyBalance__content">
          <div className="WalletEmptyBalance__icon">
            <SVG src={require("src/asset/illustrations/savings.svg")} />
          </div>
          <h2>
            <Lang name="cabinet__EmptyBalanceCommonTitle" />
          </h2>
          <p>
            <Lang name="cabinet__EmptyBalanceCommonText" />
          </p>
          {/*<Button*/}
          {/*  onClick={() => {*/}
          {/*    router.navigate(pages.WALLET_SWAP);*/}
          {/*  }}*/}
          {/*>*/}
          {/*  <Lang name="global_buy" />*/}
          {/*</Button>*/}
          <Button
            onClick={() => {
              router.navigate(pages.WALLET_CRYPTO, { currency: "btc" });
              actions.openModal("receive");
            }}
          >
            <Lang name="global_receive" />
          </Button>
        </div>
      </ContentBox>
    );
  }

  return (
    <ContentBox className="WalletEmptyBalance">
      <div className="WalletEmptyBalance__content">
        <CircleIcon currency={currencyInfo} />
        <h2>
          <Lang
            name="cabinet__EmptyBalanceTitle"
            params={{
              currency: abbr
            }}
          />
        </h2>
        {currency !== "fndr" && canExchange && (
          <p>
            <Lang
              name={
                isFiat
                  ? "cabinet__EmptyFiatBalanceText"
                  : "cabinet__EmptyBalanceText"
              }
              params={{
                currency: abbr
              }}
            />
          </p>
        )}
        {isFiat ? (
          <ButtonWrapper align="center">
            <Button
              onClick={() => {
                openModal("merchant");
              }}
            >
              <Lang name="cabinet_fiatBalance_add" />
            </Button>
          </ButtonWrapper>
        ) : (
          <ButtonWrapper align="center">
            {/*{canExchange && (*/}
            {/*  <Button onClick={handleSwap}>*/}
            {/*    <Lang name="global_buy" />*/}
            {/*  </Button>*/}
            {/*)}*/}
            {currency === "fndr" ? (
              <Button
                onClick={() => {
                  router.navigate(pages.FNDR);
                }}
              >
                <Lang name="global_buy" />
              </Button>
            ) : (
              <Button
                onClick={() => {
                  openModal("receive");
                }}
                type={canExchange ? "secondary" : "default"}
              >
                <Lang name="global_receive" />
              </Button>
            )}
          </ButtonWrapper>
        )}
      </div>
    </ContentBox>
  );
};
