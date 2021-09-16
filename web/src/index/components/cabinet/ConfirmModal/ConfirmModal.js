import "./ConfirmModal.less";

import React from "react";
import * as UI from "../../../../ui";
import * as utils from "../../../../utils";
import * as emitter from "../../../../services/emitter";
import { ButtonWrapper } from "../../../../ui";

class ConfirmModal extends React.Component {
  constructor(props) {
    super(props);
    this.__handleClose = this.__handleClose.bind(this);
    this.__handleAccept = this.__handleAccept.bind(this);
  }

  state = {
    status: ""
  };

  __handleClose() {
    emitter.emit("confirm_cancel");
    this.props.onClose();
  }

  __handleAccept() {
    emitter.emit("confirm_accept");
    if (!this.props.dontClose) {
      this.props.onClose();
    } else {
      this.setState({ status: "loading" });
    }
  }

  render() {
    const { props } = this;

    if (!props.title) {
      this.props.onClose();
      return null;
    }

    return (
      <UI.Modal isOpen={true} onClose={this.__handleClose}>
        <UI.ModalHeader>{props.title}</UI.ModalHeader>
        <div className="ConfirmModal">
          {!!props.content && (
            <p className="ConfirmModal__content">{props.content}</p>
          )}
          <ButtonWrapper align="fill">
            <UI.Button
              type={props.type}
              state={this.state.status}
              onClick={this.__handleAccept}
            >
              {props.okText || utils.getLang("global_confirm")}
            </UI.Button>
            <UI.Button type="secondary" onClick={this.__handleClose}>
              {props.cancelText || utils.getLang("global_cancel")}
            </UI.Button>
          </ButtonWrapper>
        </div>
      </UI.Modal>
    );
  }
}

export default ConfirmModal;
