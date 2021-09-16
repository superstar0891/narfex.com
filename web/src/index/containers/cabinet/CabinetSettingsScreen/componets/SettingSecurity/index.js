import "./SettingSecurity.less";

import React, { useState } from "react";
import { connect } from "react-redux";

import * as actions from "../../../../../../actions/index";
import * as settingsActions from "../../../../../../actions/cabinet/settings";
import * as utils from "../../../../../../utils";
import * as UI from "../../../../../../ui";
import * as toastsActions from "../../../../../../actions/toasts";
import { openModal } from "../../../../../../actions/index";
import * as emitter from "../../../../../../services/emitter";
import InputTooltip from "../../../CabinetRegisterScreen/comonents/Input/Input";
import PasswordInfo from "../../../CabinetRegisterScreen/comonents/PasswordInfo/PasswordInfo";
import Lang from "../../../../../../components/Lang/Lang";

function SettingSecurity(props) {
  const { user } = props;
  const [passwordTouched, setPasswordTouched] = useState(false);
  const [pendingChangePassword, setPendingChangePassword] = useState(false);

  const __handleChangePassword = () => {
    if (
      user.old_password &&
      user.re_password &&
      utils.isPassword(user.new_password)
    ) {
      setPendingChangePassword(true);
      actions
        .gaCode({ dontClose: true })
        .then(code => {
          settingsActions
            .changeNewPassword({
              old_password: props.user.old_password,
              password: props.user.new_password,
              ga_code: code
            })
            .then(() => {
              props.toastPush(
                utils.getLang("cabinet_passwordUpdateSuccess"),
                "success"
              );
              props.setUserFieldValue({ field: "old_password", value: "" });
              props.setUserFieldValue({ field: "new_password", value: "" });
              props.setUserFieldValue({ field: "re_password", value: "" });
            })
            .catch(err => {
              props.toastPush(err.message, "error");
            })
            .finally(() => {
              setPendingChangePassword(false);
              emitter.emit("ga_cancel");
            });
        })
        .finally(() => {
          props.profile.ga_enabled && setPendingChangePassword(false);
        });
    } else {
      setPasswordTouched(true);
    }
  };

  return (
    <>
      <UI.ContentBox className="CabinetSettingsScreen__main">
        <div className="CabinetSettingsScreen__header">
          {utils.getLang("cabinet_changePassword")}
        </div>
        <div className="CabinetSettingsScreen__w100wrapper CabinetSettingsScreen__relative">
          <div className="CabinetSettingsScreen__form left">
            <div className="CabinetSettingsScreen__input_field">
              <UI.Input
                type="password"
                error={passwordTouched && !user.old_password}
                placeholder={utils.getLang("cabinet_oldPassword", true)}
                value={user.old_password}
                onTextChange={value =>
                  props.setUserFieldValue({ field: "old_password", value })
                }
              />
            </div>
            <div className="CabinetSettingsScreen__input_field">
              <InputTooltip
                error={passwordTouched && !utils.isPassword(user.new_password)}
                title={<PasswordInfo password={user.new_password} />}
                type="password"
                placeholder={utils.getLang("cabinet_newPassword", true)}
                value={user.new_password}
                onTextChange={value =>
                  props.setUserFieldValue({ field: "new_password", value })
                }
              />
            </div>
            <div className="CabinetSettingsScreen__input_field">
              <InputTooltip
                type="password"
                title={
                  passwordTouched && (
                    <Lang name="registration_passwordConfirmDescription" />
                  )
                }
                error={
                  passwordTouched &&
                  (!user.re_password || user.new_password !== user.re_password)
                }
                placeholder={utils.getLang("cabinet_reEnterNewPassword", true)}
                value={user.re_password}
                onTextChange={value =>
                  props.setUserFieldValue({ field: "re_password", value })
                }
              />
            </div>
          </div>
          <div className="CabinetSettingsScreen__form right">
            <UI.Button
              state={pendingChangePassword && "loading"}
              type={"outline"}
              onClick={__handleChangePassword}
            >
              {utils.getLang("cabinet_settingsSave")}
            </UI.Button>
          </div>
        </div>

        {/*<div className="CabinetSettingsScreen__space"></div>
        <div className="CabinetSettingsScreen__header">{utils.getLang('cabinet_settings2FA')}</div>
        <div className="CabinetSettingsScreen__w100wrapper CabinetSettingsScreen__relative">
          <div className="CabinetSettingsScreen__form left">
            <div className="CabinetSettingsScreen__switch_field">
              <span>{utils.getLang('global_ga')}</span>
              <span>
                <UI.Switch on={true} disabled />
              </span>
            </div>
          </div>
          <div className="CabinetSettingsScreen__form right">
          </div>
        </div>
        <div className="CabinetSettingsScreen__space"></div>
        <div className="CabinetSettingsScreen__header">
          {utils.getLang("site__authModalSecretKey")}
        </div>
        <div className="CabinetSettingsScreen__space"></div>
        <div className="CabinetSettingsScreen__header">{utils.getLang('cabinet_sessionTimeout')}</div>
        <div className="CabinetSettingsScreen__relative">
          <p>Session timeout represents the event occuring when a user do not perform any action on a web site during a interval (defined by web server). The event, on server side, change the status of the user session to 'invalid' (ie. "not used anymore") and instruct the web server to destroy it (deleting all data contained into it).</p>
          <UI.Range min={0} max={12} formatLabel={value => value + " Hour"} value={3} />
        </div>*/}
      </UI.ContentBox>

      <UI.ContentBox className="CabinetSettingsScreen__main">
        <div className="CabinetSettingsScreen__header">
          {utils.getLang("cabinet_secretKey")}
        </div>
        <UI.Button
          onClick={() => {
            actions.openModal(
              props.hasSecretKey ? "secret_key_info" : "secret_key"
            );
          }}
        >
          {props.hasSecretKey
            ? utils.getLang("global_update")
            : utils.getLang("cabinet_setupSecretKey")}
        </UI.Button>
      </UI.ContentBox>

      <UI.ContentBox className="CabinetSettingsScreen__main">
        <div className="CabinetSettingsScreen__header">
          {utils.getLang("cabinet_settings2FA")}
        </div>

        <UI.Switch
          on={props.profile.ga_enabled}
          disabled={props.profile.ga_enabled}
          onChange={() => {
            openModal("google_code");
          }}
          label={utils.getLang("global_ga")}
        />
      </UI.ContentBox>
    </>
  );
}

export default connect(
  state => ({
    ...state.settings,
    profile: state.default.profile,
    adaptive: state.default.adaptive,
    translator: state.settings.translator,
    hasSecretKey: state.default.profile.has_secret_key
  }),
  {
    setTitle: actions.setTitle,
    loadSettings: settingsActions.loadSettings,
    setUserFieldValue: settingsActions.setUserFieldValue,
    toastPush: toastsActions.toastPush
  }
)(SettingSecurity);
