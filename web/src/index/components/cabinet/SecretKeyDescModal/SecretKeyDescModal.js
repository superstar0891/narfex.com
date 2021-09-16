import "./SecretKeyDescModal.less";

import React from "react";
import * as UI from "../../../../ui";
import * as utils from "../../../../utils";
import * as action from "../../../../actions/index";

export default class SecretKeyDescModal extends React.Component {
  render() {
    return (
      <UI.Modal
        isOpen={true}
        className="SecretKeyDescModal__wrapper"
        onClose={() => {
          this.props.onClose();
        }}
      >
        <UI.ModalHeader>{utils.getLang("global_attention")}</UI.ModalHeader>
        <div className="SecretKeyDescModal">
          {utils.getLang(
            "cabinet_secretKeyDescriptionText",
            <UI.MarkDown
              className="SecretKeyDescModal__content"
              content={utils.getLang("cabinet_secretKeyDescriptionText", true)}
            />
          )}
          <div className="SecretKeyDescModal__content">
            <UI.Button
              onClick={() => {
                action.openModal("change_secret_key");
              }}
            >
              {utils.getLang("global_understand")}
            </UI.Button>
          </div>
        </div>
      </UI.Modal>
    );
  }
}
