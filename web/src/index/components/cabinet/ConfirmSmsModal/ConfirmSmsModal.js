import "./ConfirmSmsModal.less";

import React from "react";
import { connect } from "react-redux";
import * as UI from "../../../../ui";

import * as utils from "../../../../utils";
import * as settingsActions from "../../../../actions/cabinet/settings";
import * as toastsActions from "../../../../actions/toasts";

class ConfirmSmsModal extends React.Component {
  state = {
    smsCode: "",
    errorSmsCode: false
  };

  render() {
    return (
      <UI.Modal
        isOpen={true}
        onClose={() => {
          this.props.close();
        }}
        width={424}
      >
        <UI.ModalHeader>
          {utils.getLang("cabinet_confirmSmsModal_name")}
        </UI.ModalHeader>
        {this.__renderContent()}
      </UI.Modal>
    );
  }

  __renderContent() {
    return (
      <div className="ChangeNumberModal__input_padding">
        <div className="ChangeNumberModal__input_wrapper">
          <UI.Input
            autoFocus
            type="number"
            autoComplete="off"
            value={this.state.smsCode}
            onChange={this.__handleChange}
            placeholder={utils.getLang("cabinet_confirmSmsModal_enter", true)}
            onKeyPress={e =>
              e.key === "Enter" && this.state.smsCode.length < 4
                ? this.__handleSubmit()
                : null
            }
            error={this.state.errorSmsCode}
          />
        </div>
        <div className="ChangeNumberModal__submit_wrapper">
          <UI.Button
            onClick={this.__handleSubmit}
            disabled={this.state.smsCode.length < 4}
          >
            {utils.getLang("site__authModalSubmit")}
          </UI.Button>
        </div>
      </div>
    );
  }

  __handleChange = e => {
    const val = e.target.value;

    if (val.length < 4) {
      this.setState({ smsCode: val });
    } else if (val.length === 4) {
      this.setState({ smsCode: val }, () => {
        this.__handleSubmit();
      });
    }
  };

  __handleSubmit = () => {
    const regex = /[0-9]|\./;
    if (regex.test(this.state.smsCode)) {
      settingsActions
        .changeNumber({
          phone_code: this.props.params.dialCode,
          phone_number: this.props.params.phoneWithoutCode,
          sms_code: this.state.smsCode
        })
        .then(({ phone_number }) => {
          this.props.onClose();
          this.props.setUserFieldValue({
            field: "phone_number",
            value: phone_number
          });
          this.props.toastPush(
            utils.getLang("cabinet_phoneNumberChangedSuccessfully"),
            "success"
          );
        })
        .catch(info => {
          switch (info.response) {
            case "invalid_code":
              this.setState(
                {
                  errorSmsCode: true
                },
                () => {
                  setTimeout(() => {
                    this.setState({
                      errorSmsCode: false
                    });
                  }, 1000);
                }
              );
              break;
            default:
              this.props.toastPush(utils.getLang(info.message), "error");
              break;
          }
        });
    }
  };
}

export default connect(null, {
  setUserFieldValue: settingsActions.setUserFieldValue,
  toastPush: toastsActions.toastPush
})(ConfirmSmsModal);
