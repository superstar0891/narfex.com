import "./ChangeEmailModal.less";

import React from "react";
import { connect } from "react-redux";
import * as UI from "../../../../ui";

import * as utils from "../../../../utils";
import * as settingsActions from "../../../../actions/cabinet/settings";
import SVG from "react-inlinesvg";
import * as toastsActions from "../../../../actions/toasts";
import { openModal } from "../../../../actions";

class ChangeEmailModal extends React.Component {
  state = {
    gaCode: "",
    state: "",
    errorGaCode: false,
    newEmail: this.props.newEmail || "",
    errorNewEmail: false
  };

  render() {
    return (
      <UI.Modal isOpen={true} onClose={this.props.onClose} width={424}>
        <UI.ModalHeader>
          {utils.getLang("cabinet_changeEmailModal_name")}
        </UI.ModalHeader>
        {this.__renderContent()}
      </UI.Modal>
    );
  }

  __renderContent() {
    return (
      <div>
        <div className="ChangeEmailModal__input_wrapper">
          <UI.Input
            placeholder={"Enter New Email"}
            autoComplete="off"
            value={this.state.newEmail}
            onChange={e => this.__handleChange(e, "EMAIL")}
            error={this.state.errorNewEmail}
          />
          {this.props.gaEnabled && (
            <UI.Input
              type="code"
              cell
              autoComplete="off"
              mouseWheel={false}
              value={this.state.gaCode}
              onChange={e => this.__handleChange(e, "GA")}
              placeholder={utils.getLang("site__authModalGAPlaceholder", true)}
              error={this.state.errorGaCode}
              indicator={
                <SVG src={require("../../../../asset/google_auth.svg")} />
              }
            />
          )}
        </div>
        <div className="ChangeEmailModal__submit_wrapper">
          <UI.Button
            state={this.state.state}
            onClick={this.__handleSubmit}
            disabled={this.props.gaEnabled && this.state.gaCode.length !== 6}
          >
            {utils.getLang("cabinet_settingsSave")}
          </UI.Button>
        </div>
      </div>
    );
  }

  __handleChange = (e, type) => {
    switch (type) {
      case "EMAIL":
        this.setState({ newEmail: e.target.value });
        break;
      case "GA":
        const val = e.target.value;
        if (val.length <= 6) {
          this.setState({ gaCode: val }, e => {
            if (!this.props.gaEnabled || val.length === 6) {
              this.__handleSubmit();
            }
          });
        }
        break;
      default:
        break;
    }
  };

  __handleSubmit = () => {
    if (!utils.isEmail(this.state.newEmail)) {
      return this.__inputError(this, "errorNewEmail");
    }
    this.setState({ state: "loading" });

    settingsActions
      .changeEmail({
        email: this.state.newEmail,
        ga_code: this.state.gaCode
      })
      .then(data => {
        openModal(
          "check_change_email",
          {},
          {
            newEmail: this.state.newEmail
          }
        );
      })
      .catch(info => {
        this.props.toastPush(info.message, "error");
        switch (info.code) {
          case "ga_auth_code_incorrect": {
            this.setState({ gaCode: "" });
            return this.__inputError(this, "errorGaCode");
          }
          case "email_incorrect":
            return this.__inputError(this, "errorNewEmail");
          default:
            break;
        }
      })
      .finally(() => {
        this.setState({ state: "" });
      });
  };

  __inputError(node, stateField) {
    node.setState(
      {
        [stateField]: true
      },
      () => {
        setTimeout(() => {
          node.setState({
            [stateField]: false
          });
        }, 1000);
      }
    );
  }
}

export default connect(
  state => ({
    gaEnabled: state.default.profile.ga_enabled
  }),
  {
    toastPush: toastsActions.toastPush
  }
)(ChangeEmailModal);
