import "./ChangeNumberModal.less";

import React from "react";
import { connect } from "react-redux";
import * as UI from "../../../../ui";

import ConfirmSmsModal from "../../cabinet/ConfirmSmsModal/ConfirmSmsModal";

import * as utils from "../../../../utils";
// import ReactPhoneInput from 'react-phone-input-2'
import "react-phone-input-2/dist/style.css";
import { isValidPhoneNumber } from "react-phone-number-input";
import * as settingsActions from "../../../../actions/cabinet/settings";

import SVG from "react-inlinesvg";
import * as toastsActions from "../../../../actions/toasts";

class ChangeNumberModal extends React.Component {
  state = {
    gaCode: "",
    errorGaCode: false,
    phone: "",
    dialCode: "",
    phoneWithoutCode: "",
    pending: false
  };

  render() {
    return (
      <UI.Modal isOpen={true} onClose={this.props.onClose} width={424}>
        <UI.ModalHeader>
          {utils.getLang("cabinet_changeNumberModal_name")}
        </UI.ModalHeader>
        {this.__renderContent()}
      </UI.Modal>
    );
  }

  __renderContent() {
    return (
      <div className="ChangeNumberModal__input_padding">
        <div className="ChangeNumberModal__input_wrapper">
          {/*<ReactPhoneInput*/}
          {/*  defaultCountry={'ru'}*/}
          {/*  value={this.state.phone}*/}
          {/*  onChange={this.__handleOnChangePhone}*/}
          {/*  enableSearchField={true}*/}
          {/*  disableSearchIcon={true}*/}
          {/*  countryCodeEditable={false}*/}
          {/*  searchPlaceholder="Сountry search or code"*/}
          {/*  autoFocus={true}*/}
          {/*  searchClass="ChangeNumberModal__PhoneInput_searchClass"*/}
          {/*  dropdownClass="ChangeNumberModal__PhoneInput_dropdownClass"*/}
          {/*  buttonClass="ChangeNumberModal__PhoneInput_buttonClass"*/}
          {/*  inputClass="ChangeNumberModal__PhoneInput_inputClass"*/}
          {/*  //containerClass={'ChangeNumberModal__PhoneInput_containerClass'}*/}

          {/*  preferredCountries={['ru', 'id']}*/}
          {/*/>*/}
        </div>
        <UI.Input
          type="code"
          cell
          autoComplete="off"
          value={this.state.gaCode}
          onChange={this.__handleChange}
          mouseWheel={false}
          placeholder={utils.getLang("site__authModalGAPlaceholder", true)}
          error={this.state.errorGaCode}
          indicator={<SVG src={require("../../../../asset/google_auth.svg")} />}
        />
        <div className="ChangeNumberModal__submit_wrapper">
          <UI.Button
            onClick={this.__handleSubmit}
            disabled={
              this.state.pending ||
              this.state.gaCode.length < 6 ||
              !isValidPhoneNumber(this.state.phone)
            }
          >
            {utils.getLang("cabinet_settingsSave")}
          </UI.Button>
        </div>
      </div>
    );
  }

  __handleOnChangePhone = (value, data) => {
    this.setState({
      phone: value,
      dialCode: data.dialCode,
      phoneWithoutCode: value
        .replace("+" + data.dialCode, "")
        .replace(/[^\d;]/g, "")
    });
  };

  __handleChange = e => {
    const val = e.target.value;

    if (val.length < 6) {
      this.setState({ gaCode: val });
    } else if (val.length === 6) {
      this.setState({ gaCode: val }, () => {
        this.__handleSubmit();
      });
    }
  };

  __handleSubmit = () => {
    if (isValidPhoneNumber(this.state.phone)) {
      this.setState({ pending: true });
      settingsActions
        .sendSmsCode({
          phone_code: this.state.dialCode,
          phone_number: this.state.phoneWithoutCode,
          ga_code: this.state.gaCode
        })
        .then(data => {
          this.props.openModalPage(
            null,
            {},
            {
              children: ConfirmSmsModal,
              params: {
                dialCode: this.state.dialCode,
                phoneWithoutCode: this.state.phoneWithoutCode
              }
            }
          );
        })
        .catch(info => {
          switch (info.code) {
            case "ga_auth_code_incorrect":
              this.setState(
                {
                  errorGaCode: true
                },
                () => {
                  setTimeout(() => {
                    this.setState({
                      errorGaCode: false
                    });
                  }, 1000);
                }
              );
              break;
            default:
              this.props.toastPush(info.message, "error");
              break;
          }
        })
        .finally(() => {
          this.setState({ pending: false });
        });
    }
  };
}

export default connect(null, {
  toastPush: toastsActions.toastPush
})(ChangeNumberModal);
