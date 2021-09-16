import "./SettingPersonal.less";
import React from "react";
import { connect } from "react-redux";
import * as settingsActions from "../../../../../../actions/cabinet/settings";
import * as utils from "../../../../../../utils";
import * as UI from "../../../../../../ui";
import VerificationBlock from "../VerificationBlock/VerificationBlock";
import * as actions from "src/actions/index";
import * as toasts from "src/actions/toasts";
import { openModal } from "src/actions/index";
import * as emitter from "../../../../../../services/emitter";
import { Switch } from "../../../../../../ui";
import { toggleFloodControl, toggleTranslator } from "src/actions/index";
import { userRole } from "../../../../../../actions/cabinet/profile";
import Lang from "../../../../../../components/Lang/Lang";

class SettingPersonal extends React.Component {
  state = {
    firstNameInputError: false,
    lastNameInputError: false,
    loginInputError: false,
    pendingChangeInfo: false,
    pendingChangeLogin: false
  };

  __inputError = (node, stateField) => {
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
  };

  handleToggleTranslator = () => {
    this.props.toggleTranslator(!this.props.translator);
  };

  render() {
    const buttonType = this.props.adaptive ? undefined : "secondary";
    const { profile } = this.props;
    return (
      <>
        {!utils.isProduction() && <VerificationBlock />}
        <UI.ContentBox className="CabinetSettingsScreen__main">
          <div className="CabinetSettingsScreen__header">
            {utils.getLang("cabinet_settingsPersonalInformation")}
          </div>
          <div className="CabinetSettingsScreen__w100wrapper CabinetSettingsScreen__relative">
            <div className="CabinetSettingsScreen__form left">
              <div className="CabinetSettingsScreen__input_field">
                <UI.Input
                  placeholder={utils.getLang(
                    "cabinet_settingsYourFirstName",
                    true
                  )}
                  value={this.props.user.first_name}
                  onTextChange={value =>
                    this.props.setUserFieldValue({ field: "first_name", value })
                  }
                  error={this.state.firstNameInputError}
                />
              </div>
              <div className="CabinetSettingsScreen__input_field">
                <UI.Input
                  placeholder={utils.getLang(
                    "cabinet_settingsYourLastName",
                    true
                  )}
                  value={this.props.user.last_name}
                  onTextChange={value =>
                    this.props.setUserFieldValue({ field: "last_name", value })
                  }
                  error={this.state.lastNameInputError}
                />
              </div>
            </div>
            <div className="CabinetSettingsScreen__form right">
              <UI.Button
                state={this.state.pendingChangeInfo && "loading"}
                type={buttonType}
                onClick={() => {
                  if (!utils.isName(this.props.user.first_name)) {
                    return this.__inputError(this, "firstNameInputError");
                  } else if (!utils.isName(this.props.user.last_name)) {
                    return this.__inputError(this, "lastNameInputError");
                  }
                  this.setState({ pendingChangeInfo: true });
                  actions
                    .gaCode({ dontClose: true })
                    .then(code => {
                      settingsActions
                        .changeInfo({
                          first_name: this.props.user.first_name,
                          last_name: this.props.user.last_name,
                          ga_code: code
                        })
                        .then(data => {
                          toasts.success(
                            utils.getLang("cabinet_nameChangedSuccessfully")
                          );
                        })
                        .catch(error => {
                          toasts.error(error.message);
                        })
                        .finally(() => {
                          this.setState({ pendingChangeInfo: false });
                          emitter.emit("ga_cancel");
                        });
                    })
                    .finally(() => {
                      profile.ga_enabled &&
                        this.setState({ pendingChangeInfo: false });
                    });
                }}
              >
                {utils.getLang("cabinet_settingsSave")}
              </UI.Button>
            </div>
          </div>
          <div className="CabinetSettingsScreen__space" />
          <div className="CabinetSettingsScreen__header">
            {utils.getLang("site__contactLogin")}
          </div>
          <div className="CabinetSettingsScreen__w100wrapper CabinetSettingsScreen__relative">
            <div className="CabinetSettingsScreen__form left">
              <div className="CabinetSettingsScreen__input_field">
                <UI.Input
                  placeholder={utils.getLang("cabinet_settingsYourLogin", true)}
                  value={this.props.user.login}
                  onTextChange={value =>
                    this.props.setUserFieldValue({ field: "login", value })
                  }
                  error={this.state.loginInputError}
                />
              </div>
            </div>
            <div className="CabinetSettingsScreen__form right">
              <UI.Button
                state={this.state.pendingChangeLogin && "loading"}
                type={buttonType}
                onClick={() => {
                  if (!utils.isLogin(this.props.user.login)) {
                    return this.__inputError(this, "loginInputError");
                  }
                  this.setState({ pendingChangeLogin: true });
                  actions
                    .gaCode({ dontClose: true })
                    .then(code => {
                      settingsActions
                        .changeLogin({
                          login: this.props.user.login,
                          ga_code: code
                        })
                        .then(() => {
                          toasts.success(
                            utils.getLang("cabinet_loginChangedSuccessfully")
                          );
                        })
                        .catch(e => {
                          toasts.error(e.message);
                        })
                        .finally(() => {
                          this.setState({ pendingChangeLogin: false });
                          emitter.emit("ga_cancel");
                        });
                    })
                    .finally(() => {
                      profile.ga_enabled &&
                        this.setState({ pendingChangeLogin: false });
                    });
                }}
              >
                {utils.getLang("cabinet_settingsChange")}
              </UI.Button>
            </div>
          </div>
          <div className="CabinetSettingsScreen__space" />
          {/*<div className="CabinetSettingsScreen__header">*/}
          {/*  {utils.getLang('cabinet_settingsPhoneNumber')}*/}
          {/*</div>*/}
          {/*<div className="CabinetSettingsScreen__w100wrapper CabinetSettingsScreen__relative">*/}
          {/*  <div className="CabinetSettingsScreen__form left">*/}
          {/*    <div className="CabinetSettingsScreen__input_field">*/}
          {/*      <UI.Input*/}
          {/*        classNameWrapper="CabinetSettingsScreen__inputWithoutEffects"*/}
          {/*        disabled={true}*/}
          {/*        value={this.props.user.phone_number}*/}
          {/*      />*/}
          {/*    </div>*/}
          {/*  </div>*/}
          {/*  <div className="CabinetSettingsScreen__form right">*/}
          {/*    <UI.Button type={buttonType} onClick={() => {modalGroupActions.openModalPage('change_number')}}>*/}
          {/*      {utils.getLang('cabinet_settingsChange')}*/}
          {/*    </UI.Button>*/}
          {/*  </div>*/}
          {/*</div>*/}
          <div className="CabinetSettingsScreen__header">
            {utils.getLang("cabinet_settingsEmail")}
          </div>
          <div className="CabinetSettingsScreen__w100wrapper CabinetSettingsScreen__relative">
            <div className="CabinetSettingsScreen__form left">
              <div className="CabinetSettingsScreen__input_field">
                <UI.Input
                  classNameWrapper="CabinetSettingsScreen__inputWithoutEffects"
                  disabled={true}
                  value={this.props.user.email}
                />
              </div>
            </div>
            <div className="CabinetSettingsScreen__form right">
              <UI.Button
                type={buttonType}
                onClick={() => {
                  openModal("change_email");
                }}
              >
                {utils.getLang("cabinet_settingsChange")}
              </UI.Button>
            </div>
          </div>
        </UI.ContentBox>

        {userRole("translator") && (
          <UI.ContentBox className="CabinetSettingsScreen__main">
            <div className="CabinetSettingsScreen__header">
              <Lang name="cabinet__translation_mode" />
            </div>
            <UI.Switch
              on={this.props.translator}
              onChange={this.handleToggleTranslator}
            />
          </UI.ContentBox>
        )}

        {!utils.isProduction() && profile.user.applicant_id && (
          <UI.ContentBox className="CabinetSettingsScreen__main">
            <div className="CabinetSettingsScreen__header">applicant_id</div>
            <pre>{profile.user.applicant_id}</pre>
          </UI.ContentBox>
        )}

        {!utils.isProduction() && (
          <UI.ContentBox className="CabinetSettingsScreen__main">
            <div className="CabinetSettingsScreen__header">
              Flood control (only dev mode)
            </div>
            <Switch
              on={this.props.floodControl}
              onChange={this.props.toggleFloodControl}
            />
          </UI.ContentBox>
        )}
      </>
    );
  }
}

export default connect(
  state => ({
    profile: state.default.profile,
    translator: state.settings.translator,
    floodControl: state.settings.floodControl
  }),
  {
    toggleTranslator,
    toggleFloodControl
  }
)(SettingPersonal);
