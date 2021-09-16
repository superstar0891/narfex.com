import "./GAConfirmModal.less";

import React from "react";
import * as UI from "../../../../ui";

import * as utils from "../../../../utils";
import SVG from "react-inlinesvg";
import * as emitter from "../../../../services/emitter";

export default class GAConfirmModal extends React.Component {
  state = {
    gaCode: "",
    pending: false,
    errorGaCode: false
  };

  componentDidMount() {
    this.clearListener = emitter.addListener("ga_clear", () => {
      this.setState({
        gaCode: "",
        pending: false
      });
    });

    this.closeListener = emitter.addListener("ga_cancel", () => {
      this.props.onClose();
    });
  }

  componentWillUnmount() {
    emitter.removeListener(this.clearListener);
    emitter.removeListener(this.closeListener);
  }

  __handleClose = () => {
    emitter.emit("ga_cancel");
  };

  __handleSubmit = () => {
    emitter.emit("ga_submit", { code: this.state.gaCode });
    if (!this.props.dontClose) {
      this.props.onClose();
    } else {
      this.setState({ pending: true });
    }
  };

  render() {
    return (
      <UI.Modal
        className="GAConfirmModal"
        isOpen={true}
        onClose={this.__handleClose}
        width={384}
      >
        <UI.ModalHeader>
          {utils.getLang("cabinet_ga_modal_name")}
        </UI.ModalHeader>
        {this.__renderContent()}
      </UI.Modal>
    );
  }

  __renderContent() {
    return (
      <div>
        <UI.Input
          autoFocus={true}
          type="code"
          name="ga_code"
          cell
          mouseWheel={false}
          autoComplete="off"
          value={this.state.gaCode}
          onTextChange={this.__handleChange}
          placeholder={utils.getLang("site__authModalGAPlaceholder", true)}
          error={this.state.errorGaCode}
          indicator={<SVG src={require("../../../../asset/google_auth.svg")} />}
        />
        <input className="GAConfirmModal__autoCompleteHack" type="text" />
        <div className="GAConfirmModal__submit_wrapper">
          <UI.Button
            state={this.state.pending && "loading"}
            onClick={this.__handleSubmit}
            disabled={this.state.gaCode.length < 6}
          >
            {utils.getLang("cabinet_settingsSave")}
          </UI.Button>
        </div>
      </div>
    );
  }

  __handleChange = val => {
    if (val.length < 6) {
      this.setState({ gaCode: val });
    } else if (val.length === 6) {
      this.setState({ gaCode: val }, () => {
        this.__handleSubmit();
      });
    }
  };
}

GAConfirmModal.defaultProps = {
  params: {
    onChangeHandler: () => {}
  }
};
