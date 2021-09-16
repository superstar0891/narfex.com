import "./CheckNewEmailModal.less";

import React from "react";
import * as UI from "../../../../ui";

import { openModal } from "../../../../actions/index";
import * as utils from "../../../../utils";

export default class CheckNewEmailModal extends React.Component {
  render() {
    return (
      <UI.Modal isOpen={true} onClose={this.props.onClose} width={384}>
        <UI.ModalHeader>
          {utils.getLang("cabinet_checkNewEmailModal_name")}
        </UI.ModalHeader>
        {this.__renderContent()}
      </UI.Modal>
    );
  }

  __renderContent() {
    return (
      <div className="CheckNewEmailModal">
        <div
          className="CheckNewEmailModal__icon"
          style={{
            background: `url(${require("../../../../asset/120/email_success.svg")})`
          }}
        />
        <div className="CheckNewEmailModal__content">
          <div>{utils.getLang("cabinet_checkNewEmailModal_pleaseCheck")}</div>
          <div className="CheckNewEmailModal__new_email">
            {this.props.newEmail}
          </div>
          <div>{utils.getLang("cabinet_checkNewEmailModal_toComplete")}</div>
        </div>
        <div className={"CheckNewEmailModal__resend_button"}>
          <span
            onClick={() => {
              openModal(
                "change_email",
                {},
                {
                  newEmail: this.props.newEmail
                }
              );
            }}
          >
            {utils.getLang("cabinet_checkNewEmailModal_reSendEmail")}
          </span>
        </div>
        <div className="CheckNewEmailModal__button_wrapper">
          <UI.Button onClick={this.props.onClose}>
            {utils.getLang("site__authModalOk")}
          </UI.Button>
        </div>
      </div>
    );
  }
}

CheckNewEmailModal.defaultProps = {
  // newEmail: 'example@yourmail.domain'
};
