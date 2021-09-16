import "./WalletHeader.less";

import React from "react";
import { useRouter } from "react-router5";
import { useSelector } from "react-redux";
import { Button, ContentBox, NumberFormat } from "../../../../../../ui";
import Lang from "../../../../../../components/Lang/Lang";
import { currencySelector } from "../../../../../../selectors";
import * as actions from "../../../../../../actions";
import * as pages from "../../../../../constants/pages";

export default ({ balance, isCrypto }) => {
  const currency = useSelector(currencySelector(balance?.currency));
  const router = useRouter();

  const Buttons = () => {
    if (currency.abbr === "fndr") {
      return (
        <Button
          onClick={() => {
            router.navigate(pages.FNDR);
          }}
          size="middle"
        >
          <Lang name="global_buy" />
        </Button>
      );
    } else if (isCrypto) {
      return (
        <>
          <Button
            onClick={() => {
              actions.openModal("receive");
            }}
            size="middle"
          >
            <Lang name="cabinet_walletTransactionModal_receive" />
          </Button>
          <Button
            onClick={() => {
              actions.openModal("send");
            }}
            size="middle"
            type="secondary"
          >
            <Lang name="cabinet_walletTransactionModal_send" />
          </Button>
        </>
      );
    } else {
      return (
        <>
          <Button
            onClick={() => {
              actions.openModal("merchant");
            }}
            size="middle"
          >
            <Lang name="cabinet_fiatBalance_add" />
          </Button>
          <Button
            onClick={() => {
              actions.openModal("merchant", {}, { type: "withdrawal" });
            }}
            size="middle"
            type="secondary"
          >
            <Lang name="global_withdrawal" />
          </Button>
        </>
      );
    }
  };

  return (
    <ContentBox className="WalletHeader">
      <div className="WalletHeader__content">
        <div className="WalletHeader__label">
          <span className="WalletHeader__label__currency">{currency.name}</span>
          <span className="WalletHeader__label__usd">
            <NumberFormat
              number={balance.to_usd * balance.amount}
              currency="usd"
            />
          </span>
        </div>
        <div className="WalletHeader__amount">
          <NumberFormat number={balance.amount} currency={balance.currency} />
        </div>
      </div>
      <div className="WalletHeader__buttons">
        <Buttons />
      </div>
    </ContentBox>
  );
};
