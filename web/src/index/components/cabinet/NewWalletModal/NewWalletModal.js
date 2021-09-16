import "./NewWalletModal.less";

import React from "react";
import * as UI from "../../../../ui";
import Currency from "./components/Currency";
import EmptyContentBlock from "../../cabinet/EmptyContentBlock/EmptyContentBlock";
import * as walletsActions from "../../../../actions/cabinet/wallets";
import * as utils from "../../../../utils";
import * as toasts from "../../../../actions/toasts";

const NewWalletModal = props => {
  const handleGenerate = currency => () => {
    walletsActions.generateWallet(currency.abbr).then(() => {
      toasts.success(utils.getLang("cabinet_wallet_created_success"));
      props.onClose();
    });
  };

  let currencies = walletsActions.getNoGeneratedCurrencies();
  const getContent = () => {
    if (currencies.length) {
      return (
        <div className="NewWalletModal__currencies">
          {currencies.map((currency, i) => (
            <Currency
              key={i}
              {...currency}
              onClick={handleGenerate(currency)}
            />
          ))}
        </div>
      );
    } else {
      return (
        <EmptyContentBlock
          icon={require("../../../../asset/120/invest.svg")}
          message={utils.getLang("cabinet_noWalletsAvailable")}
          skipContentClass
        />
      );
    }
  };

  return (
    <UI.Modal noSpacing isOpen={true} onClose={props.onClose}>
      <UI.ModalHeader>
        {utils.getLang("cabinet_walletBox_create")}
      </UI.ModalHeader>
      <div className="NewWalletModal__content">{getContent()}</div>
    </UI.Modal>
  );
};

export default NewWalletModal;
