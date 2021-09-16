import "./WalletBox.less";

import React from "react";

import * as actions from "../../../../actions";
import * as utils from "../../../../utils";

export default function WalletBoxNew() {
  return (
    <div className="WalletBox" onClick={() => actions.openModal("new_wallet")}>
      <div className="WalletBox__content new">
        {utils.getLang("cabinet_walletBox_create")}
      </div>
    </div>
  );
}
