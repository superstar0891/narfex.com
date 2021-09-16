import "./DepositPoolSuccessModal.less";

import React from "react";
import { getLang } from "../../../../utils";
import * as UI from "../../../../ui/";

export default props => (
  <UI.Modal
    className="DepositPoolSuccessModal"
    isOpen={true}
    onClose={props.onClose}
  >
    <UI.ModalHeader>
      {getLang("cabinet_depositPoolSuccessTitle")}
    </UI.ModalHeader>
    <div
      style={{
        backgroundImage: `url(${require("../../../../asset/120/success.svg")})`
      }}
      className="DepositPoolSuccessModal__icon"
    ></div>
    <p>{getLang("cabinet_depositPoolSuccessText")}</p>
    <UI.Button onClick={props.onClose}>
      {getLang("global_understand")}
    </UI.Button>
  </UI.Modal>
);
