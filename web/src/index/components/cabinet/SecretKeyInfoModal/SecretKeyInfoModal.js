import "./SecretKeyInfoModal.less";

import React from "react";
import * as UI from "../../../../ui";
import * as utils from "../../../../utils";
import COMPANY from "../../../constants/company";

export default class SecretKeyInfoModal extends React.Component {
  render() {
    return (
      <UI.Modal
        isOpen={true}
        className="SecretKeyInfoModal__wrapper"
        onClose={() => this.props.onClose()}
      >
        <UI.ModalHeader>{utils.getLang("global_attention")}</UI.ModalHeader>
        <div className="SecretKeyInfoModal">
          <div className="SecretKeyInfoModal__content">
            <p>{utils.getLang("cabinet_secretKeyInfoModalText1")}</p>
            <p>
              {utils.getLang("cabinet_secretKeyInfoModalText2")}{" "}
              <a href={"mailto:" + COMPANY.email.support}>
                {COMPANY.email.support}
              </a>{" "}
              {utils.getLang("cabinet_secretKeyInfoModalText3")}
            </p>
            <UI.Button onClick={() => this.props.onClose()}>
              {utils.getLang("global_understand")}
            </UI.Button>
          </div>
        </div>
      </UI.Modal>
    );
  }
}
