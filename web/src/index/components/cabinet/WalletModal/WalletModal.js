import "./WalletModal.less";

import React from "react";
import * as UI from "src/ui/index";
import * as actions from "src/actions/index";
import * as utils from "../../../../utils";
import { getCurrencyInfo } from "../../../../actions";
import { getLang } from "../../../../utils";

export default ({ onClose, wallet }) => {
  if (!wallet) {
    onClose();
    return null;
  }

  const currency = getCurrencyInfo(wallet.currency);

  return (
    <UI.Modal className="WalletModal" onClose={onClose}>
      <UI.ModalHeader>
        {getLang("cabinet_walletTransactionModal_my")} {currency.name}{" "}
        {getLang("global_wallet")}
      </UI.ModalHeader>
      <UI.WalletCard
        balance={wallet.amount}
        balanceUsd={wallet.amount * wallet.to_usd}
        currency={currency.abbr}
      />
      <UI.ButtonWrapper align="fill">
        {utils.isFiat(wallet.currency) ? (
          <>
            <UI.Button
              onClick={() => {
                actions.openModal("merchant", {
                  currency: currency.abbr
                });
              }}
              currency={currency}
            >
              {utils.getLang("cabinet_fiatBalance_add")}
            </UI.Button>
            <UI.Button
              disabled={wallet.amount === 0}
              onClick={() => {
                actions.openModal(
                  "merchant",
                  {
                    currency: currency.abbr
                  },
                  { type: "withdrawal" }
                );
              }}
              currency={currency}
            >
              {utils.getLang("global_withdrawal")}
            </UI.Button>
          </>
        ) : (
          <>
            <UI.Button
              disabled={wallet.amount === 0}
              onClick={() => {
                actions.openModal("send", {
                  currency: currency.abbr
                });
              }}
              currency={currency}
            >
              {utils.getLang("cabinet_walletTransactionModal_send")}
            </UI.Button>
            <UI.Button
              onClick={() => {
                actions.openModal("receive", {
                  preset: currency.name
                });
              }}
              currency={currency}
            >
              {utils.getLang("cabinet_walletTransactionModal_receive")}
            </UI.Button>
          </>
        )}
      </UI.ButtonWrapper>
    </UI.Modal>
  );
};
